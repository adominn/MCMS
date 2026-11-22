<?php

declare(strict_types=1);

namespace App\Modules\Pathways;

use App\Modules\Core\Settings;
use Illuminate\Support\Facades\DB;

// MD-13 rules for an individual stackable plan: the home-credit share, residency and credit-bearing checks (FR-sp30, FR-spresidency, BR-05, BR-06, BR-35).
final class Plans
{
    public function __construct(private Settings $settings) {}

    /** The numbers behind a plan, from the chosen mapped credentials. */
    public function figures(object $pathway, array $itemIds, float $other): array
    {
        $programme = DB::table('programmes')->find($pathway->programme_id);
        $items = DB::table('pathway_items')->where('pathway_id', $pathway->id)->whereIn('id', $itemIds)->get();
        $transfer = round((float) $items->sum('credits') + $other, 1);
        $total = (float) $programme->total_credits;

        return ['items' => $items, 'transfer' => $transfer, 'total' => $total, 'percent' => $total > 0 ? round(max(0, $total - $transfer) / $total * 100, 1) : 0.0, 'programme' => $programme];
    }

    /** Appendix G6 to G8 checks, recorded with the plan. @return list<array{key: string, met: bool, detail: string}> */
    public function checks(object $plan): array
    {
        $min = (int) $this->settings->get('stackable.min_home_credit_percent');
        $res = (int) $this->settings->get('stackable.residency_semesters');
        $programme = DB::table('programmes')->find($plan->programme_id);
        $items = DB::table('pathway_items as i')->join('micro_credentials as m', 'm.id', '=', 'i.micro_credential_id')->whereIn('i.id', json_decode($plan->item_ids, true))->get(['i.*', 'm.category']);
        $c = [];
        $pct = mcms_num($plan->utem_percent);
        $c[] = ['key' => 'home_credit', 'met' => (float) $plan->utem_percent >= $min, 'detail' => "$pct% of the {$programme->total_credits} graduating credits would be earned directly at UTeM; at least $min% is required. Meeting $min% does not mean the rest can be transferred automatically."];
        $odl = $plan->delivery_method === 'odl';
        $resMet = $programme->mode !== 'conventional' || ((int) $plan->residency_semesters >= $res && ! $odl);
        $c[] = ['key' => 'residency', 'met' => $resMet,
            'detail' => $programme->mode === 'conventional' ? "Residency planned: {$plan->residency_semesters} semester(s); at least $res required for a conventional programme.".($odl ? ' Conventional-to-ODL mode shift flagged for mandatory referral to Centre for Quality Assurance (ODL scope QA referral).' : '') : 'Not required for this programme mode.'];
        
        $samcValid = true;
        foreach ($items as $item) {
            if ($item->category === 'samc') {
                $hasQv = DB::table('qv_records')
                    ->where('micro_credential_id', $item->micro_credential_id)
                    ->where('status', 'verified')
                    ->where('valid_from', '<=', today())
                    ->where('valid_until', '>=', today())
                    ->exists();
                if (! $hasQv) {
                    $samcValid = false;
                    break;
                }
            }
        }
        $isCreditBearing = $items->every(fn ($i) => in_array($i->category, ['umc', 'samc'], true)) && $samcValid;
        $c[] = ['key' => 'credit_bearing', 'met' => $isCreditBearing, 'detail' => $isCreditBearing ? 'Every credential counted toward the degree is credit-bearing (UMC or active Quality-Verified SAMC).' : 'One or more credentials counted are not credit-bearing (MCE or SAMC without valid Quality Verification standing).'];
        
        $unmapped = $items->filter(fn ($i) => ! json_decode((string) $i->plo_map, true))->count();
        $c[] = ['key' => 'plo_mapping', 'met' => $unmapped === 0, 'detail' => $unmapped === 0 ? 'Every credential counted is mapped to programme learning outcomes.' : "$unmapped credential(s) are not mapped to any programme learning outcome."];
        
        $rem = json_decode((string) $plan->remaining, true) ?: [];
        $mandatory = collect($rem)->reject(fn ($r) => ($r['status'] ?? '') === 'na');
        $allDone = $mandatory->isNotEmpty() && $mandatory->every(fn ($r) => ($r['status'] ?? '') === 'done');
        $pending = $mandatory->filter(fn ($r) => ($r['status'] ?? '') !== 'done')->pluck('name')->all();
        $c[] = [
            'key' => 'remaining',
            'met' => $allDone,
            'detail' => $allDone
                ? 'All '.count($rem).' remaining programme requirements completed.'
                : ($rem === []
                    ? 'The remaining programme requirements are not listed.'
                    : count($pending).' mandatory remaining requirement(s) pending completion: '.implode(', ', $pending).'.'),
        ];

        return $c;
    }

    /** PLOs and Body of Knowledge domains of the pathway that none of the given mapped credentials covers (FR-spplo). @return list<string> */
    public function gaps(object $pathway, array $itemIds): array
    {
        $items = DB::table('pathway_items')->where('pathway_id', $pathway->id)->when($itemIds !== [], fn ($q) => $q->whereIn('id', $itemIds))->get();
        $plos = $items->flatMap(fn ($i) => collect(json_decode((string) $i->plo_map, true))->pluck('plo'))->all();
        $bok = $items->flatMap(fn ($i) => json_decode((string) $i->bok_map, true) ?: [])->all();
        $gaps = [];
        foreach (json_decode((string) $pathway->plos, true) ?: [] as $p) {
            in_array($p, $plos, true) || $gaps[] = "PLO $p";
        }
        foreach (json_decode((string) $pathway->bok, true) ?: [] as $d) {
            in_array($d, $bok, true) || $gaps[] = "domain $d";
        }

        return $gaps;
    }

    public function record(int $planId): void
    {
        $plan = DB::table('stackable_plans')->find($planId);
        DB::table('plan_requirements')->where('stackable_plan_id', $planId)->delete();
        foreach ($this->checks($plan) as $c) {
            DB::table('plan_requirements')->insert(['stackable_plan_id' => $planId, 'requirement_key' => $c['key'], 'met' => $c['met'], 'detail' => $c['detail'], 'checked_at' => now()]);
        }
    }

    /** EV-55: a changed target curriculum or micro-credential flags mappings for review. */
    public static function flag(array $pathwayIds, string $reason): void
    {
        if ($pathwayIds === []) {
            return;
        }
        DB::table('pathways')->whereIn('id', $pathwayIds)->whereIn('status', ['approved', 'published'])->update(['status' => 'review', 'review_reason' => $reason, 'updated_at' => now()]);
        DB::table('stackable_plans')->whereIn('pathway_id', $pathwayIds)->where('status', 'suitable')->update(['status' => 'needs_review', 'updated_at' => now()]); // Suitable returns to Needs review
    }

    public static function flagMicroCredential(int $mcId, string $reason): void
    {
        self::flag(DB::table('pathway_items')->where('micro_credential_id', $mcId)->pluck('pathway_id')->unique()->all(), $reason);
    }

    public static function flagCourse(string $code, string $reason): void
    {
        self::flag(DB::table('pathway_items as i')->join('parent_courses as c', 'c.id', '=', 'i.parent_course_id')->where('c.code', $code)->pluck('i.pathway_id')->unique()->all(), $reason);
    }
}
