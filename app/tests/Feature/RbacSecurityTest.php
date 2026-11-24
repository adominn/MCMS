<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

if (! function_exists('findUser')) {
function findUser(string $name): User
{
    return User::where('name', $name)->firstOrFail();
}
}


it('verifies student role route access and restrictions', function () {
    $student = findUser('Amir Hakimi');

    // Accessible student pages
    $this->actingAs($student)->get('/tasks')->assertOk();
    $this->actingAs($student)->get('/help')->assertOk()->assertSee('My learning');
    $this->actingAs($student)->get('/profile')->assertOk();
    $this->actingAs($student)->get('/documents')->assertOk();
    $this->actingAs($student)->get('/notifications')->assertOk();
    $this->actingAs($student)->get('/enrolments')->assertOk();
    $this->actingAs($student)->get('/my-record')->assertOk();
    $this->actingAs($student)->get('/my-credentials')->assertOk();
    $this->actingAs($student)->get('/my-credit')->assertOk();
    $this->actingAs($student)->get('/transfer')->assertOk();
    $this->actingAs($student)->get('/feedback')->assertOk();
    $this->actingAs($student)->get('/my-results')->assertOk();

    // Forbidden staff and admin pages (403)
    $this->actingAs($student)->get('/proposals')->assertForbidden();
    $this->actingAs($student)->get('/micro-credentials')->assertForbidden();
    $this->actingAs($student)->get('/offerings')->assertForbidden();
    $this->actingAs($student)->get('/approvals')->assertForbidden();
    $this->actingAs($student)->get('/results')->assertForbidden();
    $this->actingAs($student)->get('/credentials')->assertForbidden();
    $this->actingAs($student)->get('/deposits')->assertForbidden();
    $this->actingAs($student)->get('/transfers')->assertForbidden();
    $this->actingAs($student)->get('/suspensions')->assertForbidden();
    $this->actingAs($student)->get('/admin/users')->assertForbidden();
    $this->actingAs($student)->get('/admin/settings')->assertForbidden();
    $this->actingAs($student)->get('/admin/audit')->assertForbidden();
});

it('verifies lecturer role route access and restrictions', function () {
    $lecturer = findUser('Dr. Aina Zulkifli');

    // Accessible teaching and review pages
    $this->actingAs($lecturer)->get('/tasks')->assertOk();
    $this->actingAs($lecturer)->get('/help')->assertOk()->assertSee('Proposals and versions');
    $this->actingAs($lecturer)->get('/profile')->assertOk();
    $this->actingAs($lecturer)->get('/proposals')->assertOk();
    $this->actingAs($lecturer)->get('/micro-credentials')->assertOk();
    $this->actingAs($lecturer)->get('/offerings')->assertOk();
    $this->actingAs($lecturer)->get('/reviews')->assertOk();
    $this->actingAs($lecturer)->get('/quality')->assertOk();
    $this->actingAs($lecturer)->get('/monitoring')->assertOk();
    $this->actingAs($lecturer)->get('/pathways')->assertOk();
    $this->actingAs($lecturer)->get('/records/reports')->assertOk();

    // Forbidden student pages (403)
    $this->actingAs($lecturer)->get('/enrolments')->assertForbidden();
    $this->actingAs($lecturer)->get('/my-credentials')->assertForbidden();
    $this->actingAs($lecturer)->get('/my-credit')->assertForbidden();
    $this->actingAs($lecturer)->get('/transfer')->assertForbidden();
    $this->actingAs($lecturer)->get('/feedback')->assertForbidden();
    $this->actingAs($lecturer)->get('/my-results')->assertForbidden();

    // Forbidden deputy dean decision pages (403)
    $this->actingAs($lecturer)->get('/approvals')->assertForbidden();
    $this->actingAs($lecturer)->get('/results')->assertForbidden();
    $this->actingAs($lecturer)->get('/credentials')->assertForbidden();
    $this->actingAs($lecturer)->get('/deposits')->assertForbidden();
    $this->actingAs($lecturer)->get('/transfers')->assertForbidden();
    $this->actingAs($lecturer)->get('/suspensions')->assertForbidden();

    // Forbidden administrator pages (403)
    $this->actingAs($lecturer)->get('/admin/users')->assertForbidden();
    $this->actingAs($lecturer)->get('/admin/settings')->assertForbidden();
    $this->actingAs($lecturer)->get('/admin/audit')->assertForbidden();
});

it('verifies deputy dean role route access and restrictions', function () {
    $dean = findUser('Hakim Rahman');

    // Accessible academic governance and decision pages
    $this->actingAs($dean)->get('/tasks')->assertOk();
    $this->actingAs($dean)->get('/help')->assertOk()->assertSee('Task queue');
    $this->actingAs($dean)->get('/profile')->assertOk();
    $this->actingAs($dean)->get('/approvals')->assertOk();
    $this->actingAs($dean)->get('/offerings')->assertOk();
    $this->actingAs($dean)->get('/results')->assertOk();
    $this->actingAs($dean)->get('/credentials')->assertOk();
    $this->actingAs($dean)->get('/quality')->assertOk();
    $this->actingAs($dean)->get('/deposits')->assertForbidden(); // P6/P7 Registry / ABC Depositor separated from Deputy Dean (Guide App H2/H3)
    $this->actingAs(userNurul())->get('/deposits')->assertOk();
    $this->actingAs($dean)->get('/transfers')->assertOk();
    $this->actingAs($dean)->get('/pathways')->assertOk();
    $this->actingAs($dean)->get('/monitoring')->assertOk();
    $this->actingAs($dean)->get('/records/reports')->assertOk();
    $this->actingAs($dean)->get('/records/trace')->assertOk();
    $this->actingAs($dean)->get('/suspensions')->assertOk();

    // Forbidden student pages (403)
    $this->actingAs($dean)->get('/enrolments')->assertForbidden();
    $this->actingAs($dean)->get('/my-credentials')->assertForbidden();
    $this->actingAs($dean)->get('/my-credit')->assertForbidden();
    $this->actingAs($dean)->get('/transfer')->assertForbidden();

    // Forbidden lecturer proposal authoring pages (403) - Separation of Duties (D-52)
    $this->actingAs($dean)->get('/proposals')->assertForbidden();
    $this->actingAs($dean)->get('/proposals/new')->assertForbidden();
    $this->actingAs($dean)->get('/micro-credentials')->assertForbidden();

    // Forbidden administrator pages (403)
    $this->actingAs($dean)->get('/admin/users')->assertForbidden();
    $this->actingAs($dean)->get('/admin/settings')->assertForbidden();
    $this->actingAs($dean)->get('/admin/audit')->assertForbidden();
});

it('verifies CODL admin role route access and zero academic authority (Decision D-12)', function () {
    $admin = findUser('Farid Hashim');

    // Accessible administrative pages
    $this->actingAs($admin)->get('/tasks')->assertOk();
    $this->actingAs($admin)->get('/help')->assertOk()->assertSee('Administration & governance');
    $this->actingAs($admin)->get('/profile')->assertOk();
    $this->actingAs($admin)->get('/admin/users')->assertOk();
    $this->actingAs($admin)->get('/admin/settings')->assertOk();
    $this->actingAs($admin)->get('/admin/reference/ptj')->assertOk();
    $this->actingAs($admin)->get('/admin/templates')->assertOk();
    $this->actingAs($admin)->get('/admin/adapters')->assertOk();
    $this->actingAs($admin)->get('/admin/audit')->assertOk();
    $this->actingAs($admin)->get('/records/retention')->assertOk();
    $this->actingAs($admin)->get('/records/reports')->assertOk();
    $this->actingAs($admin)->get('/approvals')->assertOk();
    $this->actingAs($admin)->get('/transfers')->assertOk();
    $this->actingAs($admin)->get('/pathways')->assertOk();

    // Faculty teaching/assessment/deposit operations remain Forbidden (403)
    $this->actingAs($admin)->get('/proposals')->assertForbidden();
    $this->actingAs($admin)->get('/micro-credentials')->assertForbidden();
    $this->actingAs($admin)->get('/results')->assertForbidden();
    $this->actingAs($admin)->get('/credentials')->assertForbidden();
    $this->actingAs($admin)->get('/deposits')->assertForbidden();
    $this->actingAs($admin)->get('/suspensions')->assertForbidden();
    $this->actingAs($admin)->get('/enrolments')->assertForbidden();
    $this->actingAs($admin)->get('/my-credit')->assertForbidden();
});

it('verifies industry expert role access and restrictions', function () {
    $expert = findUser('Ir. Siti Aminah');

    // Industry expert home redirects to reviews (matching prototype)
    $this->actingAs($expert)->get('/tasks')->assertRedirect('/reviews');
    $this->actingAs($expert)->get('/help')->assertOk()->assertSee(__('Review invitations'));
    $this->actingAs($expert)->get('/profile')->assertOk();
    $this->actingAs($expert)->get('/reviews')->assertOk();

    // Forbidden pages (403)
    $this->actingAs($expert)->get('/proposals')->assertForbidden();
    $this->actingAs($expert)->get('/approvals')->assertForbidden();
    $this->actingAs($expert)->get('/results')->assertForbidden();
    $this->actingAs($expert)->get('/credentials')->assertForbidden();
    $this->actingAs($expert)->get('/deposits')->assertForbidden();
    $this->actingAs($expert)->get('/transfers')->assertForbidden();
    $this->actingAs($expert)->get('/enrolments')->assertForbidden();
    $this->actingAs($expert)->get('/admin/users')->assertForbidden();
});

it('verifies academic authority (P3 / Senate) role route access and restrictions', function () {
    $academic = findUser('Prof. Dr. Zulkifli Ahmad');

    // Accessible academic authority pages
    $this->actingAs($academic)->get('/approvals')->assertOk();
    $this->actingAs($academic)->get('/pathways')->assertOk();
    $this->actingAs($academic)->get('/transfers')->assertOk();
    $this->actingAs($academic)->get('/quality')->assertOk();
    $this->actingAs($academic)->get('/monitoring')->assertOk();

    // Forbidden administrative and proposal creation pages (403)
    $this->actingAs($academic)->get('/admin/users')->assertForbidden();
    $this->actingAs($academic)->get('/admin/settings')->assertForbidden();
    $this->actingAs($academic)->get('/deposits')->assertForbidden();
    $this->actingAs($academic)->get('/proposals/new')->assertForbidden();
    $this->actingAs($academic)->get('/my-results')->assertForbidden();
});

it('verifies quality assurance authority (P4 / PBK) role route access and restrictions', function () {
    $qa = findUser('Dr. Norazlina Mohd');

    // Accessible QA pages
    $this->actingAs($qa)->get('/monitoring')->assertOk();
    $this->actingAs($qa)->get('/quality')->assertOk();
    $this->actingAs($qa)->get('/approvals')->assertOk(); // QA audit review of approvals

    // Forbidden deposit and proposal authoring pages (403)
    $this->actingAs($qa)->get('/deposits')->assertForbidden();
    $this->actingAs($qa)->get('/admin/users')->assertForbidden();
    $this->actingAs($qa)->get('/admin/settings')->assertForbidden();
    $this->actingAs($qa)->get('/proposals/new')->assertForbidden();
});

it('verifies records custodian (P6 / Registry) role route access and separation of duties (Note H)', function () {
    $registry = findUser('Hasnah Abu Bakar');

    // Accessible registry & credentials pages
    $this->actingAs($registry)->get('/credentials')->assertOk();
    $this->actingAs($registry)->get('/records/reports')->assertOk();

    // Forbidden academic verification, ABC deposit, and admin pages per Note H separation of duties (403)
    $this->actingAs($registry)->get('/results')->assertForbidden();
    $this->actingAs($registry)->get('/transfers')->assertForbidden();
    $this->actingAs($registry)->get('/deposits')->assertForbidden();
    $this->actingAs($registry)->get('/admin/users')->assertForbidden();
    $this->actingAs($registry)->get('/proposals/new')->assertForbidden();
});

it('verifies authorized ABC depositor (P7) role route access and separation of duties (Note H)', function () {
    $depositor = findUser('Nurul Izzah');

    // Accessible deposit pages
    $this->actingAs($depositor)->get('/deposits')->assertOk();

    // Forbidden administrative and proposal creation pages
    $this->actingAs($depositor)->get('/admin/users')->assertForbidden();
    $this->actingAs($depositor)->get('/admin/settings')->assertForbidden();
    $this->actingAs($depositor)->get('/proposals/new')->assertForbidden();
});

it('verifies unauthenticated guests are redirected to signin for protected routes', function () {
    // Protected routes redirect to signin
    $this->get('/tasks')->assertRedirect('/signin');
    $this->get('/help')->assertRedirect('/signin');
    $this->get('/profile')->assertRedirect('/signin');
    $this->get('/enrolments')->assertRedirect('/signin');
    $this->get('/proposals')->assertRedirect('/signin');
    $this->get('/approvals')->assertRedirect('/signin');
    $this->get('/admin/users')->assertRedirect('/signin');

    // Public pages are accessible (200)
    $this->get('/')->assertRedirect('/signin');
    $this->get('/signin')->assertOk();
    $this->get('/public')->assertOk();
    $this->get('/public/catalogue')->assertOk();
    $this->get('/public/how')->assertOk();
    $this->get('/public/pathways')->assertOk();
    $this->get('/public/faq')->assertOk();
    $this->get('/public/privacy')->assertOk();
    $this->get('/public/terms')->assertOk();
    $this->get('/public/accessibility')->assertOk();
    $this->get('/public/verify')->assertOk();
});

it('enforces horizontal data isolation for learners', function () {
    $student1 = findUser('Amir Hakimi');
    $student2 = User::create([
        'name' => 'Siti Nurhaliza',
        'email' => 'siti.nur@learner.example.test',
        'upn' => 'siti.nur@learner.example.test',
        'locale' => 'en',
    ]);
    $student2->roleAssignments()->create(['role' => 'student', 'reason' => 'Test']);

    $ftmkId = DB::table('ptjs')->where('code', 'FTMK')->value('id');
    $mcId = DB::table('micro_credentials')->insertGetId([
        'code' => 'TEST-01',
        'category' => 'umc',
        'origin' => 'unbundled',
        'ptj_id' => $ftmkId,
        'owner_id' => findUser('Dr. Aina Zulkifli')->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $verId = DB::table('mc_versions')->insertGetId([
        'micro_credential_id' => $mcId,
        'version_no' => 1,
        'name_en' => 'Test MC',
        'status' => 'approved',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $offId = DB::table('offerings')->insertGetId([
        'mc_version_id' => $verId,
        'cohort_name' => '2026/1',
        'status' => 'running',
        'delivery_mode' => 'online',
        'capacity' => 30,
        'reg_opens_on' => today()->subMonths(2),
        'reg_closes_on' => today()->subMonth(),
        'start_date' => today()->subMonth(),
        'end_date' => today()->addMonth(),
        'created_by' => findUser('Dr. Aina Zulkifli')->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $e2 = DB::table('enrolments')->insertGetId([
        'user_id' => $student2->id,
        'offering_id' => $offId,
        'mc_version_id' => $verId,
        'reference' => 'ENR-SITI-01',
        'status' => 'enrolled',
        'ack_text' => 'Acknowledged personal data and recognition conditions',
        'ack_versions' => json_encode(['recognition' => 1]),
        'status_changed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Student 1 cannot view Student 2's enrolment (403)
    $this->actingAs($student1)->get("/enrolments/{$e2}")->assertForbidden();
    $this->actingAs($student1)->get("/enrolments/{$e2}/receipt")->assertForbidden();
    $this->actingAs($student1)->post("/enrolments/{$e2}/withdraw", ['reason' => 'test'])->assertForbidden();

    // Student 2 can view their own enrolment
    $this->actingAs($student2)->get("/enrolments/{$e2}")->assertOk();
});

it('enforces separation of duties (D-15, FR-14, FR-15)', function () {
    $lecturer = findUser('Dr. Aina Zulkifli');
    $dean = findUser('Hakim Rahman');

    $ftmkId = DB::table('ptjs')->where('code', 'FTMK')->value('id');

    // Create a proposal owned by the Deputy Dean himself
    $mcId = DB::table('micro_credentials')->insertGetId([
        'code' => 'DEAN-01',
        'category' => 'umc',
        'origin' => 'unbundled',
        'ptj_id' => $ftmkId,
        'owner_id' => $dean->id, // Deputy Dean owns this!
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $verId = DB::table('mc_versions')->insertGetId([
        'micro_credential_id' => $mcId,
        'version_no' => 1,
        'name_en' => 'Dean Proposal',
        'status' => 'submitted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Deputy Dean cannot decide on or assign reviewers for their own proposal (FR-14)
    $this->actingAs($dean)->post("/approvals/{$verId}/endorse")->assertForbidden();
    $this->actingAs($dean)->post("/approvals/{$verId}/return", ['notes' => 'reason'])->assertForbidden();
    $this->actingAs($dean)->post("/approvals/{$verId}/reviewers", [
        'reviewers' => [$dean->id],
        'due_on' => today()->addDays(7)->toDateString(),
        'scope' => 'Review everything',
    ])->assertForbidden();

    // Now test proposal owned by Dr. Aina: Dean cannot assign Dr. Aina to review her own proposal (FR-15)
    $ainaMcId = DB::table('micro_credentials')->insertGetId([
        'code' => 'AINA-01',
        'category' => 'umc',
        'origin' => 'unbundled',
        'ptj_id' => $ftmkId,
        'owner_id' => $lecturer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $ainaVerId = DB::table('mc_versions')->insertGetId([
        'micro_credential_id' => $ainaMcId,
        'version_no' => 1,
        'name_en' => 'Aina Proposal',
        'status' => 'submitted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->actingAs($dean)->post("/approvals/{$ainaVerId}/reviewers", [
        'reviewers' => [$lecturer->id],
        'due_on' => today()->addDays(7)->toDateString(),
        'scope' => 'Review everything',
    ])->assertStatus(422);

    // Create an offering owned by the Deputy Dean
    $offId = DB::table('offerings')->insertGetId([
        'mc_version_id' => $verId,
        'cohort_name' => '2026/1',
        'status' => 'not_ready',
        'delivery_mode' => 'online',
        'capacity' => 30,
        'reg_opens_on' => today()->addWeek(),
        'reg_closes_on' => today()->addWeeks(3),
        'start_date' => today()->addMonth(),
        'end_date' => today()->addMonths(2),
        'created_by' => $dean->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Deputy Dean cannot confirm readiness of their own cohort (Decision D-15)
    $this->actingAs($dean)->post("/offerings/{$offId}/confirm")->assertForbidden();
});

it('prevents cross-role unintended redirects to forbidden pages upon persona signin', function () {
    $student = findUser('Amir Hakimi');
    $lecturer = findUser('Dr. Aina Zulkifli');

    // 1. Unauthenticated hit to a staff-only pathway sets url.intended
    $response = $this->get('/pathways/5');
    $response->assertRedirect('/signin');

    // 2. Student signs in via stub persona - MUST NOT redirect to forbidden /pathways/5
    $loginResponse = $this->post('/signin/stub', ['user' => $student->id]);
    $loginResponse->assertRedirect(route('tasks'));

    // Verify following the redirect lands cleanly on 200 OK without 403 error
    $this->get(route('tasks'))->assertOk();

    // 3. Lecturer signs in with intended staff pathway - CAN access /pathways/5
    $this->post('/signout');
    $this->get('/pathways/5')->assertRedirect('/signin');

    $lecLoginResponse = $this->post('/signin/stub', ['user' => $lecturer->id]);
    $lecLoginResponse->assertRedirect('http://localhost/pathways/5');
});

test('CODL admin and industry expert role home URLs route brand, error recovery, and task areas properly', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $admin = who('Farid Hashim');
    $expert = User::whereHas('roleAssignments', fn ($q) => $q->where('role', 'industry_expert'))->firstOrFail();

    expect($admin->homeUrl())->toBe(route('users'));
    expect($expert->homeUrl())->toBe(route('reviews'));

    $this->actingAs($admin)->get('/')->assertRedirect(route('users'));

    $this->actingAs($admin)->get(route('users'))
        ->assertOk()
        ->assertSee('href="' . route('users') . '"', false);

    $this->actingAs($admin)->get('/quality/2')
        ->assertForbidden()
        ->assertSee('Go to my work')
        ->assertSee('href="' . route('users') . '"', false);

    // Non-academic CODL view hides zero academic tabs and renders human-readable area labels
    $this->actingAs($admin)->get('/tasks')
        ->assertOk()
        ->assertDontSee('Proposals (0)')
        ->assertDontSee('Qv_monitoring');

    $lecturer = who('Dr. Aina Zulkifli');
    $ftmkId = \Illuminate\Support\Facades\DB::table('ptjs')->where('code', 'FTMK')->value('id');
    \Illuminate\Support\Facades\DB::table('tasks')->insert([
        'assignee_role' => 'lecturer',
        'user_id' => $lecturer->id,
        'ptj_id' => $ftmkId,
        'area' => 'qv_monitoring',
        'kind' => 'qv_renew',
        'link' => '/quality/2',
        'subject_ref' => 'QV-TEST-001',
        'title' => 'Prepare Quality Verification Renewal Test',
        'created_at' => now(),
    ]);

    $this->actingAs($lecturer)->get('/tasks')
        ->assertOk()
        ->assertSee('Quality Verification')
        ->assertDontSee('Qv_monitoring');
});

it('keeps authenticated students inside the secured portal when browsing offerings', function () {
    $student = who('Amir Hakimi');
    $lecturer = who('Dr. Aina Zulkifli');
    $dean = who('Hakim Rahman');
    $admin = who('Farid Hashim');
    $expert = who('Ir. Siti Aminah');
    $ftmkId = DB::table('ptjs')->where('code', 'FTMK')->value('id');

    $mcId = DB::table('micro_credentials')->insertGetId([
        'category' => 'umc',
        'origin' => 'unbundled',
        'ptj_id' => $ftmkId,
        'owner_id' => $lecturer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $verId = DB::table('mc_versions')->insertGetId([
        'micro_credential_id' => $mcId,
        'version_no' => 1,
        'proposal_type' => 'new',
        'status' => 'approved',
        'name_en' => 'Portal Security Cloud Micro-Credential',
        'name_ms' => 'Mikro-Kredensial Awan Selamat',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $offId = DB::table('offerings')->insertGetId([
        'mc_version_id' => $verId,
        'cohort_name' => '2026/SEC',
        'start_date' => today()->addDays(14)->toDateString(),
        'end_date' => today()->addDays(44)->toDateString(),
        'delivery_mode' => 'Online',
        'capacity' => 30,
        'reg_opens_on' => today()->subDays(2)->toDateString(),
        'reg_closes_on' => today()->addDays(10)->toDateString(),
        'fee' => 150,
        'status' => 'open',
        'readiness_state' => 'ready',
        'created_by' => $lecturer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Unauthenticated visitors are redirected away from authenticated portal catalogue/info routes
    $this->get('/catalogue')->assertRedirect('/signin');
    $this->get('/offering/' . $offId)->assertRedirect('/signin');
    $this->get('/how')->assertRedirect('/signin');
    $this->get('/faq')->assertRedirect('/signin');

    // Non-students cannot access student portal catalogue/offering routes
    foreach ([$lecturer, $dean, $admin, $expert] as $staff) {
        $this->actingAs($staff)->get('/catalogue')->assertForbidden();
        $this->actingAs($staff)->get('/offering/' . $offId)->assertForbidden();
    }

    // Authenticated student browsing /catalogue stays inside /offering/{id} (never leaking to /public/offering/{id})
    $this->actingAs($student)->get('/catalogue')
        ->assertOk()
        ->assertSee('Portal Security Cloud Micro-Credential')
        ->assertSee('href="' . route('offering', $offId) . '"', false)
        ->assertDontSee('/public/offering/' . $offId);

    $this->actingAs($student)->get('/offering/' . $offId)
        ->assertOk()
        ->assertSee('Micro-Credential Management')
        ->assertSee('href="' . route('catalogue') . '"', false)
        ->assertSee('href="' . route('register', $offId) . '"', false)
        ->assertDontSee('Public Verification & Credential Portal')
        ->assertDontSee('Return to portal');

    // Guests on /public/offering/{id} stay in /public/catalogue breadcrumbs and see Register
    $this->get('/public/offering/' . $offId)
        ->assertOk()
        ->assertSee('href="' . route('public.catalogue') . '"', false)
        ->assertSee('href="' . route('register', $offId) . '"', false);

    // Non-student staff previewing /public/offering/{id} do not get a broken Register link to a 403 endpoint
    $this->actingAs($lecturer)->get('/public/offering/' . $offId)
        ->assertOk()
        ->assertDontSee('href="' . route('register', $offId) . '"', false);
});

it('enforces active_role switches across RBAC middleware and task queues', function () {
    // Multi-role switching is supported between compatible roles (e.g. Student and Lecturer).
    // Deputy Dean and Lecturer roles are strictly mutually exclusive under Separation of Duties (D-52).
    $dual = who('Amir Hakimi');
    $ftmkId = DB::table('ptjs')->where('code', 'FTMK')->value('id');
    DB::table('role_assignments')->insertOrIgnore([
        'user_id' => $dual->id,
        'role' => 'lecturer',
        'scope_type' => 'ptj',
        'ptj_id' => $ftmkId,
        'created_at' => now(),
    ]);

    DB::table('tasks')->insert([
        [
            'assignee_role' => 'student',
            'user_id' => $dual->id,
            'ptj_id' => null,
            'area' => 'enrolment',
            'kind' => 'payment',
            'link' => '/enrolments',
            'subject_ref' => 'STU-ONLY-01',
            'title' => 'Student Enrolment Queue Task',
            'created_at' => now()->subYear(),
        ],
        [
            'assignee_role' => 'lecturer',
            'user_id' => $dual->id,
            'ptj_id' => $ftmkId,
            'area' => 'qv_monitoring',
            'kind' => 'qv_renew',
            'link' => null,
            'subject_ref' => 'LEC-ONLY-01',
            'title' => 'Lecturer QV Renewal Task',
            'created_at' => now()->subYear(),
        ],
    ]);

    // Switching from a student-only page to lecturer redirects cleanly to homeUrl() and blocks student routes
    $this->actingAs($dual)
        ->from('/enrolments')
        ->post('/role/switch', ['role' => 'lecturer'])
        ->assertRedirect('/tasks');

    $this->actingAs($dual)->withSession(['active_role' => 'lecturer'])
        ->get('/enrolments')->assertForbidden();
    $this->actingAs($dual)->withSession(['active_role' => 'lecturer'])
        ->get('/transfer')->assertForbidden();
    $this->actingAs($dual)->withSession(['active_role' => 'lecturer'])
        ->get('/proposals')->assertOk();

    // Tasks queue only exposes tasks belonging to the currently active role
    $this->actingAs($dual)->withSession(['active_role' => 'lecturer'])
        ->get('/tasks')
        ->assertOk()
        ->assertSee('Lecturer QV Renewal Task')
        ->assertDontSee('Student Enrolment Queue Task');

    // Opening a task whose area is only in AREA_LABELS (e.g. qv_monitoring) renders safely without key error
    $qvTaskId = DB::table('tasks')->where('subject_ref', 'LEC-ONLY-01')->value('id');
    $this->actingAs($dual)->withSession(['active_role' => 'lecturer'])
        ->get('/tasks/' . $qvTaskId)
        ->assertOk()
        ->assertSee('Quality Verification');

    // Switching back to student restores student access and hides lecturer-only tasks
    $this->actingAs($dual)->withSession(['active_role' => 'student'])
        ->get('/enrolments')->assertOk();
    $this->actingAs($dual)->withSession(['active_role' => 'student'])
        ->get('/proposals')->assertForbidden();
    $this->actingAs($dual)->withSession(['active_role' => 'student'])
        ->get('/tasks')
        ->assertOk()
        ->assertSee('Student Enrolment Queue Task')
        ->assertDontSee('Lecturer QV Renewal Task');

    // If all roles are revoked, no tasks leak through empty where groups
    DB::table('role_assignments')->where('user_id', $dual->id)->delete();
    $this->actingAs($dual)->get('/tasks')
        ->assertOk()
        ->assertDontSee('Student Enrolment Queue Task')
        ->assertDontSee('Lecturer QV Renewal Task');
});

it('enforces documents role access and PTj/owner scoping on documents, admissions, and lecturer audit', function () {
    $student = who('Amir Hakimi');
    $expert = who('Ir. Siti Aminah');
    $deanFtmk = who('Hakim Rahman');
    $lecOne = who('Dr. Aina Zulkifli');

    $ftmkId = DB::table('ptjs')->where('code', 'FTMK')->value('id');
    $ftkeId = DB::table('ptjs')->where('code', 'FTKE')->value('id');

    // Industry experts cannot access /documents and do not see "My documents" in header dropdown
    $this->actingAs($expert)->get('/documents')->assertForbidden();
    $this->actingAs($expert)->get('/reviews')->assertOk()->assertDontSee(route('documents'));

    // Second FTMK lecturer for horizontal owner-level audit scoping
    $lecTwoId = DB::table('users')->insertGetId([
        'name' => 'Dr. Peer FTMK Lecturer',
        'email' => 'peer.lecturer@utem.example.test',
        'institution_id' => 'UTEM-LEC-PEER',
        'status' => 'active',
        'locale' => 'en',
        'password' => bcrypt('secret'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $lecTwo = User::findOrFail($lecTwoId);
    DB::table('role_assignments')->insert([
        'user_id' => $lecTwo->id,
        'role' => 'lecturer',
        'scope_type' => 'ptj',
        'ptj_id' => $ftmkId,
        'created_at' => now(),
    ]);

    // PTj scoping on /documents (abc_batches & suspensions) and /transfers (admissions)
    $ftkeProgId = DB::table('programmes')->insertGetId([
        'code' => 'BEKG',
        'name' => 'Bachelor of Electrical Engineering',
        'mqf_level' => 6,
        'mode' => 'conventional',
        'accreditation_status' => 'accredited',
        'total_credits' => 120,
        'ptj_id' => $ftkeId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $ftmkProgId = DB::table('programmes')->where('ptj_id', $ftmkId)->value('id');

    DB::table('abc_batches')->insert([
        ['reference' => 'ABC-FTMK-SEC-01', 'ptj_id' => $ftmkId, 'format_version' => 'csv-draft-1', 'row_count' => 1, 'prepared_by' => $deanFtmk->id, 'created_at' => now()],
        ['reference' => 'ABC-FTKE-SEC-99', 'ptj_id' => $ftkeId, 'format_version' => 'csv-draft-1', 'row_count' => 1, 'prepared_by' => $deanFtmk->id, 'created_at' => now()],
    ]);

    $mcFtmk1 = DB::table('micro_credentials')->insertGetId([
        'category' => 'samc', 'origin' => 'own', 'ptj_id' => $ftmkId, 'owner_id' => $lecOne->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $vFtmk1 = DB::table('mc_versions')->insertGetId([
        'micro_credential_id' => $mcFtmk1, 'version_no' => 1, 'proposal_type' => 'new', 'status' => 'approved', 'name_en' => 'Own Lecturer MC One', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $mcFtmk2 = DB::table('micro_credentials')->insertGetId([
        'category' => 'samc', 'origin' => 'own', 'ptj_id' => $ftmkId, 'owner_id' => $lecTwo->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $vFtmk2 = DB::table('mc_versions')->insertGetId([
        'micro_credential_id' => $mcFtmk2, 'version_no' => 1, 'proposal_type' => 'new', 'status' => 'approved', 'name_en' => 'Peer Lecturer MC Two', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $mcFtke = DB::table('micro_credentials')->insertGetId([
        'category' => 'samc', 'origin' => 'own', 'ptj_id' => $ftkeId, 'owner_id' => $lecOne->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('mc_versions')->insert([
        'micro_credential_id' => $mcFtke, 'version_no' => 1, 'proposal_type' => 'new', 'status' => 'approved', 'name_en' => 'Other Faculty FTKE MC', 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('suspensions')->insert([
        ['micro_credential_id' => $mcFtmk1, 'kind' => 'suspension', 'reason' => 'FTMK hold', 'effective_from' => today()->toDateString(), 'offering_ids' => '[]', 'authority' => 'dean', 'decided_by' => $deanFtmk->id, 'created_at' => now(), 'updated_at' => now()],
        ['micro_credential_id' => $mcFtke, 'kind' => 'suspension', 'reason' => 'FTKE secret hold', 'effective_from' => today()->toDateString(), 'offering_ids' => '[]', 'authority' => 'dean', 'decided_by' => $deanFtmk->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('admissions')->insert([
        ['user_id' => $student->id, 'programme_id' => $ftmkProgId, 'intake' => '2026/FTMK-ADM', 'recorded_by' => $deanFtmk->id, 'recorded_at' => now()],
        ['user_id' => $student->id, 'programme_id' => $ftkeProgId, 'intake' => '2026/FTKE-ADM', 'recorded_by' => $deanFtmk->id, 'recorded_at' => now()],
    ]);

    $this->actingAs($deanFtmk)->get('/documents')
        ->assertOk()
        ->assertSee('Own Lecturer MC One')
        ->assertDontSee('Other Faculty FTKE MC');

    // Registry / ABC Depositor sees ABC deposit batches on /documents
    $this->actingAs(userNurul())->get('/documents')
        ->assertOk()
        ->assertSee('ABC-FTMK-SEC-01');

    $this->actingAs($deanFtmk)->get('/transfers')
        ->assertOk()
        ->assertSee('2026/FTMK-ADM')
        ->assertDontSee('2026/FTKE-ADM');

    // Lecturer only sees audit events for micro-credentials they own, while FTMK deputy dean sees both FTMK events
    $audit = app(\App\Modules\Records\AuditRecorder::class);
    $audit->record('proposal.submitted.own', ['type' => 'McVersion', 'id' => (string) $vFtmk1], actorId: $lecOne->id);
    $audit->record('proposal.submitted.peer', ['type' => 'McVersion', 'id' => (string) $vFtmk2], actorId: $lecTwo->id);

    $this->actingAs($lecOne)->get('/records/audit')
        ->assertOk()
        ->assertSee('proposal.submitted.own')
        ->assertDontSee('proposal.submitted.peer');

    $this->actingAs($deanFtmk)->get('/records/audit')
        ->assertOk()
        ->assertSee('proposal.submitted.own')
        ->assertSee('proposal.submitted.peer');
});

it('provides role-appropriate back links and breadcrumbs on achievement record view for lecturers and students', function () {
    [$o, , , $people] = runningCohort();
    $lecturer = who('Dr. Aina Zulkifli');
    $student = who('Amir Hakimi');

    $enrolmentId = DB::table('enrolments')
        ->where('offering_id', $o)
        ->where('user_id', $student->id)
        ->value('id');

    expect($enrolmentId)->not->toBeNull();

    // Lecturer viewing student record has back links to participants list, not student /my-record
    $this->actingAs($lecturer)->get("/records/{$enrolmentId}")
        ->assertOk()
        ->assertSee(route('offerings.participants', $o))
        ->assertDontSee(route('records.mine'));

    // Student viewing their own record has back links to /my-record
    $this->actingAs($student)->get("/records/{$enrolmentId}")
        ->assertOk()
        ->assertSee(route('records.mine'));
});

it('prevents higher roles auditing student data from being exposed to student-only routes', function () {
    $lecturer = who('Dr. Aina Zulkifli');
    $dean = who('Hakim Rahman');
    $student = who('Amir Hakimi');

    // 1. Notification link routing respects staff vs student roles
    expect(\App\Modules\Notifications\Notifier::linkFor('results.verified', $lecturer))
        ->toBe(route('offerings', [], false))
        ->and(\App\Modules\Notifications\Notifier::linkFor('results.verified', $student))
        ->toBe(route('results.mine', [], false));

    expect(\App\Modules\Notifications\Notifier::linkFor('credential.issued', $dean))
        ->toBe(route('credentials.admin', [], false))
        ->and(\App\Modules\Notifications\Notifier::linkFor('credential.issued', $student))
        ->toBe(route('credentials.wallet', [], false));

    expect(\App\Modules\Notifications\Notifier::linkFor('enrolment.confirmed', $lecturer))
        ->toBe(route('offerings', [], false))
        ->and(\App\Modules\Notifications\Notifier::linkFor('enrolment.confirmed', $student))
        ->toBe(route('enrolments', [], false));

    // 2. Deputy dean viewing transfer decision letter has back links to /transfers, not student /transfer
    $ftmkId = DB::table('ptjs')->where('code', 'FTMK')->value('id');
    $progId = DB::table('programmes')->where('ptj_id', $ftmkId)->value('id');
    $tId = DB::table('transfer_applications')->insertGetId([
        'reference' => 'TRF-TEST-RBAC-01',
        'user_id' => $student->id,
        'applied_by' => $student->id,
        'programme_id' => $progId,
        'status' => 'approved',
        'applied_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($dean)->get("/transfers/{$tId}/letter")
        ->assertOk()
        ->assertSee(route('transfers'))
        ->assertDontSee('href="'.route('transfer').'"', false);

    $this->actingAs($student)->get("/transfer/{$tId}")
        ->assertOk()
        ->assertSee('href="'.route('transfer').'"', false);
});
