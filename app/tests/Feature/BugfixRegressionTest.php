<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

it('renders stackable plan detail cleanly when remaining requirements contain status planned', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();
    $dd = User::where('name', 'Hakim Rahman')->firstOrFail();
    $senate = User::where('name', 'Prof. Dr. Zulkifli Ahmad')->firstOrFail();
    $prog = DB::table('programmes')->where('code', 'BITS')->first();

    $pathwayId = DB::table('pathways')->insertGetId([
        'reference' => 'PW-REG-001',
        'programme_id' => $prog->id,
        'status' => 'published',
        'created_by' => $dd->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $planId = DB::table('stackable_plans')->insertGetId([
        'reference' => 'PLAN-REG-001',
        'user_id' => $amir->id,
        'programme_id' => $prog->id,
        'pathway_id' => $pathwayId,
        'item_ids' => json_encode([]),
        'status' => 'submitted',
        'delivery_method' => 'conventional',
        'residency_semesters' => 2,
        'transfer_credits' => 9,
        'other_credits' => 0,
        'utem_percent' => 92.5,
        'remaining' => json_encode([
            ['kind' => 'core', 'name' => 'BITI 2213 Artificial Intelligence', 'status' => 'planned'],
            ['kind' => 'project', 'name' => 'BITU 3983 Final Year Project I', 'status' => 'todo'],
        ]),
        'created_by' => $dd->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($dd)->get("/plans/{$planId}")
        ->assertOk()
        ->assertSee('PLAN-REG-001')
        ->assertSee('Planned');

    $this->actingAs($senate)->get("/plans/{$planId}")
        ->assertOk()
        ->assertSee('PLAN-REG-001')
        ->assertSee('Planned');
});

it('uses staff transfers.document route instead of student transfer.document on read-only transfer view', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();
    $dd = User::where('name', 'Hakim Rahman')->firstOrFail();
    $prog = DB::table('programmes')->where('code', 'BITS')->first();

    $appId = DB::table('transfer_applications')->insertGetId([
        'reference' => 'TR-REG-001',
        'user_id' => $amir->id,
        'programme_id' => $prog->id,
        'status' => 'approved',
        'documents' => 'syllabus-ds.pdf',
        'document_checklist' => json_encode(['items' => ['acr', 'credential']]),
        'applied_by' => $amir->id,
        'applied_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $res = $this->actingAs($dd)->get("/transfers/{$appId}");
    $res->assertOk()
        ->assertSee("/transfers/{$appId}/document/", false)
        ->assertDontSee("/transfer/{$appId}/document/", false);
});

it('seeds WalkthroughDemoSeeder credentials with signed Ed25519 JWT and kid without SQL errors', function () {
    $this->seed(\Database\Seeders\DemoResultsSeeder::class);
    $this->seed(\Database\Seeders\WalkthroughDemoSeeder::class);

    $hana = User::where('email', 'hana.sofea@learner.example.test')->firstOrFail();
    $creds = DB::table('credentials')
        ->join('enrolments', 'enrolments.id', '=', 'credentials.enrolment_id')
        ->where('enrolments.user_id', $hana->id)
        ->select('credentials.*')
        ->get();
    expect($creds->count())->toBeGreaterThan(0);
    foreach ($creds as $cred) {
        expect($cred->jwt)->not->toBeEmpty();
        expect($cred->kid)->not->toBeEmpty();
    }
});
