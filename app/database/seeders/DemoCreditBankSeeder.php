<?php

namespace Database\Seeders;

use App\Modules\CreditBank\Acr;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// FL-11: prepares the Academic Credit Record of every completed enrolment that has a credential and none yet. Run on request: php artisan db:seed --class=DemoCreditBankSeeder
class DemoCreditBankSeeder extends Seeder
{
    public function run(): void
    {
        // The demonstration Data Structures is the UMC of the scenario: it comes from the accredited course BITS 1123.
        $course = DB::table('parent_courses')->where('code', 'BITS 1123')->value('id');
        foreach (DB::table('mc_versions')->where('name_en', 'Data Structures')->get() as $v) {
            DB::table('mc_versions')->where('id', $v->id)->update(['parent_course_id' => $course]);
            DB::table('micro_credentials')->where('id', $v->micro_credential_id)->update(['category' => 'umc', 'origin' => 'unbundled']);
            // The Senate decision on record, so the trace shows the approval stage.
            $hakim = DB::table('users')->where('name', 'Hakim Rahman')->value('id');
            if ($hakim && ! DB::table('approval_decisions')->where('mc_version_id', $v->id)->exists()) {
                foreach (['endorsement' => ['endorsed', null], 'senate' => ['approved', 'Senate 3/2026']] as $stage => [$decision, $ref]) {
                    DB::table('approval_decisions')->insert(['mc_version_id' => $v->id, 'stage' => $stage, 'decision' => $decision, 'decided_by' => $hakim, 'decided_role' => 'deputy_dean', 'meeting_ref' => $ref, 'meeting_date' => $ref ? today()->subMonths(3) : null, 'created_at' => now()]);
                }
            }
        }
        foreach (DB::table('credentials')->whereIn('status', ['valid', 'corrected'])->pluck('enrolment_id') as $e) {
            app(Acr::class)->prepare((int) $e);
        }
    }
}
