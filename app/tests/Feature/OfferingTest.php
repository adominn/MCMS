<?php

use App\Models\User;
use App\Modules\Offering\ForbiddenClaims;
use App\Modules\Offering\OfferingStatus;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->siti = User::where('name', 'Ir. Siti Aminah')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

if (! function_exists('cohort')) {
function cohort(int $version, array $over = []): array
{
    return $over + ['mc_version_id' => $version, 'cohort_name' => '2027/1', 'start_date' => today()->addDays(30)->toDateString(), 'end_date' => today()->addDays(90)->toDateString(), 'delivery_mode' => 'online', 'capacity' => 30,
        'reg_opens_on' => today()->toDateString(), 'reg_closes_on' => today()->addDays(20)->toDateString(), 'fee' => '0', 'facilitators' => 'Dr. Aina', 'description' => 'Hands-on labs and weekly clinics.', 'prerequisites' => 'Basic programming'];
}
}


if (! function_exists('newCohort')) {
function newCohort(int $version, array $over = []): int
{
    test()->actingAs(test()->aina)->post('/offerings', cohort($version, $over))->assertSessionHasNoErrors();

    return (int) DB::table('offerings')->max('id');
}
}


if (! function_exists('answerAll')) {
function answerAll(int $o, string $status = 'yes'): void
{
    $items = [];
    foreach (array_keys(config('offerings.readiness')) as $k) {
        $items[$k] = ['status' => $status, 'note' => "Evidence for $k"];
    }
    test()->actingAs(test()->aina)->post("/offerings/$o/readiness", ['item' => $items, 'action' => 'save'])->assertSessionHasNoErrors();
}
}


if (! function_exists('sendChecklist')) {
function sendChecklist(int $o, string $outcome = 'ready', ?string $conditions = null): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(test()->aina)->post("/offerings/$o/readiness", ['action' => 'send', 'outcome' => $outcome, 'conditions' => $conditions]);
}
}


if (! function_exists('confirmReady')) {
function confirmReady(int $o, string $outcome = 'ready', ?string $conditions = null, bool $accept = true): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(test()->hakim)->post("/offerings/$o/confirm", ['outcome' => $outcome, 'conditions' => $conditions, 'accept' => $accept ? array_fill_keys(array_keys(config('offerings.readiness')), 1) : []]);
}
}


/** An approved UMC with an open cohort. */
if (! function_exists('openCohort')) {
function openCohort(array $over = []): array
{
    [$v1, $mc] = approvedV1();
    $o = newCohort($v1, $over);
    answerAll($o);
    sendChecklist($o)->assertSessionHasNoErrors();
    confirmReady($o)->assertSessionHasNoErrors();

    return [$o, $v1, $mc];
}
}


// ---- creating an offering (FR-83, FR-84)

it('creates a cohort only for an approved version, inside scope, with its readiness checklist (FR-83, FR-84)', function () {
    [$v1] = approvedV1();
    $o = newCohort($v1);
    expect(DB::table('offerings')->find($o)->status)->toBe('not_ready')
        ->and(DB::table('readiness_items')->where('offering_id', $o)->count())->toBe(count(config('offerings.readiness')))
        ->and(DB::table('audit_events')->where('action', 'offering.created')->exists())->toBeTrue();

    $draft = completeUmc();
    $this->actingAs($this->aina)->post('/offerings', cohort($draft))->assertForbidden(); // not approved
    $this->actingAs($this->lim)->post('/offerings', cohort($v1))->assertForbidden(); // not the owner, not a deputy dean
    $this->actingAs(User::where('name', 'Amir Hakimi')->first())->get('/offerings')->assertForbidden();
    $this->actingAs(User::where('name', 'Farid Hashim')->first())->get('/offerings')->assertForbidden();
});

it('lets a deputy dean of the PTj create a cohort, and refuses one of another PTj (FR-83)', function () {
    [$v1] = approvedV1();
    $this->actingAs($this->hakim)->post('/offerings', cohort($v1))->assertSessionHasNoErrors();
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->post('/offerings', cohort($v1, ['cohort_name' => '2027/2']))->assertForbidden();
});

it('checks the dates and numbers of a cohort', function () {
    [$v1] = approvedV1();
    $this->actingAs($this->aina);
    $this->post('/offerings', cohort($v1, ['end_date' => today()->addDays(10)->toDateString()]))->assertSessionHasErrors('end_date');
    $this->post('/offerings', cohort($v1, ['reg_closes_on' => today()->addDays(40)->toDateString()]))->assertSessionHasErrors('reg_closes_on');
    $this->post('/offerings', cohort($v1, ['start_date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('start_date');
    $this->post('/offerings', cohort($v1, ['capacity' => 0]))->assertSessionHasErrors('capacity');
    expect(DB::table('offerings')->count())->toBe(0);
});

it('refuses free text that promises credit, transfer or a degree (FR-93)', function (string $text) {
    expect(ForbiddenClaims::scan($text))->not->toBeEmpty();
})->with([
    'We guarantee credit transfer to your degree', 'This course will be transferred to the programme', 'Gives automatic credit toward a degree', 'It leads to a degree in computing',
    'Three micro-credentials make a degree', 'Earn 3 credits', 'Rated MQF level 6', 'A Quality-Verified course',
    'Kami memberi jaminan pemindahan kredit', 'Mendapat kredit automatik ke ijazah', 'Membawa kepada ijazah sarjana muda', 'Memperoleh 3 kredit', 'Tahap MQF 6', 'Kursus yang disahkan kualiti',
]);

it('accepts honest free text', function () {
    expect(ForbiddenClaims::scan('Hands-on labs, weekly clinics and a final project. Ask the programme about recognition.'))->toBeEmpty();
});

it('blocks a cohort whose text carries a forbidden claim, naming the field (FR-93)', function () {
    [$v1] = approvedV1();
    $this->actingAs($this->aina)->post('/offerings', cohort($v1, ['description' => 'We guarantee credit transfer.']))->assertSessionHasErrors('description');
    $this->actingAs($this->aina)->post('/offerings', cohort($v1, ['prerequisites' => 'Earn 3 credits first']))->assertSessionHasErrors('prerequisites');
    expect(DB::table('offerings')->count())->toBe(0);
});

// ---- readiness (FR-85 to FR-88)

it('needs the checklist sent before the deputy dean can confirm, and every item answered for Ready (FR-86)', function () {
    [$v1] = approvedV1();
    $o = newCohort($v1);
    confirmReady($o)->assertForbidden(); // not sent yet
    sendChecklist($o)->assertSessionHasErrors('outcome'); // items still "Not yet"
    answerAll($o);
    sendChecklist($o)->assertSessionHasNoErrors();
    expect(DB::table('tasks')->where('kind', 'readiness')->where('ref_id', $o)->whereNull('completed_at')->exists())->toBeTrue()
        ->and(DB::table('audit_events')->where('action', 'readiness.sent')->exists())->toBeTrue();
});

it('needs the evidence of every Yes item accepted before Ready (FR-86)', function () {
    [$v1] = approvedV1();
    $o = newCohort($v1);
    answerAll($o);
    sendChecklist($o);
    confirmReady($o, accept: false)->assertSessionHasErrors('outcome');
    expect(DB::table('offerings')->find($o)->readiness_state)->toBe('pending');
    confirmReady($o)->assertSessionHasNoErrors();
    expect(DB::table('offerings')->find($o)->readiness_state)->toBe('ready');
});

it('records Ready with conditions only with the conditions, and Not ready keeps registration closed (FR-87)', function () {
    [$v1] = approvedV1();
    $o = newCohort($v1);
    answerAll($o, 'not_yet');
    sendChecklist($o, 'ready_conditions', 'Facilitator evidence to follow')->assertSessionHasNoErrors();
    confirmReady($o, 'ready_conditions', '', accept: false)->assertSessionHasErrors('conditions');
    confirmReady($o, 'ready_conditions', 'Facilitator evidence by 1 December', accept: false)->assertSessionHasNoErrors();
    expect(DB::table('offerings')->find($o)->status)->toBe('open')
        ->and(DB::table('readiness_confirmations')->where('offering_id', $o)->orderBy('id')->pluck('role')->all())->toBe(['lecturer', 'ptj']);
    $o2 = newCohort($v1, ['cohort_name' => '2027/2']);
    answerAll($o2);
    sendChecklist($o2);
    confirmReady($o2, 'not_ready')->assertSessionHasNoErrors();
    expect(DB::table('offerings')->find($o2)->status)->toBe('not_ready');
    $this->get("/public/offering/$o2")->assertNotFound();
});

it('keeps the lecturer and the deputy dean apart: the lecturer cannot confirm, a deputy dean cannot confirm their own cohort (D-15)', function () {
    [$v1] = approvedV1();
    $o = newCohort($v1);
    answerAll($o);
    sendChecklist($o);
    $this->actingAs($this->aina)->post("/offerings/$o/confirm", ['outcome' => 'ready'])->assertForbidden();
    DB::table('offerings')->where('id', $o)->update(['created_by' => $this->hakim->id]);
    $this->aina->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
    $this->actingAs($this->aina)->post("/offerings/$o/confirm", ['outcome' => 'ready'])->assertForbidden(); // owner of the micro-credential
});

it('withdraws acceptance when the lecturer changes an item, tells the lecturer the result, and closes the task (FR-85)', function () {
    [$v1] = approvedV1();
    $o = newCohort($v1);
    answerAll($o);
    sendChecklist($o);
    $this->actingAs($this->hakim)->post("/offerings/$o/confirm", ['outcome' => 'not_ready', 'accept' => ['info_eligibility' => 1]]);
    expect((bool) DB::table('readiness_items')->where('offering_id', $o)->where('item', 'info_eligibility')->value('accepted'))->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'readiness')->whereNull('completed_at')->exists())->toBeFalse()
        ->and(DB::table('notifications')->where('user_id', $this->aina->id)->where('template_key', 'decision.made')->where('channel', 'in_system')->orderByDesc('id')->value('body'))->toContain('Readiness: Not ready');
    $this->actingAs($this->aina)->post("/offerings/$o/readiness", ['item' => ['info_eligibility' => ['status' => 'yes', 'note' => 'Updated evidence']], 'action' => 'save']);
    expect((bool) DB::table('readiness_items')->where('offering_id', $o)->where('item', 'info_eligibility')->value('accepted'))->toBeFalse();
});

it('pre-fills the checklist of a new cohort from the last one while resetting evidence acceptance (FR-88)', function () {
    [$o, $v1] = openCohort();
    $second = newCohort($v1, ['cohort_name' => '2027/2', 'start_date' => today()->addDays(120)->toDateString(), 'end_date' => today()->addDays(180)->toDateString(), 'reg_opens_on' => today()->addDays(60)->toDateString(), 'reg_closes_on' => today()->addDays(100)->toDateString()]);
    $item = DB::table('readiness_items')->where('offering_id', $second)->where('item', 'protect_changes')->first();
    expect($item->status)->toBe('yes')->and($item->note)->toBe('Evidence for protect_changes')->and((bool) $item->accepted)->toBeFalse()
        ->and(DB::table('offerings')->find($second)->status)->toBe('not_ready'); // still needs its own confirmation
});

it('renders plain-language offering readiness printout with automated checks, 4 sections, and important notice', function () {
    [$o, $v1] = openCohort();
    $this->actingAs($this->hakim)->get("/offerings/$o")
        ->assertOk()
        ->assertSee('Faculty / Centre')
        ->assertSee('Quality Verification Status')
        ->assertSee('Participant & Achievement Record Governance')
        ->assertSee('UTeM Identity & Learner Profile')
        ->assertSee('MCMS Verified Completion Engine')
        ->assertSee('significant changes to learning outcomes');

    $res = $this->actingAs($this->hakim)->get("/offerings/$o/readiness?print=1");
    $res->assertOk()
        ->assertDontSee('Appendix B')
        ->assertDontSee('Lampiran B')
        ->assertSee('Offering Operational Readiness Report')
        ->assertSee('Automated System Checks')
        ->assertSee('Course Information & Support')
        ->assertSee('Learning Materials & Platform')
        ->assertSee('Assessment & Academic Integrity')
        ->assertSee('Learner Protection & Contingency')
        ->assertSee('Important Notice')
        ->assertSee('significant changes to learning outcomes');

    // Support party (P5) confirmation recording
    $this->actingAs($this->farid)->post("/offerings/$o/confirm", ['outcome' => 'ready', 'conditions' => 'Platform verified'])->assertSessionHasNoErrors();
    expect(DB::table('readiness_confirmations')->where('offering_id', $o)->where('role', 'support')->exists())->toBeTrue();
});

// ---- status by date (BR-17, JB-21)

it('opens registration on its date, then runs and ends the cohort by date (BR-17)', function () {
    [$o] = openCohort(['reg_opens_on' => today()->addDays(5)->toDateString()]);
    expect(DB::table('offerings')->find($o)->status)->toBe('ready'); // confirmed, but the registration date has not come
    $this->get('/public/catalogue')->assertSee('Registration opens');
    $this->travelTo(now()->addDays(6)); $this->flushSession();
    app(OfferingStatus::class)->refresh();
    expect(DB::table('offerings')->find($o)->status)->toBe('open')->and(DB::table('audit_events')->where('action', 'offering.opened')->exists())->toBeTrue();
    $this->travelTo(now()->addDays(15)); $this->flushSession(); // registration closed, start not reached
    $this->get('/public/catalogue')->assertSee('Registration closed');
    $this->travelTo(now()->addDays(10)); $this->flushSession();
    app(OfferingStatus::class)->refresh();
    expect(DB::table('offerings')->find($o)->status)->toBe('running');
    $this->get("/public/offering/$o")->assertNotFound();
    $this->travelTo(now()->addDays(80)); $this->flushSession();
    app(OfferingStatus::class)->refresh();
    expect(DB::table('offerings')->find($o)->status)->toBe('ended');
});

it('holds a cohort when a material change needs approval, and lifts the hold when it is decided (FR-88, EV-07)', function () {
    [$o, $v1, $mc] = openCohort();
    requestChange($mc, ['outcomes'])->assertRedirect();
    expect(DB::table('offerings')->find($o)->status)->toBe('held')->and(DB::table('audit_events')->where('action', 'offering.held')->exists())->toBeTrue();
    $this->get("/public/offering/$o")->assertNotFound();
    $this->actingAs($this->aina)->get("/offerings/$o")->assertSee('On hold');
    approveV2(v2Of($mc)->id);
    expect(DB::table('offerings')->find($o)->status)->toBe('open');
    $this->get("/public/offering/$o")->assertOk();
});

it('lifts the hold when the new version is not supported', function () {
    [$o, $v1, $mc] = openCohort();
    requestChange($mc, ['outcomes']);
    $v2 = v2Of($mc)->id;
    $this->actingAs($this->aina)->post("/proposals/$v2/submit", ['ip_confirmed' => 1]);
    $this->actingAs($this->hakim)->post("/approvals/$v2/reject", ['reason' => 'Out of scope']);
    expect(DB::table('offerings')->find($o)->status)->toBe('open');
});

it('lets the deputy dean cancel a cohort with a reason, and removes it from the catalogue', function () {
    [$o] = openCohort();
    $this->actingAs($this->hakim)->post("/offerings/$o/cancel", ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->hakim)->post("/offerings/$o/cancel", ['reason' => 'Too few applicants expected'])->assertSessionHasNoErrors();
    expect(DB::table('offerings')->find($o)->status)->toBe('cancelled');
    $this->get("/public/offering/$o")->assertNotFound();
    $this->get('/public/catalogue')->assertDontSee('Software Testing');
});

// ---- the public catalogue (FR-90 to FR-96)

it('shows an open cohort in the catalogue with no login (FR-90)', function () {
    [$o] = openCohort();
    $this->get('/public/catalogue')->assertOk()->assertSee('Software Testing')->assertSee('2027/1')->assertSee('Open for registration')->assertSee('Free');
    $this->get('/public')->assertSee('1 offerings open or upcoming');
});

it('shows what FR-91 asks for on the offering page', function () {
    [$o] = openCohort();
    $this->get("/public/offering/$o")->assertOk()
        ->assertSee('Learning outcomes')->assertSee('Test software')->assertSee('Modules')->assertSee('120 h')->assertSee('Online')->assertSee('Basic programming')
        ->assertSee('Project (100%)')->assertSee('Credential you receive')->assertSee('Places')->assertSee('Completion in stages');
});

it('generates the recognition statement from the template and shows UMC credit from the parent course (FR-93, FR-94)', function () {
    [$o] = openCohort();
    $this->get("/public/offering/$o")->assertSee('Unbundled Micro-Credential (UMC) from BITS 1123 Data Structures')->assertSee('Credit:', false)->assertSee('3')
        ->assertSee('separate decisions'); // FR-94
});

function approvedOwn(int $slt, array $over = []): int
{
    $v = newOwn($slt);
    $mcId = (int) DB::table('mc_versions')->where('id', $v)->value('micro_credential_id');
    DB::table('micro_credentials')->where('id', $mcId)->update(['category_status' => 'confirmed']);
    DB::table('mc_versions')->where('id', $v)->update([
        'name_ms' => $over['name_ms'] ?? 'Kursus Mikro Kendiri',
        'need' => $over['need'] ?? 'Industry upskilling need',
        'delivery_mode' => $over['delivery_mode'] ?? 'online',
        'delivery_language' => $over['delivery_language'] ?? 'English',
        'ip_confirmed' => 1,
        'status' => 'approved',
    ] + $over);
    modules($v, [$slt]);
    $modId = (int) DB::table('mc_modules')->where('mc_version_id', $v)->value('id');
    $loId = DB::table('learning_outcomes')->insertGetId([
        'mc_version_id' => $v,
        'code' => 'LO1',
        'text_en' => 'Apply core concepts',
        'text_ms' => 'Mengaplikasikan konsep teras',
        'sort_order' => 1,
    ]);
    $compId = DB::table('assessment_components')->insertGetId([
        'mc_version_id' => $v,
        'mc_module_id' => $modId,
        'name_en' => 'Final Assessment',
        'weight_percent' => 100,
    ]);
    DB::table('assessment_component_outcomes')->insert([
        'assessment_component_id' => $compId,
        'learning_outcome_id' => $loId,
    ]);

    return $v;
}

it('shows a SAMC with no credit, MQF or Quality Verification claim without a valid record (FR-89, D-17)', function () {
    $v = approvedOwn(80, ['proposed_credit' => 2.0, 'proposed_mqf_level' => 4, 'qv_status' => 'will_apply']);
    $o = newCohort($v);
    answerAll($o);
    sendChecklist($o);
    confirmReady($o);
    $this->get("/public/offering/$o")->assertOk()->assertSee('Standalone Micro-Credential (SAMC)')->assertSee('no credit value')->assertDontSee('MQF level 4')->assertDontSee('Credit:', false)->assertSee('separate decisions');
});

it('shows SAMC credit only while Quality Verification is valid to the cohort end date (FR-89)', function () {
    $v = approvedOwn(80, ['proposed_credit' => 2.0, 'proposed_mqf_level' => 4, 'qv_status' => 'verified', 'qv_valid_until' => today()->addDays(365)->toDateString()]);
    $o = newCohort($v);
    answerAll($o);
    sendChecklist($o);
    confirmReady($o);
    $this->get("/public/offering/$o")->assertSee('This SAMC is Quality-Verified')->assertSee('MQF level 4')->assertSee('Credit:', false);
    DB::table('mc_versions')->where('id', $v)->update(['qv_valid_until' => today()->addDays(60)->toDateString()]); // expires before the cohort ends
    $this->get("/public/offering/$o")->assertDontSee('This SAMC is Quality-Verified')->assertDontSee('MQF level 4')->assertSee('Credit after QV renewal');
});

it('shows an MCE as non-credit, without the separate-stages credit notice', function () {
    $v = approvedOwn(20);
    $o = newCohort($v);
    answerAll($o);
    sendChecklist($o);
    confirmReady($o);
    $this->get("/public/offering/$o")->assertSee('Executive Micro-Credential (MCE)')->assertDontSee('separate decisions');
});

it('searches and filters the catalogue (FR-92)', function () {
    [$o] = openCohort(['fee' => '150']);
    $v = approvedOwn(80, ['name_en' => 'Cloud Native Development', 'name_ms' => 'Pembangunan Awan Asli', 'discipline' => 'Cloud', 'delivery_language' => 'Bahasa Melayu']);
    $o2 = newCohort($v, ['delivery_mode' => 'blended', 'start_date' => today()->addDays(200)->toDateString(), 'end_date' => today()->addDays(260)->toDateString(), 'reg_opens_on' => today()->toDateString(), 'reg_closes_on' => today()->addDays(150)->toDateString(), 'cohort_name' => '2027/2']);
    answerAll($o2);
    sendChecklist($o2);
    confirmReady($o2);
    $see = fn (string $url, string $yes, string $no) => $this->get($url)->assertSee($yes)->assertDontSee($no);
    $see('/public/catalogue?q=cloud', 'Cloud Native Development', 'Software Testing');
    $see('/public/catalogue?category=umc', 'Software Testing', 'Cloud Native Development');
    $see('/public/catalogue?category=samc', 'Cloud Native Development', 'Software Testing');
    $see('/public/catalogue?ptj=FTMK&mode=blended', 'Cloud Native Development', 'Software Testing');
    $see('/public/catalogue?free=1', 'Cloud Native Development', 'Software Testing');
    $see('/public/catalogue?fee_max=200', 'Software Testing', 'Zzz');
    $see('/public/catalogue?language=Melayu', 'Cloud Native Development', 'Software Testing');
    $see('/public/catalogue?field=cloud', 'Cloud Native Development', 'Software Testing');
    $see('/public/catalogue?starts='.today()->addDays(100)->toDateString(), 'Cloud Native Development', 'Software Testing');
    $this->get('/public/catalogue?q=nothing')->assertSee('No offerings match.');
});

it('is available in Bahasa Melayu and English (FR-96)', function () {
    [$o] = openCohort();
    DB::table('mc_versions')->where('micro_credential_id', DB::table('mc_versions')->value('micro_credential_id'))->update(['name_ms' => 'Ujian Perisian']);
    $this->get('/lang/ms')->assertRedirect();
    $this->withSession(['lang' => 'ms'])->get("/public/offering/$o")->assertSee('Ujian Perisian')->assertSee('Ini ialah Unbundled Micro-Credential')->assertSee('Hasil pembelajaran')->assertSee('keputusan yang berasingan');
    $this->withSession(['lang' => 'ms'])->get('/public/catalogue')->assertSee('Katalog')->assertSee('Percuma');
    $this->get('/lang/fr')->assertNotFound();
    $this->withSession(['lang' => 'en'])->get("/public/offering/$o")->assertSee('Learning outcomes');
});

it('serves the public pages without login, with the stages explained', function () {
    $this->get('/public')->assertOk()->assertSee('Micro-credentials at UTeM')->assertSee('UMC')->assertSee('SAMC')->assertSee('MCE');
    $this->get('/public/how')->assertOk()->assertSee('Each stage is a separate decision')->assertSee('Storing is not transferring');
    $this->get('/public/pathways')->assertOk()->assertSee('leads to a degree automatically');
    $this->get('/public/offering/9999')->assertNotFound();
});

it('displays the lecturer name across catalogue, student portal, and staff views even when facilitators is null', function () {
    [$o, $v1, $mc] = openCohort(['facilitators' => null, 'fee' => '0']);
    $amir = User::where('name', 'Amir Hakimi')->first();

    $this->get('/public/catalogue')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->get('/public/catalogue?q=Aina')->assertOk()->assertSee('Software Testing');
    $this->get("/public/offering/$o")->assertOk()->assertSee('Lecturer')->assertSee('Dr. Aina Zulkifli');

    $this->actingAs($amir)->get('/catalogue')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($amir)->get('/catalogue?q=Aina')->assertOk()->assertSee('Software Testing');
    $this->actingAs($amir)->get("/offering/$o")->assertOk()->assertSee('Lecturer')->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($amir)->get("/register/$o")->assertOk()->assertSee('Dr. Aina Zulkifli');

    $this->actingAs($amir)->post("/register/$o/eligibility", ['phone' => '012-3456789', 'prerequisites_ok' => 1])->assertSessionHasNoErrors();
    $this->actingAs($amir)->post("/register/$o/acknowledge", ['ack_statement' => 1, 'pdpa' => 1])->assertSessionHasNoErrors();
    $eId = (int) DB::table('enrolments')->where('offering_id', $o)->where('user_id', $amir->id)->value('id');

    $this->actingAs($amir)->get('/enrolments')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($amir)->get("/enrolments/$eId")->assertOk()->assertSee('Dr. Aina Zulkifli');

    DB::table('offerings')->where('id', $o)->update(['status' => 'running']);
    $this->actingAs($amir)->get('/tasks')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($amir)->get('/my-results')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($amir)->get("/my-results/$eId")->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($amir)->get('/my-record')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($amir)->get("/records/$eId")->assertOk()->assertSee('Dr. Aina Zulkifli');

    $this->actingAs($this->aina)->get('/offerings')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($this->aina)->get("/offerings/$o")->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($this->aina)->get('/micro-credentials')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($this->aina)->get("/micro-credentials/$mc")->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($this->aina)->get('/search?q=Aina')->assertOk()->assertSee('Software Testing');

    DB::table('offerings')->where('id', $o)->update([
        'reg_opens_on' => today()->subDays(10)->toDateString(),
        'reg_closes_on' => today()->subDays(2)->toDateString(),
        'start_date' => today()->subDay()->toDateString(),
        'status' => 'running',
    ]);
    $this->actingAs($this->aina)->get("/offerings/$o/results")->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($this->hakim)->get('/results')->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($this->hakim)->get("/results/$o")->assertOk()->assertSee('Dr. Aina Zulkifli');
    $this->actingAs($this->hakim)->get("/completion/$o")->assertOk()->assertSee('Dr. Aina Zulkifli');
});

it('blocks proposals containing forbidden claims in English or Bahasa Melayu (REQ-C03-003, REQ-C09-008)', function () {
    $v = completeUmc();
    $this->actingAs($this->aina)->post("/proposals/$v/basics", [
        'name_en' => 'We guarantee credit transfer course',
    ])->assertSessionHasErrors('name_en');

    $this->actingAs($this->aina)->post("/proposals/$v/justification", [
        'need' => 'Kursus ini memberi kredit automatik untuk pelajar.',
    ])->assertSessionHasErrors('need');
});

it('blocks pathways containing forbidden claims (REQ-AE-005)', function () {
    $prog = DB::table('programmes')->first();

    $this->actingAs($this->hakim)->post('/pathways', [
        'programme_id' => $prog->id,
        'plos' => 'Jaminan pemindahan kredit bagi semua kursus',
    ])->assertSessionHasErrors('plos');
});

it('renders public offering credentials strictly per credential_types and labels Credential Verification Method (REQ-C10-004, REQ-C10-005)', function () {
    [$o, $v1, $mc] = openCohort();
    DB::table('mc_versions')->where('id', $v1)->update([
        'credential_types' => json_encode(['certificate']),
        'verification_method' => 'CODL Cryptographic Registry',
    ]);

    $this->get("/public/offering/$o")
        ->assertOk()
        ->assertSee('Credential Verification Method')
        ->assertSee('Official Certificate')
        ->assertDontSee('Modular Digital Badges Awarded');
});
