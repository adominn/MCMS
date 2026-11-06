<?php

declare(strict_types=1);

namespace App\Modules\Curriculum;

use Illuminate\Support\Facades\DB;

// Everything a reader (reviewer, deputy dean, decision pack) needs to see of one version.
final class ProposalSummary
{
    public static function load(int $id): array
    {
        $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->join('users as u', 'u.id', '=', 'm.owner_id')->join('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->where('v.id', $id)->first(['v.*', 'm.category', 'm.category_status', 'm.qv_planned', 'm.ptj_id', 'm.owner_id', 'm.origin', 'u.name as owner_name', 'p.code as ptj_code']) ?? abort(404);
        $comps = DB::table('assessment_components')->where('mc_version_id', $id)->orderBy('id')->get();

        return [
            'v' => $v,
            'modules' => DB::table('mc_modules')->where('mc_version_id', $id)->orderBy('seq')->get(),
            'los' => DB::table('learning_outcomes')->where('mc_version_id', $id)->orderBy('sort_order')->get(),
            'comps' => $comps,
            'links' => DB::table('assessment_component_outcomes')->whereIn('assessment_component_id', $comps->pluck('id'))->get()->groupBy('assessment_component_id'),
            'ready' => DB::table('proposal_readiness')->where('mc_version_id', $id)->get()->keyBy('item'),
            'course' => $v->parent_course_id ? DB::table('parent_courses as c')->leftJoin('programmes as p', 'p.id', '=', 'c.programme_id')->where('c.id', $v->parent_course_id)->first(['c.*', 'p.name as programme']) : null,
            'cfg' => config('proposals'),
        ];
    }
}
