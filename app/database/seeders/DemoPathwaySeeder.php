<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// FL-13: a published pathway to BITS (12 graduating credits here, so the shares are easy to read) that maps Data Structures to three courses.
// Run on request: php artisan db:seed --class=DemoPathwaySeeder
class DemoPathwaySeeder extends Seeder
{
    public function run(): void
    {
        $bits = DB::table('programmes')->where('code', 'BITS')->first();
        if (! $bits) {
            return;
        }
        DB::table('programmes')->where('id', $bits->id)->update(['total_credits' => 12, 'mode' => 'conventional', 'intake' => '2027/1']);
        foreach (['BITS 1124' => 'Algorithms', 'BITS 1125' => 'Databases'] as $code => $name) {
            DB::table('parent_courses')->insertOrIgnore(['programme_id' => $bits->id, 'code' => $code, 'name' => $name, 'credit' => 3, 'slt_hours' => 120, 'clos' => 'Apply the topic;Explain the theory', 'created_at' => now(), 'updated_at' => now()]);
        }
        $mc = DB::table('mc_versions')->where('name_en', 'Data Structures')->value('micro_credential_id');
        $hakim = DB::table('users')->where('name', 'Hakim Rahman')->value('id');

        $existing = DB::table('pathways')->where('programme_id', $bits->id)->first();
        if ($existing) {
            $pw = $existing->id;
            DB::table('pathways')->where('id', $pw)->update([
                'reference' => 'PW-BITS-1',
                'status' => 'published',
                'plos' => json_encode(['PLO1']),
                'bok' => json_encode(['Algorithms']),
                'approval_ref' => 'Senate 7/2026',
                'approved_on' => today(),
                'approved_by' => $hakim,
                'published_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $pw = DB::table('pathways')->insertGetId([
                'reference' => 'PW-BITS-1', 'programme_id' => $bits->id, 'version_no' => 1, 'status' => 'published', 'plos' => json_encode(['PLO1']), 'bok' => json_encode(['Algorithms']),
                'approval_ref' => 'Senate 7/2026', 'approved_on' => today(), 'approved_by' => $hakim, 'published_at' => now(), 'created_by' => $hakim, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('pathway_items')->where('pathway_id', $pw)->delete();
        foreach (['BITS 1123', 'BITS 1124', 'BITS 1125'] as $code) {
            DB::table('pathway_items')->insert(['pathway_id' => $pw, 'micro_credential_id' => $mc, 'parent_course_id' => DB::table('parent_courses')->where('code', $code)->value('id'), 'credits' => 3, 'lo_equivalence' => 'high', 'content_equivalence' => 'high',
                'preliminary' => 'suitable', 'plo_map' => json_encode([['plo' => 'PLO1', 'contribution' => 'main']]), 'bok_map' => json_encode(['Algorithms'])]);
        }
    }
}
