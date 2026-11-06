<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

// A running cohort with four enrolled learners and a three-part assessment plan, for the results flows (FL-07, FL-08).
// Run on request: php artisan db:seed --class=DemoResultsSeeder
class DemoResultsSeeder extends DemoProposalsSeeder
{
    public function run(): void
    {
        if (DB::table('offerings')->where('cohort_name', '2026/2')->exists()) {
            return;
        }
        $ftmk = DB::table('ptjs')->where('code', 'FTMK')->value('id');
        $aina = User::where('name', 'Dr. Aina Zulkifli')->firstOrFail();
        $hakim = User::where('name', 'Hakim Rahman')->firstOrFail();

        $v = $this->proposal($aina, $ftmk, 'Data Structures', 'approved', now()->subDays(60));
        DB::table('mc_versions')->where('id', $v)->update(['effective_date' => today()->subDays(40)]);
        DB::table('micro_credentials')->where('id', DB::table('mc_versions')->where('id', $v)->value('micro_credential_id'))->update(['category_status' => 'confirmed']);
        DB::table('mc_modules')->where('mc_version_id', $v)->delete();
        $mods = [];
        foreach ([[1, 'Arrays and lists'], [2, 'Trees and graphs']] as [$seq, $name]) {
            $mods[$seq] = DB::table('mc_modules')->insertGetId(['mc_version_id' => $v, 'seq' => $seq, 'name_en' => $name, 'slt_hours' => 40]);
        }
        DB::table('assessment_component_outcomes')->delete();
        DB::table('assessment_components')->where('mc_version_id', $v)->delete();
        foreach ([['Module 1 test', 'module1_mark', $mods[1], 30], ['Module 2 test', 'module2_mark', $mods[2], 30], ['Project', 'project_mark', null, 40]] as [$n, $k, $m, $w]) {
            DB::table('assessment_components')->insert(['mc_version_id' => $v, 'mc_module_id' => $m, 'name_en' => $n, 'csv_key' => $k, 'weight_percent' => $w, 'max_mark' => 100, 'pass_mark' => 50]);
        }

        $o = DB::table('offerings')->insertGetId([
            'mc_version_id' => $v, 'cohort_name' => '2026/2', 'start_date' => today()->subDays(20), 'end_date' => today()->addDays(60), 'delivery_mode' => 'online', 'capacity' => 30,
            'reg_opens_on' => today()->subDays(50), 'reg_closes_on' => today()->subDays(25), 'fee' => 0, 'facilitators' => 'Dr. Aina Zulkifli', 'status' => 'running', 'readiness_state' => 'ready',
            'created_by' => $aina->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('readiness_confirmations')->insert([
            ['offering_id' => $o, 'role' => 'lecturer', 'user_id' => $aina->id, 'outcome' => 'ready', 'created_at' => now()->subDays(45)],
            ['offering_id' => $o, 'role' => 'ptj', 'user_id' => $hakim->id, 'outcome' => 'ready', 'created_at' => now()->subDays(44)],
        ]);

        $amir = User::where('name', 'Amir Hakimi')->firstOrFail();
        $people = [$amir];
        foreach ([['Bee Tan', 'B032410002'], ['Cee Lim', 'B032410003'], ['Dan Ong', 'B032410004']] as [$name, $matric]) {
            $u = User::firstOrCreate(['email' => strtolower(str_replace(' ', '.', $name)).'@learner.example.test'], ['name' => $name, 'participant_group' => 'working professional', 'institution_id' => $matric]);
            $u->roleAssignments()->firstOrCreate(['role' => 'student'], ['reason' => 'Seed data']);
            $people[] = $u;
        }
        foreach ($people as $u) {
            $e = DB::table('enrolments')->insertGetId(['reference' => 'E-D-'.$u->id, 'user_id' => $u->id, 'offering_id' => $o, 'mc_version_id' => $v, 'status' => 'enrolled', 'fee_amount' => 0, 'ack_text' => 'Seeded', 'ack_versions' => '{}', 'status_changed_at' => now()->subDays(30), 'created_at' => now()->subDays(30), 'updated_at' => now()]);
            foreach ($mods as $mid) {
                DB::table('module_enrolments')->insert(['enrolment_id' => $e, 'mc_module_id' => $mid, 'enrolled_at' => now()->subDays(30)]);
            }
        }
    }
}
