<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

// FL-12: Amir has a deposited credit (FL-11) and applies for BITS 1123; Bee Tan has one too, for the case whose outcomes do not match. Run on request: php artisan db:seed --class=DemoTransferSeeder
class DemoTransferSeeder extends DemoCreditBankSeeder
{
    public function run(): void
    {
        parent::run();
        // The BITS programme belongs to the faculty whose deputy dean decides.
        // Bee Tan has a deposited credit already, so a second case can start without walking the consent flow again.
        $hakim = User::where('name', 'Hakim Rahman')->firstOrFail();
        $bee = User::where('name', 'Bee Tan')->first();
        $acr = $bee ? DB::table('acrs')->where('user_id', $bee->id)->first() : null;
        if ($acr && ! DB::table('abc_deposits')->where('acr_id', $acr->id)->exists()) {
            DB::table('acrs')->where('id', $acr->id)->update(['status' => 'deposited']);
            $id = DB::table('abc_deposits')->insertGetId(['acr_id' => $acr->id, 'abc_reference' => 'ABC-DEMO-BEE', 'deposit_date' => today(), 'credits' => $acr->credits, 'credit_expiry' => today()->addYears(10), 'deposit_status' => 'successful', 'credit_status' => 'available', 'recorded_by' => $hakim->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('credit_ledger_entries')->insert(['abc_deposit_id' => $id, 'event' => 'deposit', 'to_status' => 'available', 'credits' => $acr->credits, 'recorded_by' => $hakim->id, 'occurred_at' => now()]);
        }
        DB::table('programmes')->where('code', 'BITS')->whereNull('ptj_id')->update(['ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
    }
}
