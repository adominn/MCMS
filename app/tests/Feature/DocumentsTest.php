<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoScenarioSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->seed(DemoScenarioSeeder::class);
});

it('renders student enrolment confirmation letter under standard print layout', function () {
    $student = User::where('name', 'Amir Hakimi')->firstOrFail();
    $enrolment = DB::table('enrolments')->where('user_id', $student->id)->first();

    if (!$enrolment) {
        $offering = DB::table('offerings')->first();
        $eid = DB::table('enrolments')->insertGetId([
            'user_id' => $student->id,
            'offering_id' => $offering->id,
            'mc_version_id' => $offering->mc_version_id,
            'reference' => 'E-2026-TEST',
            'status' => 'enrolled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $enrolment = DB::table('enrolments')->find($eid);
    }

    $response = $this->actingAs($student)->get("/enrolments/{$enrolment->id}/confirmation");

    $response->assertOk()
        ->assertSee('Enrolment confirmation')
        ->assertSee('Universiti Teknikal Malaysia Melaka')
        ->assertSee('Centre for Open and Distance Learning (CODL)')
        ->assertSee('Your registration is confirmed')
        ->assertSee($student->name)
        ->assertSee($enrolment->reference)
        ->assertSee('UCreds')
        ->assertSee('Print or save as PDF');
});

it('renders digital credential printable sheet with W3C VC proof and JSON', function () {
    $student = User::where('name', 'Amir Hakimi')->firstOrFail();
    $credential = DB::table('credentials as c')
        ->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')
        ->where('e.user_id', $student->id)
        ->first(['c.id', 'c.number', 'c.public_code', 'c.status']);

    if (!$credential) {
        $enrolment = DB::table('enrolments')->where('user_id', $student->id)->first();
        $cid = DB::table('credentials')->insertGetId([
            'number' => 'MCMS-C-2026-TEST01',
            'public_code' => 'TESTCODE01',
            'enrolment_id' => $enrolment->id,
            'status' => 'valid',
            'content' => json_encode([
                'holder' => $student->name,
                'name' => ['en' => 'Cloud Native Development', 'ms' => 'Cloud Native Development'],
                'category' => 'SAMC',
                'issuer' => 'Universiti Teknikal Malaysia Melaka',
                'issued' => now()->toIso8601String(),
                'claims' => ['credit' => 3, 'mqf' => 4, 'qv' => true, 'qv_until' => now()->addYears(3)->toDateString()],
            ]),
            'jwt' => '',
            'kid' => 'test-key-1',
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $credential = DB::table('credentials')->find($cid);
    }

    $response = $this->actingAs($student)->get("/my-credentials/{$credential->id}/sheet");

    $response->assertOk()
        ->assertSee('Digital Credential')
        ->assertSee('Open Badges 3.0')
        ->assertSee('W3C Verifiable Credential 2.0')
        ->assertSee('Format')
        ->assertSee('Proof block')
        ->assertSee('DataIntegrityProof')
        ->assertSee('eddsa-rdfc-2022')
        ->assertSee('Credential JSON')
        ->assertSee('Print or save as PDF');
});

it('prevents unauthorized access to digital credential sheet', function () {
    $student = User::where('name', 'Amir Hakimi')->firstOrFail();
    $credential = DB::table('credentials')->first();

    if (!$credential) {
        $enrolment = DB::table('enrolments')->where('user_id', $student->id)->first();
        $cid = DB::table('credentials')->insertGetId([
            'number' => 'MCMS-C-2026-TEST01',
            'public_code' => 'TESTCODE01',
            'enrolment_id' => $enrolment ? $enrolment->id : 1,
            'status' => 'valid',
            'content' => json_encode(['holder' => $student->name]),
            'jwt' => '',
            'kid' => 'test-key-1',
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $credential = DB::table('credentials')->find($cid);
    }

    $otherUser = User::firstOrCreate(
        ['email' => 'other.student@example.test'],
        ['name' => 'Other Student', 'upn' => 'other.student@example.test', 'locale' => 'en']
    );
    $otherUser->roleAssignments()->firstOrCreate(['role' => 'student'], ['reason' => 'Test']);

    $response = $this->actingAs($otherUser)->get("/my-credentials/{$credential->id}/sheet");
    $response->assertForbidden();
});

it('renders proposal print (Appendix A) with 10 sections', function () {
    $version = DB::table('mc_versions')->first();
    expect($version)->not->toBeNull();

    $owner = User::find(DB::table('micro_credentials')->where('id', $version->micro_credential_id)->value('owner_id'));

    $response = $this->actingAs($owner)->get("/proposals/{$version->id}/print");

    $response->assertOk()
        ->assertSee('Appendix A')
        ->assertSee('1. Basics')
        ->assertSee('2. Justification')
        ->assertSee('3. Learning outcomes')
        ->assertSee('4. Modules and learning time (SLT)')
        ->assertSee('5. Assessment weightings')
        ->assertSee('6. Alignment of outcomes to assessment')
        ->assertSee('7. Credential design')
        ->assertSee('8. Delivery')
        ->assertSee('9. Co-developers')
        ->assertSee('10. Declarations and Signatures')
        ->assertSee('Intellectual property declaration')
        ->assertSee('Print or save as PDF');
});

it('renders suspension notice under standard print layout', function () {
    $dean = User::whereHas('roleAssignments', fn ($q) => $q->where('role', 'deputy_dean'))->firstOrFail();
    $mc = DB::table('micro_credentials')->first();
    expect($mc)->not->toBeNull();

    $response = $this->actingAs($dean)->get("/suspensions/{$mc->id}/print");

    $response->assertOk()
        ->assertSee('notice')
        ->assertSee('Who is affected')
        ->assertSee('Transition plan')
        ->assertSee('Credentials and credit you have already earned stay valid')
        ->assertSee('Kredensial dan kredit yang telah anda peroleh kekal sah')
        ->assertSee('Print or save as PDF');
});

it('renders UCreds import validation report in printable dual-mode', function () {
    $admin = User::whereHas('roleAssignments', fn ($q) => $q->where('role', 'codl_admin'))->firstOrFail();
    $import = DB::table('import_files')->first();

    if (!$import) {
        $offering = DB::table('offerings')->first();
        $iid = DB::table('import_files')->insertGetId([
            'offering_id' => $offering->id,
            'kind' => 'results',
            'template_version' => '1.0',
            'original_name' => 'results-test-cohort.csv',
            'path' => 'ucreds/imports/test.csv',
            'sha256' => hash('sha256', 'dummy'),
            'row_count' => 20,
            'rejected_count' => 2,
            'staged_count' => 18,
            'status' => 'partly_accepted',
            'run_by' => $admin->id,
            'created_at' => now(),
        ]);
        $import = DB::table('import_files')->find($iid);
    }

    $response = $this->actingAs($admin)->get("/admin/ucreds/imports/{$import->id}?print=1");

    $response->assertOk()
        ->assertSee('Import Validation Report')
        ->assertSee('Row counts')
        ->assertSee('Staged as Provisional')
        ->assertSee('Rejected')
        ->assertSee('Provisional status note')
        ->assertSee('Print or save as PDF');
});

it('renders UCreds enrolment export manifest under standard print layout', function () {
    $admin = User::whereHas('roleAssignments', fn ($q) => $q->where('role', 'codl_admin'))->firstOrFail();
    $offering = DB::table('offerings')->first();
    expect($offering)->not->toBeNull();

    $response = $this->actingAs($admin)->get("/admin/ucreds/export/{$offering->id}/manifest");

    $response->assertOk()
        ->assertSee('UCreds Enrolment Export Manifest')
        ->assertSee('Columns in export file')
        ->assertSee('learner_id')
        ->assertSee('full_name')
        ->assertSee('enrolment_id')
        ->assertSee('offering')
        ->assertSee('cohort')
        ->assertSee('start_date')
        ->assertSee('end_date')
        ->assertSee('Enrolment validation policy')
        ->assertSee('Print or save as PDF');
});
