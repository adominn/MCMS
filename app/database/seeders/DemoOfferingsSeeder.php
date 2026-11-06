<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Two open, paid cohorts for the registration flows (FL-06, FL-06b). Run on request: php artisan db:seed --class=DemoOfferingsSeeder
class DemoOfferingsSeeder extends DemoProposalsSeeder
{
    public function run(): void
    {
        if (DB::table('mc_versions')->where('name_en', 'Cloud Native Development')->exists()) {
            return;
        }
        $ftmk = DB::table('ptjs')->where('code', 'FTMK')->value('id');
        $aina = User::where('name', 'Dr. Aina Zulkifli')->firstOrFail();
        $hakim = User::where('name', 'Hakim Rahman')->firstOrFail();

        foreach ([['Cloud Native Development', '2027/1', 150, 'Basic programming'], ['Lean Six Sigma Yellow Belt', '2026/12', 200, null]] as [$title, $cohort, $fee, $prereq]) {
            $v = $this->proposal($aina, $ftmk, $title, 'approved', now()->subDays(30));
            DB::table('mc_versions')->where('id', $v)->update(['effective_date' => today()->subDays(10)]);
            DB::table('micro_credentials')->where('id', DB::table('mc_versions')->where('id', $v)->value('micro_credential_id'))->update(['category_status' => 'confirmed']);
            $o = DB::table('offerings')->insertGetId([
                'mc_version_id' => $v, 'cohort_name' => $cohort, 'start_date' => today()->addDays(5), 'end_date' => today()->addDays(90), 'delivery_mode' => 'online', 'capacity' => 30,
                'reg_opens_on' => today()->subDay(), 'reg_closes_on' => today()->addDays(4), 'fee' => $fee, 'facilitators' => 'Dr. Aina Zulkifli', 'platform_ref' => 'UCreds course '.strtoupper(substr($title, 0, 3)),
                'prerequisites' => $prereq, 'description' => 'Hands-on labs and weekly clinics.', 'status' => 'open', 'readiness_state' => 'ready', 'created_by' => $aina->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (array_keys(config('offerings.readiness')) as $item) {
                DB::table('readiness_items')->insert(['offering_id' => $o, 'item' => $item, 'status' => 'yes', 'note' => 'Evidence on file', 'accepted' => true]);
            }
            DB::table('readiness_confirmations')->insert([
                ['offering_id' => $o, 'role' => 'lecturer', 'user_id' => $aina->id, 'outcome' => 'ready', 'created_at' => now()],
                ['offering_id' => $o, 'role' => 'ptj', 'user_id' => $hakim->id, 'outcome' => 'ready', 'created_at' => now()],
            ]);
        }
    }
}
