<?php

declare(strict_types=1);

namespace App\Modules\Assessment;

use App\Models\User;
use App\Modules\Learning\Template;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;

// MD-07: verification of results and the module achievement it can create. Verifying does not complete the micro-credential (D-15).
final class Verifier
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier) {}

    /**
     * Verifies the provisional results of a cohort for the chosen scopes ('mc' for components of the whole micro-credential, 'm{id}' for a module).
     * Held results are left out. Returns the counts.
     */
    public function verify(object $o, User $by, string $evidence, array $scopes): array
    {
        $comps = Template::components($o->mc_version_id);
        $inScope = $comps->filter(fn ($c) => ! $scopes || in_array($c->mc_module_id ? 'm'.$c->mc_module_id : 'mc', $scopes, true))->pluck('id');
        $q = DB::table('assessment_results as r')->join('enrolments as e', 'e.id', '=', 'r.enrolment_id')->where('e.offering_id', $o->id)->whereIn('r.assessment_component_id', $inScope);
        abort_if((clone $q)->where('r.status', 'provisional')->where('r.entered_by', $by->id)->exists(), 403, 'You entered some of these results yourself, so you cannot verify them (separation of duties).');
        $ids = (clone $q)->where('r.status', 'provisional')->whereNotNull('r.score')->pluck('r.id');
        $held = (clone $q)->where('r.status', 'held')->count();

        $badges = 0;
        DB::transaction(function () use ($ids, $by, $evidence, $o, &$badges) {
            DB::table('assessment_results')->whereIn('id', $ids)->update(['status' => 'verified', 'verified_by' => $by->id, 'verified_at' => now(), 'verification_evidence' => $evidence, 'updated_at' => now()]);
            foreach (DB::table('enrolments')->where('offering_id', $o->id)->pluck('id') as $eid) {
                $badges += $this->achievements($eid, $o->mc_version_id, $by->id);
            }
            \App\Modules\Achievement\Completion::syncTask($o->id);
            $this->audit->record('results.verified', ['type' => 'Offering', 'id' => $o->id], after: ['results' => $ids->count(), 'badges' => $badges], reason: $evidence); // EV-29
        });

        return ['verified' => $ids->count(), 'held' => $held, 'badges' => $badges];
    }

    /** FR-126: a module is achieved when every assessment of the module is verified and met. Returns the new badges. */
    public function achievements(int $enrolmentId, int $versionId, int $verifiedBy): int
    {
        $new = 0;
        foreach (DB::table('mc_modules')->where('mc_version_id', $versionId)->orderBy('seq')->get() as $m) {
            $comps = DB::table('assessment_components')->where('mc_version_id', $versionId)->where('mc_module_id', $m->id)->get();
            if ($comps->isEmpty() || DB::table('module_achievements')->where('enrolment_id', $enrolmentId)->where('mc_module_id', $m->id)->exists()) {
                continue;
            }
            $results = DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->whereIn('assessment_component_id', $comps->pluck('id'))->get()->keyBy('assessment_component_id');
            $met = $comps->every(fn ($c) => isset($results[$c->id]) && $results[$c->id]->status === 'verified' && (float) $results[$c->id]->score >= (float) $c->pass_mark);
            if ($met) {
                $aid = DB::table('module_achievements')->insertGetId(['enrolment_id' => $enrolmentId, 'mc_module_id' => $m->id, 'verified_by' => $verifiedBy, 'achieved_at' => now()]);
                $bid = DB::table('digital_badges')->insertGetId(['module_achievement_id' => $aid, 'code' => 'TMP'.$aid, 'issued_at' => now()]);
                DB::table('digital_badges')->where('id', $bid)->update(['code' => 'B-'.now()->year.'-'.str_pad((string) $bid, 4, '0', STR_PAD_LEFT)]);
                $new++;
            }
        }

        return $new;
    }
}
