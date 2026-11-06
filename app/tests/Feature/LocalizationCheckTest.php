<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
});

it('renders the proposal workspace completely in Bahasa Melayu when BM is active', function () {
    // Create a new proposal with empty details so blocking errors appear
    $this->actingAs($this->aina)->post('/proposals', [
        'origin' => 'unbundled', 'slt' => 120, 'credit' => 'no', 'proposal_type' => 'new', 'name_en' => 'Software Testing',
        'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id'), 'parent_course_id' => DB::table('parent_courses')->where('code', 'BITS 1123')->value('id'),
    ])->assertRedirect();

    $id = (int) DB::table('mc_versions')->max('id');

    $response = $this->actingAs($this->aina)
        ->withSession(['active_role' => 'lecturer', 'ui_lang' => 'ms', 'lang' => 'ms'])
        ->get("/proposals/{$id}?tab=justification");

    $response->assertOk();
    $content = $response->getContent();

    $expectedStrings = [
        'Lampiran A',
        'Versi',
        'Disimpan secara automatik',
        'Maklumat asas',
        'Justifikasi',
        'Modul',
        'Hasil pembelajaran',
        'Pentaksiran',
        'Kredensial',
        'Ketersediaan',
        'Hantar',
        'Analisis industri atau majikan',
        'Maklum balas peserta',
        'Keperluan program',
        'Standard profesional',
        'Perubahan teknologi',
        'Lain-lain',
        'Perubahan disimpan secara automatik.',
        'ralat menyekat',
        'Masukkan tajuk dalam kedua-dua Bahasa Inggeris dan Bahasa Melayu.',
        'Pilih mod penyampaian dan masukkan bahasa penyampaian.',
        'Huraikan keperluan atau masalah yang ditangani oleh kredensial mikro ini.',
        'Tambah sekurang-kurangnya satu modul.',
        'Tambah sekurang-kurangnya satu hasil pembelajaran.',
        'Tambah sekurang-kurangnya satu komponen pentaksiran.',
        'Sahkan bahawa bahan pihak ketiga mematuhi peraturan hak cipta dan harta intelek.',
    ];

    foreach ($expectedStrings as $s) {
        expect($content)->toContain($s);
    }
});

it('localizes navigation and common pages in Bahasa Melayu', function () {
    $response = $this->actingAs($this->aina)
        ->withSession(['active_role' => 'lecturer', 'ui_lang' => 'ms', 'lang' => 'ms'])
        ->get("/tasks");

    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('Tugas saya');
});
