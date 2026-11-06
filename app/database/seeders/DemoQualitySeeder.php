<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

// FL-14: Cloud Native Development has a finished cohort and a Quality Verification that ends in 41 days; Lean Six Sigma Yellow Belt has no completed cohort.
// Run on request: php artisan db:seed --class=DemoQualitySeeder
class DemoQualitySeeder extends DemoOfferingsSeeder
{
    public function run(): void
    {
        parent::run();
        $v = DB::table('mc_versions')->where('name_en', 'Cloud Native Development')->first();
        if (! $v || DB::table('qv_records')->where('micro_credential_id', $v->micro_credential_id)->exists()) {
            return;
        }
        $mc = $v->micro_credential_id;
        $aina = User::where('name', 'Dr. Aina Zulkifli')->firstOrFail();
        $amir = User::where('name', 'Amir Hakimi')->firstOrFail();
        DB::table('mc_versions')->where('id', $v->id)->update(['proposed_credit' => 2, 'proposed_mqf_level' => 4, 'qv_status' => 'verified', 'qv_valid_until' => today()->addDays(41)]);
        DB::table('mc_versions')->where('name_en', 'Lean Six Sigma Yellow Belt')->update(['proposed_credit' => 1.5, 'proposed_mqf_level' => 3]);
        $o = DB::table('offerings')->insertGetId([
            'mc_version_id' => $v->id, 'cohort_name' => '2026/1', 'start_date' => today()->subDays(150), 'end_date' => today()->subDays(60), 'delivery_mode' => 'online', 'capacity' => 30,
            'reg_opens_on' => today()->subDays(180), 'reg_closes_on' => today()->subDays(155), 'fee' => 150, 'status' => 'ended', 'readiness_state' => 'ready', 'created_by' => $aina->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $e = DB::table('enrolments')->insertGetId(['reference' => 'E-QV-0001', 'user_id' => $amir->id, 'offering_id' => $o, 'mc_version_id' => $v->id, 'status' => 'completed', 'fee_amount' => 150, 'ack_text' => 'x', 'ack_versions' => '{}', 'status_changed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('achievement_records')->insert(['enrolment_id' => $e, 'mc_version_id' => $v->id, 'completion_status' => 'complete', 'completed_on' => today()->subDays(55), 'final_result' => '{}', 'confirmed_by' => $aina->id, 'confirmed_at' => now(), 'created_at' => now()]);
        DB::table('qv_records')->insert(['micro_credential_id' => $mc, 'cycle_no' => 1, 'status' => 'verified', 'submitted_on' => today()->subYears(3)->addDays(20), 'submitted_ref' => 'MQA/QV/2023/051', 'qv_reference' => 'QV/2023/051', 'verified_credit' => 2, 'verified_mqf_level' => 4,
            'valid_from' => today()->subYears(3)->addDays(41), 'valid_until' => today()->addDays(41), 'decision_document' => 'qv-2023-051.pdf', 'created_at' => now(), 'updated_at' => now()]);
    }
}
