<?php

declare(strict_types=1);

namespace App\Modules\Achievement;

use App\Modules\Core\Tasks;
use Illuminate\Support\Facades\DB;

// MD-08: the automatic completion check (FR-131, FR-134). It lists what is missing; confirming is a separate human decision (D-15).
final class Completion
{
    /** The record of an enrolment, created when it is first needed. */
    public static function record(int $enrolmentId): object
    {
        $e = DB::table('enrolments')->find($enrolmentId);
        DB::table('achievement_records')->insertOrIgnore(['enrolment_id' => $enrolmentId, 'mc_version_id' => $e->mc_version_id, 'created_at' => now()]);

        return DB::table('achievement_records')->where('enrolment_id', $enrolmentId)->first();
    }

    /** not_started, in_progress or complete. Module status Achieved does not complete the record. */
    public static function status(int $enrolmentId): string
    {
        if (DB::table('achievement_records')->where('enrolment_id', $enrolmentId)->where('completion_status', 'complete')->exists()) {
            return 'complete';
        }

        return DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->where('status', 'verified')->exists() ? 'in_progress' : 'not_started';
    }

    /**
     * Only the modules and assessments of the version the participant is enrolled on count; modules of another structure never do (FR-132).
     *
     * @return array{met: bool, items: list<array{ok: bool, text: string}>}
     */
    public static function check(int $enrolmentId): array
    {
        $e = DB::table('enrolments')->find($enrolmentId);
        $items = [];
        $add = function (bool $ok, string $text) use (&$items) { $items[] = ['ok' => $ok, 'text' => $text]; };

        $add($e->status === 'enrolled' || $e->status === 'completed', $e->status === 'enrolled' || $e->status === 'completed' ? 'Enrolled' : 'The registration is '.strtolower(config('enrolment.status')[$e->status]));
        $comps = DB::table('assessment_components')->where('mc_version_id', $e->mc_version_id)->orderBy('id')->get();
        $results = DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->get()->keyBy('assessment_component_id');
        $met = fn ($c) => isset($results[$c->id]) && $results[$c->id]->status === 'verified' && (float) $results[$c->id]->score >= (float) $c->pass_mark;
        $why = function ($c) use ($results) {
            $r = $results[$c->id] ?? null;

            return ! $r || $r->score === null ? 'no mark' : ($r->status !== 'verified' ? 'not verified yet' : 'below the pass mark ('.rtrim(rtrim((string) $r->score, '0'), '.').' of '.rtrim(rtrim((string) $c->pass_mark, '0'), '.').' needed)');
        };

        $modules = DB::table('mc_modules')->where('mc_version_id', $e->mc_version_id)->orderBy('seq')->get();
        $achieved = DB::table('module_achievements')->where('enrolment_id', $enrolmentId)->pluck('mc_module_id')->all();

        // Cross-cohort modular accumulation within completion window (Guide Cl. 7.4)
        $windowMonths = (int) (DB::table('offerings')->where('id', $e->offering_id)->value('completion_window_months')
            ?: (app(\App\Modules\Core\Settings::class)->get('umc.completion_window_months') ?: 36));

        $otherEnrolments = DB::table('enrolments')
            ->where('user_id', $e->user_id)
            ->where('mc_version_id', $e->mc_version_id)
            ->where('id', '!=', $enrolmentId)
            ->pluck('id');

        $crossCohortAchieved = [];
        if ($otherEnrolments->isNotEmpty()) {
            $crossAch = DB::table('module_achievements')
                ->whereIn('enrolment_id', $otherEnrolments)
                ->where('achieved_at', '>=', now()->subMonths($windowMonths))
                ->get();
            foreach ($crossAch as $ca) {
                $cComps = $comps->where('mc_module_id', $ca->mc_module_id);
                if ($cComps->isNotEmpty()) {
                    $caResults = DB::table('assessment_results')
                        ->where('enrolment_id', $ca->enrolment_id)
                        ->whereIn('assessment_component_id', $cComps->pluck('id'))
                        ->get()
                        ->keyBy('assessment_component_id');
                    $caMet = $cComps->every(fn ($c) => isset($caResults[$c->id]) && $caResults[$c->id]->status === 'verified' && (float) $caResults[$c->id]->score >= (float) $c->pass_mark);
                    if ($caMet) {
                        $achieved[] = $ca->mc_module_id;
                        $crossCohortAchieved[] = $ca->mc_module_id;
                    }
                } else {
                    $achieved[] = $ca->mc_module_id;
                    $crossCohortAchieved[] = $ca->mc_module_id;
                }
            }
        }
        $achieved = array_values(array_unique($achieved));

        $version = DB::table('mc_versions')->find($e->mc_version_id);
        $isSeparateAssessment = ($version->credit_model ?? '') === 'separate_assessment';

        foreach ($modules as $m) {
            $mc = $comps->where('mc_module_id', $m->id);
            if ($isSeparateAssessment && ! $m->is_assessment) {
                // In separate assessment, learning modules have no per-module summative marks;
                // they are completed via LMS progress or prior achievement
                $lmsDone = $e->modules_completed !== null && $e->modules_completed >= $m->seq;
                $isAch = $lmsDone || in_array($m->id, $achieved, true);
                $add($isAch, "Module {$m->seq} (Learning): ".($isAch ? 'completed' : 'in progress on learning platform'));
            } elseif ($mc->isEmpty()) {
                if ($isSeparateAssessment) {
                    $add(true, "Module {$m->seq}: learning module completed");
                } else {
                    $add(false, "Module {$m->seq} has no assessment, so it cannot be achieved");
                }
            } else {
                $isMet = $mc->every($met);
                if ($isMet || in_array($m->id, $crossCohortAchieved, true)) {
                    $add(true, "Module {$m->seq}: achieved");
                } else {
                    $missing = $mc->reject($met)->map(fn ($c) => ($c->name_en ?: 'assessment').' '.$why($c))->filter()->implode('; ');
                    $add(false, "Module {$m->seq} not achieved".($missing ? ": $missing" : ''));
                }
            }
        }
        foreach ($comps->whereNull('mc_module_id') as $c) {
            $add($met($c), $met($c) ? ($c->name_en ?: 'Assessment').': met' : ($c->name_en ?: 'Assessment').' '.$why($c));
        }
        $add(! DB::table('integrity_cases')->where('enrolment_id', $enrolmentId)->where('status', 'open')->exists(), DB::table('integrity_cases')->where('enrolment_id', $enrolmentId)->where('status', 'open')->exists() ? 'An integrity case is open' : 'No open integrity case');
        $learningModuleCount = $isSeparateAssessment ? $modules->where('is_assessment', false)->count() : $modules->count();
        if ($e->modules_completed !== null && $learningModuleCount > 0) { // the learning platform's progress is a condition only when it was reported
            $add($e->modules_completed >= $learningModuleCount, "Learning platform: {$e->modules_completed} of {$learningModuleCount} modules completed");
        }

        return ['met' => collect($items)->every('ok'), 'items' => $items];
    }

    /** Weighted final result across the assessments of the version. Only verified results count. */
    public static function finalResult(int $enrolmentId): array
    {
        $e = DB::table('enrolments')->find($enrolmentId);
        $rows = DB::table('assessment_components as c')
            ->leftJoin('assessment_results as r', fn ($j) => $j->on('r.assessment_component_id', '=', 'c.id')->where('r.enrolment_id', $enrolmentId)->where('r.status', 'verified'))
            ->where('c.mc_version_id', $e->mc_version_id)->orderBy('c.id')->get(['c.name_en', 'c.weight_percent', 'c.max_mark', 'c.pass_mark', 'r.score', 'r.status']);
        
        $hasMissing = $rows->contains(fn ($r) => $r->score === null);
        $weights = (float) $rows->sum('weight_percent');
        $total = (! $hasMissing && $weights > 0)
            ? $rows->sum(fn ($r) => ((float) $r->score / (float) $r->max_mark) * (float) $r->weight_percent) / $weights * 100
            : null;

        return ['weighted_percent' => $total === null ? null : round($total, 1), 'components' => $rows->map(fn ($r) => ['name' => $r->name_en, 'score' => $r->score !== null ? (float) $r->score : null, 'max' => (float) $r->max_mark, 'weight' => (float) $r->weight_percent])->all()];
    }

    /** Whether the Course Coordinator / Lecturer (P1, Appendix F11 Row 1) has signed off completion. */
    public static function coordinatorSigned(int $enrolmentId): bool
    {
        return DB::table('achievement_records')->where('enrolment_id', $enrolmentId)->whereNotNull('coordinator_signed_at')->exists();
    }

    /** Participants of a cohort that meet academic requirements and wait for the Course Coordinator (P1) sign-off. */
    public static function waitingCoordinator(int $offeringId): int
    {
        return DB::table('enrolments')->where('offering_id', $offeringId)->where('status', 'enrolled')->pluck('id')
            ->filter(fn ($id) => self::check((int) $id)['met'] && ! self::coordinatorSigned((int) $id))->count();
    }

    /** Participants of a cohort that meet every requirement, have P1 coordinator sign-off, and wait for Deputy Dean confirmation. */
    public static function waiting(int $offeringId): int
    {
        return DB::table('enrolments')->where('offering_id', $offeringId)->where('status', 'enrolled')->pluck('id')
            ->filter(fn ($id) => self::check((int) $id)['met'] && self::coordinatorSigned((int) $id))->count();
    }

    /** Opens P1 coordinator sign-off task first, then P2 deputy dean completion confirmation task (Guide App H3 & F11). */
    public static function syncTask(int $offeringId): void
    {
        $o = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('o.id', $offeringId)->first(['o.cohort_name', 'v.name_en', 'm.ptj_id', 'm.owner_id']);
        if (! $o) {
            return;
        }

        $waitingCoord = self::waitingCoordinator($offeringId);
        $openCoord = DB::table('tasks')->where('kind', 'coordinator_signoff')->where('ref_id', $offeringId)->whereNull('completed_at')->exists();
        if ($waitingCoord > 0 && ! $openCoord) {
            Tasks::open('lecturer', 'coordinator_signoff', $offeringId, 'Coordinator sign-off (F11): '.$o->name_en.' '.$o->cohort_name, "/cohorts/$offeringId/signoff", $o->ptj_id, userId: $o->owner_id, area: 'results');
        } elseif ($waitingCoord === 0 && $openCoord) {
            Tasks::close('coordinator_signoff', $offeringId);
        }

        $waitingDean = self::waiting($offeringId);
        $openDean = DB::table('tasks')->where('kind', 'completion')->where('ref_id', $offeringId)->whereNull('completed_at')->exists();
        if ($waitingDean > 0 && ! $openDean) {
            Tasks::open('deputy_dean', 'completion', $offeringId, 'Confirm completion: '.$o->name_en.' '.$o->cohort_name, "/completion/$offeringId", $o->ptj_id, area: 'results');
        } elseif ($waitingDean === 0 && $openDean) {
            Tasks::close('completion', $offeringId);
        }
    }
}
