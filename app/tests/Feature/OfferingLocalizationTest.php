<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
});

it('renders offering readiness in English without bilingual stacking', function () {
    [$o] = openCohort();
    $res = $this->actingAs($this->aina)
        ->withSession(['lang' => 'en'])
        ->get("/offerings/$o");

    $res->assertOk();
    $content = $res->getContent();

    expect($content)->toContain('Automated System Readiness Checks')
        ->toContain('Course Information &amp; Support')
        ->toContain('Learning Materials &amp; Platform')
        ->toContain('Assessment &amp; Academic Integrity')
        ->toContain('Learner Protection &amp; Contingency')
        ->toContain('Operational Readiness Confirmed');

    expect($content)->not->toContain('B2. KETELUSAN MAKLUMAT SEBELUM PENDAFTARAN')
        ->not->toContain('Clear title, synopsis, and learning outcomes published');
});

it('renders offering readiness in Bahasa Melayu when BM locale is active', function () {
    [$o] = openCohort();
    $this->actingAs($this->aina)->post('/language', ['lang' => 'ms']);
    $res = $this->actingAs($this->aina)->get("/offerings/$o");

    $res->assertOk();
    $content = $res->getContent();

    expect($content)->toContain('Semakan Automatik Kesediaan Sistem')
        ->toContain('Maklumat Kursus &amp; Sokongan')
        ->toContain('Bahan Pembelajaran &amp; Platform')
        ->toContain('Pentaksiran &amp; Integriti Akademik')
        ->toContain('Perlindungan Peserta &amp; Kontingensi')
        ->toContain('Tadbir Urus Rekod Peserta &amp; Pencapaian')
        ->toContain('Kesediaan Operasi Disahkan');

    expect($content)->not->toContain('Course Information &amp; Support')
        ->not->toContain('B2. KETELUSAN MAKLUMAT SEBELUM PENDAFTARAN');
});
