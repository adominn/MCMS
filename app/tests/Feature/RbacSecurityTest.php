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
    $this->actingAs($dean)->get('/deposits')->assertOk();
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

    // Zero academic decision authority: Forbidden (403)
    $this->actingAs($admin)->get('/proposals')->assertForbidden();
    $this->actingAs($admin)->get('/micro-credentials')->assertForbidden();
    $this->actingAs($admin)->get('/approvals')->assertForbidden();
    $this->actingAs($admin)->get('/results')->assertForbidden();
    $this->actingAs($admin)->get('/credentials')->assertForbidden();
    $this->actingAs($admin)->get('/deposits')->assertForbidden();
    $this->actingAs($admin)->get('/transfers')->assertForbidden();
    $this->actingAs($admin)->get('/suspensions')->assertForbidden();
    $this->actingAs($admin)->get('/enrolments')->assertForbidden();
    $this->actingAs($admin)->get('/my-credit')->assertForbidden();
});

it('verifies industry expert role access and restrictions', function () {
    $expert = findUser('Ir. Siti Aminah');

    // Industry expert home redirects to reviews (matching prototype)
    $this->actingAs($expert)->get('/tasks')->assertRedirect('/reviews');
    $this->actingAs($expert)->get('/help')->assertOk()->assertSee('Review invitations');
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
