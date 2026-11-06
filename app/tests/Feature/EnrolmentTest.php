<?php

use App\Models\User;
use App\Modules\Enrolment\Enrolments;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->siti = User::where('name', 'Ir. Siti Aminah')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

if (! function_exists('student')) {
function student(string $name = 'Student Two', string $group = 'working professional'): User
{
    $u = User::create(['name' => $name, 'email' => Str::slug($name).'@learner.example.test', 'participant_group' => $group]);
    $u->roleAssignments()->create(['role' => 'student']);

    return $u;
}
}


if (! function_exists('startRegistration')) {
function startRegistration(int $o, ?User $by = null, array $ack = ['ack_statement' => 1, 'pdpa' => 1]): \Illuminate\Testing\TestResponse
{
    $by ??= test()->amir;
    test()->actingAs($by)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1])->assertSessionHasNoErrors();

    return test()->actingAs($by)->post("/register/$o/acknowledge", $ack);
}
}


if (! function_exists('pay')) {
function pay(int $o, string $card = '4242 4242 4242 4242', ?string $attempt = null, ?User $by = null): \Illuminate\Testing\TestResponse
{
    return test()->actingAs($by ?? test()->amir)->post("/register/$o/pay", ['card' => $card, 'attempt' => $attempt ?? (string) Str::uuid()]);
}
}


if (! function_exists('enrolmentOf')) {
function enrolmentOf(int $o, ?User $by = null): object
{
    return DB::table('enrolments')->where('offering_id', $o)->where('user_id', ($by ?? test()->amir)->id)->first();
}
}


// ---- getting to the registration page

it('sends a visitor who chooses Register to sign in, and back to the registration afterwards', function () {
    [$o] = openCohort();
    $this->post('/signout');
    $this->flushSession();
    $this->get("/register/$o")->assertRedirect('/signin');
    $this->post('/signin/stub', ['user' => $this->amir->id])->assertRedirect("http://localhost/register/$o");
    $this->get("/public/offering/$o")->assertSee('Register');
});

it('shows a cohort the learner already has as Registered, not as a fresh registration', function () {
    [$o] = openCohort();
    $this->actingAs($this->amir)->get('/public/catalogue')->assertSee('Open for registration')->assertDontSee('>Registered<', false);
    startRegistration($o);
    $e = enrolmentOf($o);
    $this->actingAs($this->amir)->get('/public/catalogue')->assertSee('>Registered<', false);
    $this->actingAs($this->amir)->get("/public/offering/$o")->assertSee('View your registration')->assertSee("/enrolments/{$e->id}");
});

it('lets only a student register (FR-97)', function () {
    [$o] = openCohort();
    $this->actingAs($this->aina)->get("/register/$o")->assertForbidden();
    $this->actingAs(User::where('name', 'Farid Hashim')->first())->get("/register/$o")->assertForbidden();
});

// ---- eligibility (FR-97, FR-98)

it('checks the phone number and the prerequisites at the first step', function () {
    [$o] = openCohort();
    $this->actingAs($this->amir)->get("/register/$o")->assertSee('Step 1: eligibility')->assertSee('Basic programming');
    $this->actingAs($this->amir)->post("/register/$o/eligibility", ['phone' => 'abc', 'prerequisites_ok' => 1])->assertSessionHasErrors('phone');
    $this->actingAs($this->amir)->post("/register/$o/eligibility", ['phone' => '012-3456789'])->assertSessionHasErrors('prerequisites_ok');
    $this->actingAs($this->amir)->post("/register/$o/acknowledge", ['ack_statement' => 1, 'pdpa' => 1])->assertForbidden(); // the steps cannot be skipped
    expect(DB::table('enrolments')->count())->toBe(0);
});

it('explains why a user is not eligible (FR-98)', function () {
    [$o] = openCohort(['eligible_groups' => 'UTeM staff, alumni']);
    $this->actingAs($this->amir)->get("/register/$o")->assertSee('You cannot register')->assertSee('This cohort is for: UTeM staff, alumni. Your group is working professional.');
    $this->actingAs($this->amir)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1])->assertSessionHasErrors('eligibility');
    $alum = student('Alumna One', 'Alumni');
    $this->actingAs($alum)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1])->assertSessionHasNoErrors();
});

it('refuses a cohort whose registration is not open (FR-97)', function () {
    [$o] = openCohort(['reg_opens_on' => today()->addDays(5)->toDateString()]);
    $this->actingAs($this->amir)->get("/register/$o")->assertSee('Registration for this cohort is not open');
    $this->actingAs($this->amir)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1])->assertSessionHasErrors('eligibility');
});

// ---- acknowledgement (FR-99, FR-100)

it('stores the wording the participant acknowledged, in both languages, with its template versions (FR-99)', function () {
    [$o] = openCohort();
    startRegistration($o)->assertSessionHasNoErrors();
    $e = enrolmentOf($o);
    expect($e->ack_text)->toContain('Unbundled Micro-Credential (UMC) from BITS 1123 Data Structures')->toContain('Ini ialah Unbundled Micro-Credential')->toContain('separate decisions')
        ->and(json_decode($e->ack_versions, true))->toHaveKey('statement.separate_stages')->and($e->mc_version_id)->toBe(DB::table('offerings')->find($o)->mc_version_id);
});

it('needs both ticks the first time and only the statement afterwards (FR-100)', function () {
    [$o, $v1] = openCohort();
    $o2 = newCohort($v1, ['cohort_name' => '2027/2', 'start_date' => today()->addDays(120)->toDateString(), 'end_date' => today()->addDays(180)->toDateString()]);
    answerAll($o2);
    sendChecklist($o2);
    confirmReady($o2);
    $this->actingAs($this->amir)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1]);
    $this->actingAs($this->amir)->post("/register/$o/acknowledge", ['ack_statement' => 1])->assertSessionHasErrors('pdpa');
    $this->actingAs($this->amir)->post("/register/$o/acknowledge", ['pdpa' => 1])->assertSessionHasErrors('ack_statement');
    $this->actingAs($this->amir)->post("/register/$o/acknowledge", ['ack_statement' => 1, 'pdpa' => 1])->assertSessionHasNoErrors();
    expect(DB::table('pdpa_acknowledgements')->count())->toBe(1);

    $this->actingAs($this->amir)->post("/register/$o2/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1]);
    $this->actingAs($this->amir)->get("/register/$o2")->assertDontSee('personal data notice');
    $this->actingAs($this->amir)->post("/register/$o2/acknowledge", ['ack_statement' => 1])->assertSessionHasNoErrors();
    expect(DB::table('pdpa_acknowledgements')->count())->toBe(1);
});

// ---- a free cohort (FR-103, FR-104, FR-109)

it('enrols at once in a free cohort, with modules, a confirmation and an attributed history (FR-103, FR-104, FR-109)', function () {
    [$o] = openCohort();
    startRegistration($o);
    $e = enrolmentOf($o);
    expect($e->status)->toBe('enrolled')->and($e->reference)->toStartWith('E-')
        ->and(DB::table('module_enrolments')->where('enrolment_id', $e->id)->count())->toBe(DB::table('mc_modules')->where('mc_version_id', $e->mc_version_id)->count())
        ->and(DB::table('enrolment_status_log')->where('enrolment_id', $e->id)->orderBy('id')->pluck('to_status')->all())->toBe(['registered', 'enrolled'])
        ->and(DB::table('enrolment_status_log')->where('enrolment_id', $e->id)->pluck('changed_by')->unique()->all())->toBe([$this->amir->id])
        ->and(DB::table('audit_events')->whereIn('action', ['student.registered', 'enrolment.confirmed'])->count())->toBe(2)
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'enrolment.confirmed')->where('channel', 'in_system')->value('body'))->toContain($e->reference)->toContain('Open the learning platform course');
    $this->actingAs($this->amir)->get("/register/$o")->assertSee('You are enrolled')->assertSee($e->reference);
});

// ---- a paid cohort and the dummy payment (FR-101, FL-06)

it('holds the place as Pending payment, then enrols after a successful payment (FR-101, FL-06)', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    $e = enrolmentOf($o);
    expect($e->status)->toBe('pending_payment')->and((float) $e->fee_amount)->toBe(150.0)->and(app(Enrolments::class)->taken($o))->toBe(1)
        ->and(DB::table('notifications')->where('template_key', 'enrolment.registered')->where('user_id', $this->amir->id)->exists())->toBeTrue();
    $this->actingAs($this->amir)->get("/register/$o")->assertSee('Step 3: payment')->assertSee('RM 150.00');

    pay($o)->assertSessionHasNoErrors()->assertSessionHas('status', 'Payment received. You are enrolled.');
    $e = enrolmentOf($o);
    $p = DB::table('payments')->first();
    expect($e->status)->toBe('enrolled')->and($p->status)->toBe('succeeded')->and($p->card_last4)->toBe('4242')->and($p->provider_ref)->toStartWith('DUMMY-')->and((float) $p->amount)->toBe(150.0)
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->whereIn('template_key', ['payment.confirmed', 'enrolment.confirmed'])->where('channel', 'in_system')->count())->toBe(2)
        ->and(DB::table('audit_events')->where('action', 'payment.confirmed')->exists())->toBeTrue()
        ->and(DB::table('integration_calls')->where('adapter', 'payment')->value('status'))->toBe('ok');
    $this->actingAs($this->amir)->get("/enrolments/{$e->id}/receipt")->assertSee('Receipt')->assertSee('RM 150.00')->assertSee('card ending 4242')->assertSee($p->provider_ref);
    $this->actingAs($this->amir)->get('/enrolments')->assertSee($e->reference)->assertSee('Enrolled')->assertSee('Receipt');
});

it('keeps the registration pending when the card is declined, then enrols after a retry, with one successful payment (FL-06b)', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    $r = pay($o, '4000 0000 0000 0002');
    $r->assertSessionHasErrors('payment');
    expect(session('errors')->first('payment'))->toContain('Payment declined')->toContain('PAY-')->toContain('nothing was charged');
    expect(enrolmentOf($o)->status)->toBe('pending_payment')->and(DB::table('payments')->where('status', 'succeeded')->count())->toBe(0)->and(DB::table('payments')->where('status', 'failed')->count())->toBe(1)
        ->and(DB::table('integration_calls')->where('adapter', 'payment')->value('last_error'))->toBe('The card was declined.');
    $this->actingAs($this->amir)->get("/register/$o")->assertSee('Retry payment')->assertSee('Step 3: payment');

    pay($o, '4242 4242 4242 4242')->assertSessionHas('status');
    expect(enrolmentOf($o)->status)->toBe('enrolled')->and(DB::table('payments')->where('status', 'succeeded')->count())->toBe(1)->and(DB::table('payments')->count())->toBe(2)
        ->and(DB::table('audit_events')->where('action', 'payment.failed')->exists())->toBeTrue();
});

it('never charges the same attempt twice, or an enrolment that is already paid (IR-17)', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    $attempt = (string) Str::uuid();
    pay($o, attempt: $attempt);
    pay($o, attempt: $attempt); // the form sent twice
    pay($o); // a new attempt on an enrolled registration
    expect(DB::table('payments')->count())->toBe(1)->and(DB::table('audit_events')->where('action', 'payment.confirmed')->count())->toBe(1);
});

it('does not keep the card number anywhere (SR-14)', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    pay($o, '4000 0000 0000 0002');
    pay($o, '4242 4242 4242 4242');
    foreach (['payments', 'integration_calls', 'audit_events', 'enrolments', 'notifications'] as $table) {
        $dump = json_encode(DB::table($table)->get());
        expect($dump)->not->toContain('4242424242424242')->not->toContain('4242 4242 4242 4242')->not->toContain('4000000000000002')->not->toContain('4000 0000 0000 0002');
    }
});

it('validates the card number and shows the payment on the adapters page (FR-25)', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    pay($o, 'not a card')->assertSessionHasErrors('card');
    pay($o, '12')->assertSessionHasErrors('card');
    pay($o, '4000 0000 0000 0002');
    $this->actingAs(User::where('name', 'Farid Hashim')->first())->get('/admin/adapters')->assertSee('Payment service')->assertSee('The card was declined.');
});

// ---- places (FR-97)

it('counts a place from Registered and refuses a registration when the cohort is full (FR-97)', function () {
    [$o] = openCohort(['capacity' => 1, 'fee' => '150']);
    startRegistration($o);
    $other = student();
    $this->actingAs($other)->get("/register/$o")->assertSee('The cohort is full');
    $this->actingAs($other)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1])->assertSessionHasErrors('eligibility');
});

it('stops a second registration by the same person', function () {
    [$o] = openCohort();
    startRegistration($o);
    $this->actingAs($this->amir)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1]);
    $this->actingAs($this->amir)->post("/register/$o/acknowledge", ['ack_statement' => 1, 'pdpa' => 1])->assertForbidden();
    expect(DB::table('enrolments')->count())->toBe(1);
});

it('releases the place of an unpaid registration when the payment window ends, and tells the participant (JB-20)', function () {
    [$o] = openCohort(['capacity' => 1, 'fee' => '150']);
    startRegistration($o);
    $this->travelTo(now()->addHours(47));
    expect(app(Enrolments::class)->cancelUnpaid())->toBe(0);
    $this->travelTo(now()->addHours(2));
    expect(app(Enrolments::class)->cancelUnpaid())->toBe(1)->and(enrolmentOf($o)->status)->toBe('cancelled')->and(app(Enrolments::class)->taken($o))->toBe(0)
        ->and(DB::table('notifications')->where('template_key', 'enrolment.cancelled')->where('user_id', $this->amir->id)->exists())->toBeTrue()
        ->and(DB::table('audit_events')->where('action', 'enrolment.cancelled')->exists())->toBeTrue();
    $this->flushSession();
    $this->actingAs(student())->get("/register/$o")->assertDontSee('The cohort is full');
});

it('follows the payment window setting (FR-20)', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    app(\App\Modules\Core\Settings::class)->set('enrolment.payment_window_hours', '2', 'Shorter window for the test', User::where('name', 'Farid Hashim')->first());
    $this->travelTo(now()->addHours(3));
    expect(app(Enrolments::class)->cancelUnpaid())->toBe(1);
});

// ---- withdrawal (FR-105)

it('lets an enrolled participant withdraw before the deadline, with a reason, and frees the place (FR-105)', function () {
    [$o] = openCohort(['capacity' => 1]);
    startRegistration($o);
    $e = enrolmentOf($o);
    $this->actingAs($this->amir)->get("/enrolments/{$e->id}")->assertSee('You can withdraw until');
    $this->actingAs($this->amir)->post("/enrolments/{$e->id}/withdraw", ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->amir)->post("/enrolments/{$e->id}/withdraw", ['reason' => 'Changed jobs'])->assertSessionHas('status');
    expect(enrolmentOf($o)->status)->toBe('withdrawn')->and(enrolmentOf($o)->withdraw_reason)->toBe('Changed jobs')->and(app(Enrolments::class)->taken($o))->toBe(0)
        ->and(DB::table('audit_events')->where('action', 'enrolment.withdrawn')->exists())->toBeTrue()
        ->and(DB::table('notifications')->where('template_key', 'enrolment.withdrawn')->where('user_id', $this->amir->id)->exists())->toBeTrue();
    $this->actingAs($this->amir)->post("/enrolments/{$e->id}/withdraw", ['reason' => 'Again please'])->assertForbidden();
    $this->actingAs($this->amir)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1])->assertSessionHasNoErrors(); // may register again
});

it('refuses withdrawal after the deadline, and for a registration that is not enrolled', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    $e = enrolmentOf($o);
    $this->actingAs($this->amir)->post("/enrolments/{$e->id}/withdraw", ['reason' => 'Not paid yet'])->assertForbidden(); // pending payment
    pay($o);
    $this->travelTo(now()->addDays(75)); // the cohort ends in 90 days; the deadline is 20 days before
    $this->flushSession();
    $this->actingAs($this->amir)->post("/enrolments/{$e->id}/withdraw", ['reason' => 'Too late now'])->assertForbidden();
    $this->actingAs($this->amir)->get("/enrolments/{$e->id}")->assertSee('withdrawal deadline')->assertSee('has passed');
});

it('keeps one participant out of another one\'s registration and receipt', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    pay($o);
    $e = enrolmentOf($o);
    $other = student();
    $this->actingAs($other)->get("/enrolments/{$e->id}")->assertForbidden();
    $this->actingAs($other)->get("/enrolments/{$e->id}/receipt")->assertForbidden();
    $this->actingAs($other)->post("/enrolments/{$e->id}/withdraw", ['reason' => 'Not mine'])->assertForbidden();
});

it('shows No payment received on the receipt of an unpaid registration', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    $this->actingAs($this->amir)->get('/enrolments/'.enrolmentOf($o)->id.'/receipt')->assertSee('No payment received');
});

// ---- the cohort list (FR-108) and cancelling a cohort

it('lists the participants of a cohort for the lecturer and the deputy dean, filtered and exported (FR-108)', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    $two = student('Student Two');
    startRegistration($o, $two);
    pay($o);
    $this->actingAs($this->aina)->get("/offerings/$o/participants")->assertSee('Amir Hakimi')->assertSee('Student Two')->assertSee('2 of 30 places taken');
    $this->actingAs($this->hakim)->get("/offerings/$o/participants?status=enrolled")->assertSee('Amir Hakimi')->assertDontSee('Student Two');
    $csv = $this->actingAs($this->aina)->get("/offerings/$o/participants?export=csv");
    $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($csv->streamedContent())->toContain('Registration,Name,Email,Status,Registered')->toContain('Amir Hakimi')->toContain('Pending payment');
    expect(DB::table('audit_events')->where('action', 'participants.exported')->exists())->toBeTrue();
    $this->actingAs($this->lim)->get("/offerings/$o/participants")->assertNotFound(); // not the owner of this micro-credential: it does not show
    $this->actingAs($this->amir)->get("/offerings/$o/participants")->assertForbidden();
});

it('cancels the registrations and tells the participants when the cohort is cancelled', function () {
    [$o] = openCohort(['fee' => '150']);
    startRegistration($o);
    pay($o);
    $this->actingAs($this->hakim)->post("/offerings/$o/cancel", ['reason' => 'Too few applicants'])->assertSessionHasNoErrors();
    expect(enrolmentOf($o)->status)->toBe('cancelled')->and(DB::table('notifications')->where('template_key', 'enrolment.cancelled')->where('user_id', $this->amir->id)->where('channel', 'in_system')->value('body'))->toContain('cohort was cancelled');
});

// ---- an approved change reaches enrolled participants (FR-80, FR-81)

it('rebinds enrolments to the new version and tells the participants when the rule is to move (FR-80, FR-81)', function () {
    [$o, $v1, $mc] = openCohort();
    startRegistration($o);
    expect(enrolmentOf($o)->mc_version_id)->toBe($v1);
    requestChange($mc, ['outcomes']);
    $v2 = v2Of($mc)->id;
    approveV2($v2, 'move_new');
    expect(enrolmentOf($o)->mc_version_id)->toBe($v2)
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'version.changed')->where('channel', 'in_system')->value('body'))->toContain('version 2')->toContain('move to the new version')
        ->and(DB::table('audit_events')->where('action', 'transition.applied')->exists())->toBeTrue();
});

it('keeps participants on the old version when the rule is to finish on it, and still tells them (FR-80)', function () {
    [$o, $v1, $mc] = openCohort();
    startRegistration($o);
    requestChange($mc, ['outcomes']);
    approveV2(v2Of($mc)->id, 'finish_old');
    expect(enrolmentOf($o)->mc_version_id)->toBe($v1)->and(DB::table('notifications')->where('template_key', 'version.changed')->where('user_id', $this->amir->id)->exists())->toBeTrue();
});
