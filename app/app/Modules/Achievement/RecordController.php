<?php

declare(strict_types=1);

namespace App\Modules\Achievement;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ST-06 achievement record (FR-135) and the record as the lecturer and deputy dean see it (FR-136). Always an internal record, not an ACR (FR-130).
final class RecordController
{
    public function mine(Request $request)
    {
        $ids = DB::table('enrolments')->where('user_id', $request->user()->id)->whereIn('status', ['enrolled', 'completed', 'not_completed'])->orderByDesc('id')->pluck('id');

        return view('records.mine', ['records' => $ids->map(fn ($id) => $this->data($id))]);
    }

    public function show(Request $request, int $enrolment)
    {
        $e = DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('e.id', $enrolment)->first(['e.user_id', 'm.owner_id', 'm.ptj_id']) ?? abort(404);
        $u = $request->user();
        $ptjs = $u->hasAnyRole('records_custodian', 'abc_depositor', 'records_depositor')
            ? ($u->scopedPtjIds('records_depositor') ?? $u->scopedPtjIds('records_custodian') ?? $u->scopedPtjIds('abc_depositor'))
            : ($u->hasAnyRole('deputy_dean') ? $u->scopedPtjIds('deputy_dean') : []);
        abort_unless($e->user_id === $u->id || $e->owner_id === $u->id || $ptjs === null || in_array($e->ptj_id, $ptjs, true), 403, 'You cannot see this record.');

        return view('records.show', ['d' => $this->data($enrolment)]);
    }

    private function data(int $enrolmentId): array
    {
        $rec = Completion::record($enrolmentId);
        $e = DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('users as u', 'u.id', '=', 'e.user_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->leftJoin('users as lu', 'lu.id', '=', 'm.owner_id')->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')->where('e.id', $enrolmentId)
            ->first(['e.*', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.delivery_mode', 'v.name_en', 'v.name_ms', 'v.version_no', 'v.proposed_credit', 'v.badge_image_path', 'm.category', 'p.name as ptj_name', 'p.code as ptj_code', 'u.name as person', 'u.institution_id', 'lu.name as lecturer_name']);
        $achieved = DB::table('module_achievements as a')->join('digital_badges as b', 'b.module_achievement_id', '=', 'a.id')->where('a.enrolment_id', $enrolmentId)->get(['a.mc_module_id', 'a.achieved_at', 'b.code', 'b.external_id'])->keyBy('mc_module_id');
        $acr = DB::table('acrs')->where('achievement_record_id', $rec->id)->first(['id', 'reference', 'status', 'confirmed_at', 'credits']);

        return [
            'credentials' => DB::table('credentials')->where('enrolment_id', $enrolmentId)->orderBy('id')->get(['id', 'number', 'status', 'issued_at', 'public_code']), 'e' => $e, 'rec' => $rec, 'acr' => $acr, 'state' => Completion::status($enrolmentId), 'check' => $rec->completion_status ? null : Completion::check($enrolmentId), 'final' => $rec->final_result ? json_decode($rec->final_result, true) : null,
            'modules' => DB::table('mc_modules')->where('mc_version_id', $e->mc_version_id)->orderBy('seq')->get()->map(fn ($m) => (object) ['id' => $m->id, 'seq' => $m->seq, 'name' => $m->name_en, 'name_ms' => $m->name_ms, 'slt_hours' => $m->slt_hours, 'achieved' => $achieved[$m->id] ?? null]),
            'results' => DB::table('assessment_results as r')->join('assessment_components as c', 'c.id', '=', 'r.assessment_component_id')->where('r.enrolment_id', $enrolmentId)->where('r.status', 'verified')->orderBy('c.id')->get(['c.name_en', 'c.max_mark', 'c.pass_mark', 'r.score']),
        ];
    }
}
