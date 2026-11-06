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
        foreach ($modules as $m) {
            $mc = $comps->where('mc_module_id', $m->id);
            if ($mc->isEmpty()) {
                $add(false, "Module {$m->seq} has no assessment, so it cannot be achieved");
            } elseif (in_array($m->id, $achieved, true)) {
                $add(true, "Module {$m->seq}: achieved");
            } else {
                $add(false, "Module {$m->seq} not achieved: ".$mc->reject($met)->map(fn ($c) => ($c->name_en ?: 'assessment').' '.$why($c))->implode('; '));
            }
        }
        foreach ($comps->whereNull('mc_module_id') as $c) {
            $add($met($c), $met($c) ? ($c->name_en ?: 'Assessment').': met' : ($c->name_en ?: 'Assessment').' '.$why($c));
        }
        $add(! DB::table('integrity_cases')->where('enrolment_id', $enrolmentId)->where('status', 'open')->exists(), DB::table('integrity_cases')->where('enrolment_id', $enrolmentId)->where('status', 'open')->exists() ? 'An integrity case is open' : 'No open integrity case');
        if ($e->modules_completed !== null && $modules->count() > 0) { // the learning platform's progress is a condition only when it was reported
            $add($e->modules_completed >= $modules->count(), "Learning platform: {$e->modules_completed} of {$modules->count()} modules completed");
        }

        return ['met' => collect($items)->every('ok'), 'items' => $items];
    }

    /** Weighted final result across the assessments of the version. */
    public static function finalResult(int $enrolmentId): array
    {
        $e = DB::table('enrolments')->find($enrolmentId);
        $rows = DB::table('assessment_components as c')->leftJoin('assessment_results as r', fn ($j) => $j->on('r.assessment_component_id', '=', 'c.id')->where('r.enrolment_id', $enrolmentId))
            ->where('c.mc_version_id', $e->mc_version_id)->orderBy('c.id')->get(['c.name_en', 'c.weight_percent', 'c.max_mark', 'c.pass_mark', 'r.score']);
        $weights = (float) $rows->sum('weight_percent');
        $total = $weights > 0 ? $rows->sum(fn ($r) => ((float) $r->score / (float) $r->max_mark) * (float) $r->weight_percent) / $weights * 100 : null;

        return ['weighted_percent' => $total === null ? null : round($total, 1), 'components' => $rows->map(fn ($r) => ['name' => $r->name_en, 'score' => (float) $r->score, 'max' => (float) $r->max_mark, 'weight' => (float) $r->weight_percent])->all()];
    }

    /** Participants of a cohort that meet every requirement and still wait for the deputy dean's confirmation. */
    public static function waiting(int $offeringId): int
    {
        return DB::table('enrolments')->where('offering_id', $offeringId)->where('status', 'enrolled')->pluck('id')->filter(fn ($id) => self::check($id)['met'])->count();
    }

    /** "A new task appeared for completion" (FL-08): opened while someone waits, closed when no one does. */
    public static function syncTask(int $offeringId): void
    {
        $o = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('o.id', $offeringId)->first(['o.cohort_name', 'v.name_en', 'm.ptj_id']);
        $open = DB::table('tasks')->where('kind', 'completion')->where('ref_id', $offeringId)->whereNull('completed_at')->exists();
        if (self::waiting($offeringId) > 0 && ! $open) {
            Tasks::open('deputy_dean', 'completion', $offeringId, 'Confirm completion: '.$o->name_en.' '.$o->cohort_name, "/completion/$offeringId", $o->ptj_id, area: 'results');
        } elseif (self::waiting($offeringId) === 0 && $open) {
            Tasks::close('completion', $offeringId);
        }
    }
}
