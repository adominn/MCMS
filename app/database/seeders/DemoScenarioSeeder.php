<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

// PRD 7.2 and FL-19: the starting data of the demonstration scenario. Data Structures is NOT seeded: Act 1 creates it through the screens.
// What Act 4 needs is here: the SAMC "Cloud Native Development" with a finished cohort and no Quality Verification yet, and the MCE "Leadership Essentials"
// with a running cohort whose results are verified. A second lecturer (Dr. Lim) is there to review. Run on request: php artisan db:seed --class=DemoScenarioSeeder
class DemoScenarioSeeder extends DemoProposalsSeeder
{
    public function run(): void
    {
        $ftmk = DB::table('ptjs')->where('code', 'FTMK')->value('id');
        $aina = User::where('name', 'Dr. Aina Zulkifli')->firstOrFail();
        $hakim = User::where('name', 'Hakim Rahman')->firstOrFail();
        $amir = User::where('name', 'Amir Hakimi')->firstOrFail();
        $lim = User::firstOrCreate(['email' => 'lim.weijie@utem.example.test'], ['name' => 'Dr. Lim Wei Jie', 'upn' => 'lim.weijie@utem.example.test']);
        $lim->roleAssignments()->firstOrCreate(['role' => 'lecturer'], ['scope_type' => 'ptj', 'ptj_id' => $ftmk, 'reason' => 'Seed data']);

        // SAMC: 80 hours, finished cohort 2026/1, Amir completed, no Quality Verification yet.
        $v = $this->approved($aina, $ftmk, 'Cloud Native Development', 'samc', 80);
        DB::table('mc_versions')->where('id', $v)->update(['proposed_credit' => 2, 'proposed_mqf_level' => 4, 'qv_status' => 'will_apply']);
        $this->cohort($v, '2026/1', today()->subDays(150), today()->subDays(60), 'ended', $aina, $hakim, $amir, true);

        // MCE: 16 hours, running cohort 2026/2, Amir enrolled with a verified result, waiting for the deputy dean's confirmation.
        $m = $this->approved($aina, $ftmk, 'Leadership Essentials', 'mce', 16);
        $this->cohort($m, '2026/2', today()->subDays(10), today()->addDays(20), 'running', $aina, $hakim, $amir, false);
    }

    private function approved(User $owner, int $ptj, string $title, string $category, int $hours): int
    {
        $v = $this->proposal($owner, $ptj, $title, 'approved', now()->subDays(30));
        $mc = DB::table('mc_versions')->where('id', $v)->value('micro_credential_id');
        DB::table('micro_credentials')->where('id', $mc)->update(['category' => $category, 'category_status' => 'confirmed']);
        DB::table('mc_versions')->where('id', $v)->update(['effective_date' => today()->subDays(200), 'planned_slt' => $hours]);
        DB::table('mc_modules')->where('mc_version_id', $v)->update(['slt_hours' => $hours]);
        // The assessment component belongs to the module, so that the module can be achieved.
        DB::table('assessment_components')->where('mc_version_id', $v)->update(['mc_module_id' => DB::table('mc_modules')->where('mc_version_id', $v)->value('id'), 'max_mark' => 100, 'pass_mark' => 50, 'csv_key' => 'project_mark']);
        $hakim = DB::table('users')->where('name', 'Hakim Rahman')->value('id');
        foreach (['endorsement' => ['endorsed', null], 'senate' => ['approved', 'Senate 2/2026']] as $stage => [$decision, $ref]) {
            DB::table('approval_decisions')->insert(['mc_version_id' => $v, 'stage' => $stage, 'decision' => $decision, 'decided_by' => $hakim, 'decided_role' => 'deputy_dean', 'meeting_ref' => $ref, 'meeting_date' => $ref ? today()->subMonths(6) : null, 'created_at' => now()]);
        }

        return $v;
    }

    private function cohort(int $version, string $name, $start, $end, string $status, User $aina, User $hakim, User $amir, bool $completed): void
    {
        $o = DB::table('offerings')->insertGetId(['mc_version_id' => $version, 'cohort_name' => $name, 'start_date' => $start, 'end_date' => $end, 'delivery_mode' => 'online', 'capacity' => 30, 'reg_opens_on' => $start->copy()->subDays(40), 'reg_closes_on' => $start->copy()->subDays(10),
            'fee' => 0, 'status' => $status, 'readiness_state' => 'ready', 'created_by' => $aina->id, 'created_at' => now(), 'updated_at' => now()]);
        $e = DB::table('enrolments')->insertGetId(['reference' => 'E-SC-'.$o, 'user_id' => $amir->id, 'offering_id' => $o, 'mc_version_id' => $version, 'status' => $completed ? 'completed' : 'enrolled', 'fee_amount' => 0, 'ack_text' => 'x', 'ack_versions' => '{}', 'status_changed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $module = DB::table('mc_modules')->where('mc_version_id', $version)->value('id');
        $component = DB::table('assessment_components')->where('mc_version_id', $version)->value('id');
        DB::table('module_enrolments')->insert(['enrolment_id' => $e, 'mc_module_id' => $module, 'enrolled_at' => now()]);
        DB::table('assessment_results')->insert(['enrolment_id' => $e, 'assessment_component_id' => $component, 'score' => 78, 'status' => 'verified', 'source' => 'manual', 'verified_by' => $hakim->id, 'verified_at' => now(), 'verification_evidence' => 'Seed data', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('module_achievements')->insert(['enrolment_id' => $e, 'mc_module_id' => $module, 'verified_by' => $hakim->id, 'achieved_at' => now()]);
        if ($completed) {
            DB::table('achievement_records')->insert(['enrolment_id' => $e, 'mc_version_id' => $version, 'completion_status' => 'complete', 'completed_on' => $end, 'final_result' => '{}', 'confirmed_by' => $hakim->id, 'confirmed_at' => now(), 'created_at' => now()]);
        }
    }
}
