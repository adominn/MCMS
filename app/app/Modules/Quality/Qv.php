<?php

declare(strict_types=1);

namespace App\Modules\Quality;

use Illuminate\Support\Facades\DB;

// MD-10 rules: the Ready-to-submit conditions (FR-164), the automatic evidence links (FR-166), the open cycle.
final class Qv
{
    /** The cycle being worked on, or null. */
    public static function open(int $mc): ?object
    {
        return DB::table('qv_records')->where('micro_credential_id', $mc)->whereIn('status', ['preparing', 'ready', 'submitted'])->orderByDesc('cycle_no')->first();
    }

    /** The record that stands today: the newest verified one that has not lapsed (FR-172). */
    public static function standing(int $mc): ?object
    {
        return DB::table('qv_records')->where('micro_credential_id', $mc)->where('status', 'verified')->where('valid_until', '>=', today())->orderByDesc('valid_until')->first();
    }

    public static function latestVersion(int $mc): ?object
    {
        return DB::table('mc_versions')->where('micro_credential_id', $mc)->whereIn('status', ['approved', 'superseded'])->orderByDesc('version_no')->first();
    }

    /** @return list<array{ok: bool, text: string}> the basic conditions of Appendix D2 */
    public static function conditions(int $mc, \App\Modules\Core\Settings $settings): array
    {
        $m = DB::table('micro_credentials')->find($mc);
        $v = self::latestVersion($mc);
        $items = [];
        $add = function (bool $ok, string $t) use (&$items) { $items[] = ['ok' => $ok, 'text' => $t]; };

        $add($m->category === 'samc' && $m->category_status === 'confirmed', $m->category === 'samc' && $m->category_status === 'confirmed' ? 'A confirmed SAMC' : 'Not a confirmed SAMC');
        $add($m->origin === 'own', $m->origin === 'own' ? 'A stand-alone offering' : 'Not a stand-alone offering: its modules come from a credit course');
        $min = (int) $settings->get('slt.min_hours_samc');
        $slt = $v ? (int) DB::table('mc_modules')->where('mc_version_id', $v->id)->sum('slt_hours') : 0;
        $add($slt >= $min, "Learning time $slt hours (at least $min needed)");
        $reg = (int) $settings->get('qv.provider_registered') === 1;
        $add($reg, $reg ? 'The provider is registered and authorised' : 'The provider is not recorded as registered and authorised');
        $need = (int) $settings->get('qv.min_completed_cohorts');
        $have = self::completedCohorts($mc);
        $add($have >= $need, "Completed cohorts with verified evidence: $have (at least $need needed)");
        $just = $v && $v->proposed_credit !== null && $v->proposed_mqf_level !== null;
        $add($just, $just ? 'The proposed credit and MQF level are stated and marked as proposed until verified' : 'The proposed credit and MQF level are not both stated');

        return $items;
    }

    /** A cohort that ended, with at least one participant whose completion was confirmed on verified results (BR-03). */
    public static function completedCohorts(int $mc): int
    {
        return DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('v.micro_credential_id', $mc)->where('o.end_date', '<', today())
            ->whereIn('o.status', ['ended', 'running'])->whereExists(fn ($q) => $q->select(DB::raw(1))->from('enrolments as e')->join('achievement_records as a', 'a.enrolment_id', '=', 'e.id')->whereColumn('e.offering_id', 'o.id')->where('a.completion_status', 'complete'))->count();
    }

    /** Evidence that MCMS holds itself is linked, not typed (FR-166). @return array<string, string> item => what was linked */
    public static function links(int $mc): array
    {
        $v = self::latestVersion($mc);
        if (! $v) {
            return [];
        }
        $out = [];
        if (DB::table('learning_outcomes')->where('mc_version_id', $v->id)->exists() && DB::table('mc_modules')->where('mc_version_id', $v->id)->exists()) {
            $out['academic_design'] = "Learning outcomes, module structure and SLT calculation of version {$v->version_no}";
        }
        $cohort = DB::table('offerings as o')->join('mc_versions as x', 'x.id', '=', 'o.mc_version_id')->where('x.micro_credential_id', $mc)->whereExists(fn ($q) => $q->select(DB::raw(1))->from('enrolments as e')->join('assessment_results as r', 'r.enrolment_id', '=', 'e.id')->whereColumn('e.offering_id', 'o.id')->where('r.status', 'verified'))->orderByDesc('o.id')->value('o.cohort_name');
        if ($cohort && DB::table('assessment_components')->where('mc_version_id', $v->id)->exists()) {
            $out['assessment_achievement'] = "Assessment plan of version {$v->version_no} and the verified results of cohort $cohort";
        }
        if (json_decode($v->credential_types ?? '[]', true) && DB::table('credentials as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('mc_versions as x', 'x.id', '=', 'o.mc_version_id')->where('x.micro_credential_id', $mc)->exists()) {
            $out['credential'] = 'Credential design of the proposal and the credentials issued, with their signed verification';
        }

        return $out;
    }

    public static function syncLinks(int $recordId, int $mc): void
    {
        $links = self::links($mc);
        foreach (array_keys(config('qv.checklist')) as $item) {
            $row = DB::table('qv_evidence')->where('qv_record_id', $recordId)->where('item', $item)->first();
            if (isset($links[$item])) {
                DB::table('qv_evidence')->updateOrInsert(['qv_record_id' => $recordId, 'item' => $item], ['status' => 'available', 'file_ref' => $links[$item], 'linked' => true]);
            } elseif (! $row) {
                DB::table('qv_evidence')->insert(['qv_record_id' => $recordId, 'item' => $item]);
            } elseif ($row->linked) {
                DB::table('qv_evidence')->where('id', $row->id)->update(['linked' => false, 'status' => 'not_yet', 'file_ref' => null]); // the link no longer holds
            }
        }
    }
}
