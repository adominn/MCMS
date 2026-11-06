<?php

declare(strict_types=1);

namespace App\Modules\Transfer;

use Illuminate\Support\Facades\DB;

// Shared reads and rules of MD-12.
final class Transfers
{
    /** The lines of an application with what the evaluator has recorded so far. */
    public static function lines(int $applicationId)
    {
        return DB::table('transfer_lines as l')->join('parent_courses as c', 'c.id', '=', 'l.parent_course_id')->join('acrs as a', 'a.id', '=', 'l.acr_id')->join('abc_deposits as d', 'd.id', '=', 'l.abc_deposit_id')->join('mc_versions as v', 'v.id', '=', 'l.mc_version_id')
            ->where('l.transfer_application_id', $applicationId)->orderBy('l.id')->get(['l.*', 'c.code as course_code', 'c.name as course_name', 'c.clos', 'c.credit as course_credit', 'a.reference as acr_reference', 'd.credit_status', 'd.credit_expiry', 'v.name_en as source_name'])
            ->map(function ($l) {
                $l->criteria = DB::table('equivalence_criteria')->where('transfer_line_id', $l->id)->get()->keyBy('criterion');
                $l->assessments = DB::table('equivalence_assessments')->where('transfer_line_id', $l->id)->get()->keyBy('target_outcome');
                $l->targets = array_values(array_filter(array_map('trim', explode(';', (string) $l->clos))));
                $l->outcomes = DB::table('learning_outcomes')->where('mc_version_id', $l->mc_version_id)->orderBy('sort_order')->get();
                $l->lo = self::outcomeResult($l);
                $l->complete = count($l->criteria) === count(config('transfer.criteria')) && count($l->assessments) === count($l->targets) && $l->targets !== [];

                return $l;
            });
    }

    /** The learning-outcome criterion is derived from the outcome-by-outcome comparison, never typed (FR-ctloequiv, BR-30). */
    public static function outcomeResult(object $l): ?string
    {
        if ($l->targets === [] || count($l->assessments) < count($l->targets)) {
            return null;
        }
        $m = $l->assessments->pluck('match');

        return $m->contains('none') ? 'not_equivalent' : ($m->contains('partial') ? 'partly' : 'equivalent');
    }

    /** Limit and non-transferable warnings for the decision-maker (FR-ctlimits). @return list<string> */
    public static function warnings(object $app, $lines, array $approved): array
    {
        $p = DB::table('programmes')->find($app->programme_id);
        $w = [];
        $earlier = (float) DB::table('transfer_lines as l')->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')->where('t.user_id', $app->user_id)->where('t.programme_id', $app->programme_id)->where('t.id', '!=', $app->id)->sum('l.credits_approved');
        $total = $earlier + array_sum($approved);
        $limit = $p->total_credits * $p->transfer_limit_percent / 100;
        if ($total > $limit) {
            $w[] = "Transfer limit: $total credits would be credited, above the limit of $limit ({$p->transfer_limit_percent}% of {$p->total_credits}).";
        }
        $blocked = array_map('trim', explode(',', (string) $p->non_transferable));
        foreach ($lines as $l) {
            if (in_array($l->course_code, $blocked, true) && ($approved[$l->id] ?? 0) > 0) {
                $w[] = "{$l->course_code} is listed as non-transferable for this programme.";
            }
            if ($l->reevaluate) {
                $w[] = "The credit for {$l->course_code} has expired or the curriculum changed: judge the currency of knowledge again.";
            }
            if ($l->target_version !== $p->curriculum_version) {
                $w[] = "{$l->course_code} was evaluated against curriculum version {$l->target_version}; the programme is now version {$p->curriculum_version}.";
            }
        }

        return $w;
    }

    /** EV-48 and EV-14: an application that rests on credit that expired, or on a version that changed, is flagged for review before it is reused. */
    public static function flagDeposit(int $depositId): void
    {
        DB::table('transfer_lines as l')->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')->where('l.abc_deposit_id', $depositId)->whereIn('t.status', TransferController::ACTIVE)->update(['reevaluate' => true]);
    }
}
