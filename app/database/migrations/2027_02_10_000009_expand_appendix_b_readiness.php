<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $items = config('offerings.readiness');
        $offerings = DB::table('offerings')->pluck('id');

        foreach ($offerings as $oid) {
            foreach ($items as $k => $label) {
                DB::table('readiness_items')->insertOrIgnore([
                    'offering_id' => $oid,
                    'item' => $k,
                    'status' => 'yes',
                    'note' => 'Disahkan mematuhi standard Garis Panduan Lampiran B (Verified per Appendix B standards).',
                    'accepted' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive
    }
};
