<?php

declare(strict_types=1);

namespace App\Modules\Curriculum;

use App\Modules\Core\Settings;
use Illuminate\Support\Facades\DB;

// FR-48 and the category rules of SRS Section 4: blocking errors stop submission, warnings need a written response.
final class ProposalChecker
{
    public function __construct(private Settings $settings) {}

    /** @return array{blocking: list<array{fr:string,tab:string,msg:string}>, warnings: list<array{fr:string,tab:string,msg:string}>, slt:int} */
    public function check(int $versionId): array
    {
        $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $versionId)->first(['v.*', 'm.category', 'm.qv_planned']);
        $modules = DB::table('mc_modules')->where('mc_version_id', $versionId)->orderBy('seq')->get();
        $los = DB::table('learning_outcomes')->where('mc_version_id', $versionId)->orderBy('sort_order')->get();
        $comps = DB::table('assessment_components')->where('mc_version_id', $versionId)->get();
        $links = DB::table('assessment_component_outcomes')->whereIn('assessment_component_id', $comps->pluck('id'))->get();
        $total = (int) $modules->sum('slt_hours');
        $b = [];
        $w = [];
        $block = function (string $fr, string $tab, string $msg) use (&$b) { $b[] = compact('fr', 'tab', 'msg'); };
        $warn = function (string $fr, string $tab, string $msg) use (&$w) { $w[] = compact('fr', 'tab', 'msg'); };

        if (! $v->name_en || ! $v->name_ms) {
            $block('FR-35', 'basics', __('Enter the title in both English and Bahasa Melayu.'));
        }
        if (! $v->delivery_mode || ! $v->delivery_language) {
            $block('FR-35', 'basics', __('Choose the delivery mode and enter the delivery language.'));
        }
        if (! $v->need) {
            $block('FR-36', 'justification', __('Describe the need or problem this micro-credential addresses.'));
        }
        if ($modules->isEmpty()) {
            $block('FR-52', 'modules', __('Add at least one module.'));
        }
        if ($los->isEmpty()) {
            $block('FR-37', 'outcomes', __('Add at least one learning outcome.'));
        }
        if ($comps->isEmpty()) {
            $block('FR-39', 'assessment', __('Add at least one assessment component.'));
        } elseif (abs($comps->sum('weight_percent') - 100) > 0.001) {
            $block('FR-39', 'assessment', __('Assessment weightings total :total%; they must total 100%.', ['total' => rtrim(rtrim(number_format((float) $comps->sum('weight_percent'), 1), '0'), '.')]));
        }
        if ($comps->contains(fn ($c) => (float) $c->pass_mark > (float) $c->max_mark)) {
            $block('FR-39', 'assessment', __('A pass mark is above its maximum mark.'));
        }
        if (! $v->ip_confirmed) {
            $block('FR-47', 'submit', __('Confirm that third-party materials respect copyright and intellectual property rules.'));
        }

        $min = (int) $this->settings->get('slt.min_hours_samc');
        match ($v->category) {
            'umc' => $this->umc($v, $modules, $total, $block),
            'samc' => $total < $min ? $block('FR-30', 'modules', __('A SAMC needs at least :min hours of learning time; the modules total :total hours.', ['min' => $min, 'total' => $total])) : null,
            'mce' => $this->mce($v, $total, $min, $block),
        };

        foreach ($los as $lo) {
            if (! $links->contains('learning_outcome_id', $lo->id)) {
                $warn('FR-40', 'assessment', __('Outcome :code has no assessment.', ['code' => $lo->code]));
            }
        }
        foreach ($comps as $c) {
            if (! $links->contains('assessment_component_id', $c->id)) {
                $warn('FR-40', 'assessment', __('Assessment component ":name" is not linked to any outcome.', ['name' => $c->name_en ?: 'untitled']));
            }
        }
        if ($v->category === 'samc' && $v->credit_sought && ! $v->qv_planned) {
            $warn('FR-30', 'credential', __('You seek credit but Quality Verification is not planned.'));
        }
        $notYet = DB::table('proposal_readiness')->where('mc_version_id', $versionId)->where('status', 'not_yet')->count();
        $unset = count(config('proposals.readiness')) - DB::table('proposal_readiness')->where('mc_version_id', $versionId)->count();
        if ($notYet + $unset > 0) {
            $warn('FR-43', 'readiness', __(':count readiness items are not yet ready.', ['count' => $notYet + $unset]));
        }

        return ['blocking' => $b, 'warnings' => $w, 'slt' => $total];
    }

    private function umc(object $v, $modules, int $total, \Closure $block): void
    {
        $course = $v->parent_course_id ? DB::table('parent_courses as c')->leftJoin('programmes as p', 'p.id', '=', 'c.programme_id')->where('c.id', $v->parent_course_id)->first(['c.*', 'p.accreditation_status']) : null;
        if (! $course) {
            $block('FR-50', 'modules', __('Choose the parent course from the reference data.'));
        } elseif ($course->accreditation_status !== 'accredited') {
            $block('FR-51', 'modules', __('The parent programme is not recorded as accredited.'));
        }
        if (! $v->credit_model) {
            $block('FR-52', 'modules', __('Choose one unbundling model.'));
        }
        if ($course && $modules->isNotEmpty() && $total !== (int) $course->slt_hours) {
            $block('FR-53', 'modules', __('The modules total :total hours; they must equal the parent course (:parent hours).', ['total' => $total, 'parent' => $course->slt_hours]));
        }
        $whole = (int) $this->settings->get('slt.min_hours_umc_whole');
        if ($modules->isNotEmpty() && $total < $whole) {
            $block('FR-54', 'modules', __('The whole UMC needs at least :min hours; the modules total :total hours.', ['min' => $whole, 'total' => $total]));
        }
        if ($v->credit_model === 'credit_based') {
            $per = (int) $this->settings->get('slt.min_hours_module_credit_model');
            foreach ($modules->where('slt_hours', '<', $per) as $m) {
                $block('FR-55', 'modules', __('Module :seq has :hours hours; the credit-based model needs at least :min.', ['seq' => $m->seq, 'hours' => $m->slt_hours, 'min' => $per]));
            }
        }
        if ($v->credit_model === 'topic_based') {
            if ($modules->isNotEmpty() && $modules->count() < 2) {
                $block('FR-52', 'modules', __('The unbundled structure requires at least 2 modules.'));
            }
        }
        if ($v->credit_model === 'separate_assessment') {
            $assessCount = $modules->where('is_assessment', true)->count();
            $learnCount = $modules->where('is_assessment', false)->count();

            if ($assessCount === 0) {
                $block('FR-56', 'modules', __('The separate learning and assessment model needs at least one assessment module.'));
            } elseif ($learnCount === 0) {
                $block('FR-56', 'modules', __('The separate learning and assessment model requires at least one learning module and one dedicated assessment module.'));
            } elseif ($assessCount > 1) {
                $block('FR-56', 'modules', __('The separate learning and assessment model requires exactly one integrated assessment module.'));
            }

            foreach ($modules->where('is_assessment', true) as $am) {
                if ($am->slt_hours <= 0) {
                    $block('FR-56', 'modules', __('The assessment module must have allocated learning time (SLT).'));
                }
            }
        }
    }

    private function mce(object $v, int $total, int $min, \Closure $block): void
    {
        if ($total >= $min) {
            $block('FR-29', 'modules', __('An MCE is below :min hours; the modules total :total hours. Reclassify it as a SAMC.', ['min' => $min, 'total' => $total]));
        }
        if ($v->proposed_credit !== null || $v->proposed_mqf_level !== null) {
            $block('FR-29', 'credential', __('An MCE carries no credit or MQF level. Clear the proposed credit and MQF level.'));
        }
    }
}
