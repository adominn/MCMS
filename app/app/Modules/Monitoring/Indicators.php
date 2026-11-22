<?php

declare(strict_types=1);

namespace App\Modules\Monitoring;

use App\Modules\Achievement\Completion;
use App\Modules\Core\Settings;
use Illuminate\Support\Facades\DB;

// MD-14: the cohort indicators of Appendix C2, outcome attainment (C3), module progress and anonymous feedback (C4).
final class Indicators
{
    public function __construct(private Settings $settings) {}

    public function for(int $offeringId): array
    {
        $o = DB::table('offerings')->find($offeringId);
        $people = DB::table('enrolments')->where('offering_id', $offeringId)->whereIn('status', ['enrolled', 'completed', 'not_completed'])->get();
        $n = $people->count();
        $comps = DB::table('assessment_components')->where('mc_version_id', $o->mc_version_id)->get();
        $results = DB::table('assessment_results')->whereIn('enrolment_id', $people->pluck('id'))->where('status', 'verified')->get()->groupBy('enrolment_id');
        $passed = fn (int $eid, $cs) => $cs->isNotEmpty() && $cs->every(fn ($c) => (($r = ($results[$eid] ?? collect())->firstWhere('assessment_component_id', $c->id)) && $r->status === 'verified' && (float) $r->score >= (float) $c->pass_mark));
        $pct = fn (int|float $a) => $n > 0 ? round($a / $n * 100, 1) : null;

        $active = $people->filter(fn ($e) => isset($results[$e->id]) || (int) $e->progress_percent > 0)->count();
        $completed = $people->where('status', 'completed');
        $issued = DB::table('credentials')->whereIn('enrolment_id', $completed->pluck('id'))->whereNotIn('status', ['replaced', 'revoked'])->distinct()->count('enrolment_id');
        $marks = $people->map(fn ($e) => Completion::finalResult($e->id)['weighted_percent'])->filter(fn ($m) => $m !== null)->sort()->values();
        $median = $marks->isEmpty() ? null : round($marks->count() % 2 ? $marks[intdiv($marks->count(), 2)] : ($marks[$marks->count() / 2 - 1] + $marks[$marks->count() / 2]) / 2, 1);

        $cqi = DB::table('cqi_records')->where('offering_id', $offeringId)->orderByDesc('id')->first();
        $accessIssuesCount = $cqi ? (int) $cqi->c2_access_issues_count : 0;
        $accessIssuesStatus = $cqi ? $cqi->c2_access_issues_status : ($accessIssuesCount > 0 ? 'ada' : 'tiada');
        $accessIssuesNotes = $cqi ? $cqi->c2_access_issues_notes : null;

        $rows = [
            ['key' => 'participation', 'label' => 'Active participation rate', 'value' => $pct($active), 'target' => (int) $this->settings->get('quality.target_participation_percent')],
            ['key' => 'completion', 'label' => 'Completion rate', 'value' => $pct($completed->count()), 'target' => (int) $this->settings->get('quality.target_completion_percent')],
            ['key' => 'pass', 'label' => 'Pass rate', 'value' => $pct($people->filter(fn ($e) => $passed($e->id, $comps))->count()), 'target' => (int) $this->settings->get('quality.target_pass_percent')],
            ['key' => 'issuance', 'label' => 'Credential issuance rate', 'value' => $completed->count() > 0 ? round($issued / $completed->count() * 100, 1) : null, 'target' => (int) $this->settings->get('quality.target_issuance_percent')],
            ['key' => 'access_issues', 'label' => 'Significant access/technical issues', 'value' => $accessIssuesStatus === 'ada' || $accessIssuesCount > 0 ? 'Ada' : 'Tiada', 'target' => 'Tiada', 'notes' => $accessIssuesNotes],
        ];
        foreach ($rows as &$r) {
            $r['not_recorded'] = $r['value'] === null;
            if ($r['key'] === 'access_issues') {
                $r['below'] = $r['value'] === 'Ada';
            } else {
                $r['below'] = $r['value'] !== null && is_numeric($r['value']) && (float) $r['value'] < (float) $r['target'];
            }
        }

        $outcomes = DB::table('learning_outcomes')->where('mc_version_id', $o->mc_version_id)->orderBy('sort_order')->get()->map(function ($lo) use ($people, $comps, $passed, $n) {
            $mapped = $comps->filter(fn ($c) => DB::table('assessment_component_outcomes')->where('assessment_component_id', $c->id)->where('learning_outcome_id', $lo->id)->exists());

            return [
                'code' => $lo->code,
                'text' => $lo->text_en,
                'text_ms' => $lo->text_ms,
                'assessment_method' => $mapped->pluck('name_en')->implode(', ') ?: 'Course assessments',
                'share' => $n > 0 && $mapped->isNotEmpty() ? round($people->filter(fn ($e) => $passed($e->id, $mapped))->count() / $n * 100, 1) : null,
            ];
        })->all();

        $modules = DB::table('mc_modules')->where('mc_version_id', $o->mc_version_id)->orderBy('seq')->get()->map(fn ($m) => ['name' => $m->name_en, 'share' => $n > 0 ? $pct(DB::table('module_achievements')->where('mc_module_id', $m->id)->whereIn('enrolment_id', $people->pluck('id'))->count()) : null])->all();

        $fb = DB::table('feedback_responses')->where('offering_id', $offeringId)->get();
        $feedback = [
            'count' => $fb->count(),
            'areas' => collect(config('monitoring.feedback_areas'))->map(fn ($label, $k) => $fb->isEmpty() ? null : round($fb->avg(fn ($r) => (float) (json_decode($r->answers, true)[$k] ?? 0)), 1))->all(),
            'detail' => collect(config('monitoring.feedback_groups'))->flatMap(fn ($g) => $g)->map(function ($label, $k) use ($fb) {
                $v = $fb->map(fn ($r) => json_decode($r->answers, true)[$k] ?? null)->filter(fn ($x) => $x !== null);
                return $v->isEmpty() ? null : round($v->avg(), 1);
            })->all(),
            'comments' => $fb->pluck('comment')->filter()->values()->all(),
        ];

        return [
            'participants' => $n,
            'rows' => $rows,
            'average' => $marks->isEmpty() ? null : round($marks->avg(), 1),
            'median' => $median,
            'outcomes' => $outcomes,
            'modules' => $modules,
            'feedback' => $feedback,
            'access_issues' => [
                'count' => $accessIssuesCount,
                'status' => $accessIssuesStatus,
                'notes' => $accessIssuesNotes,
            ],
        ];
    }
}
