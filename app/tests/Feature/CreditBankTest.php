<?php

use App\Models\User;
use App\Modules\CreditBank\Acr;
use App\Modules\CreditBank\CreditLedger;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

/** Amir's ACR from a completed UMC. */
if (! function_exists('amirAcr')) {
function amirAcr(): object
{
    [$o, , $c] = issued();

    return DB::table('acrs')->where('user_id', test()->amir->id)->first();
}
}


if (! function_exists('confirmedAcr')) {
function confirmedAcr(): object
{
    $a = amirAcr();
    test()->actingAs(test()->hakim)->post("/deposits/{$a->id}/confirm")->assertSessionHasNoErrors();

    return $a;
}
}


if (! function_exists('consentedAcr')) {
function consentedAcr(string $decision = 'given'): object
{
    $a = confirmedAcr();
    test()->actingAs(test()->amir)->post("/my-credit/{$a->id}/consent", ['decision' => $decision])->assertSessionHasNoErrors();

    return $a;
}
}


if (! function_exists('depositOf')) {
function depositOf(object $a, array $over = [])
{
    return test()->actingAs(test()->hakim)->post("/deposits/{$a->id}/record", $over + ['abc_reference' => 'ABC-2026-0042', 'deposit_date' => today()->toDateString(), 'credits' => '3', 'deposit_status' => 'successful']);
}
}


if (! function_exists('deposited')) {
function deposited(): object
{
    $a = consentedAcr();
    depositOf($a)->assertSessionHasNoErrors();

    return DB::table('abc_deposits')->where('acr_id', $a->id)->first();
}
}


// ---- preparing (FR-acrelig, FR-acrchecks, FR-acrprep)

it('prepares the ACR by itself when the credential is issued, from verified records only, and records the checks', function () {
    $a = amirAcr();
    expect($a->status)->toBe('prepared')->and($a->reference)->toStartWith('MCMS-A-')->and((float) $a->credits)->toBe(3.0)->and($a->mqf_level)->toBe(6)
        ->and(json_decode($a->payload, true)['participant']['matric'])->toBe($this->amir->institution_id)
        ->and(DB::table('acr_checks')->where('acr_id', $a->id)->count())->toBe(count(Acr::CHECKS))->and(DB::table('acr_checks')->where('acr_id', $a->id)->where('passed', false)->count())->toBe(0)
        ->and(DB::table('tasks')->where('kind', 'acr_confirm')->whereNull('completed_at')->exists())->toBeTrue()
        ->and(DB::table('audit_events')->where('action', 'acr.prepared')->exists())->toBeTrue();
    expect(app(Acr::class)->prepare((int) DB::table('enrolments')->where('user_id', $this->amir->id)->value('id'))->id)->toBe($a->id)->and(DB::table('acrs')->count())->toBe(2); // Amir and Cee, never twice
});

it('never prepares an ACR for an MCE, and a failing check blocks with the reason recorded (FR-acrelig)', function () {
    [$o, $people] = issued();
    $cee = (int) eid($o, $people[2]);
    $gone = DB::table('acrs')->where('user_id', $people[2]->id)->pluck('id');
    DB::table('acr_checks')->whereIn('acr_id', $gone)->delete();
    DB::table('acrs')->whereIn('id', $gone)->delete();
    DB::table('micro_credentials')->update(['category' => 'mce']);
    expect(app(Acr::class)->prepare($cee))->toBeNull()->and((bool) DB::table('acr_checks')->where('enrolment_id', $cee)->whereNull('acr_id')->where('check_key', 'category')->value('passed'))->toBeFalse();
    DB::table('micro_credentials')->update(['category' => 'umc']);
    DB::table('users')->where('id', $people[2]->id)->update(['institution_id' => null]);
    expect(app(Acr::class)->prepare($cee))->toBeNull()->and((bool) DB::table('acr_checks')->where('enrolment_id', $cee)->where('check_key', 'identity_results')->value('passed'))->toBeFalse();
});

it('prepares a SAMC ACR only when Quality Verification was valid on the completion date (FR-acrelig)', function () {
    [$mc, , $o] = samcCompleted(false);
    confirmCompletion($o, [eid($o, $this->amir)]);
    expect(DB::table('credentials')->count())->toBe(1)->and(DB::table('acrs')->count())->toBe(0)
        ->and((bool) DB::table('acr_checks')->whereNull('acr_id')->where('check_key', 'qv_valid')->value('passed'))->toBeFalse();
    DB::table('qv_records')->insert(['micro_credential_id' => $mc, 'cycle_no' => 1, 'status' => 'verified', 'qv_reference' => 'QV/1', 'verified_credit' => 2, 'verified_mqf_level' => 5, 'valid_from' => today()->subMonth(), 'valid_until' => today()->addYear(), 'created_at' => now(), 'updated_at' => now()]);
    $acr = app(Acr::class)->prepare((int) eid($o, $this->amir));
    expect((float) $acr->credits)->toBe(2.0)->and($acr->mqf_level)->toBe(5)->and($acr->qv_record_id)->not->toBeNull();
});

// ---- confirm (FR-acrverify)

it('lets the deputy dean confirm, asks the participant for consent, and refuses everyone else (D-12)', function () {
    $a = amirAcr();
    $this->actingAs($this->farid)->post("/deposits/{$a->id}/confirm")->assertForbidden(); // CODL takes no academic decision
    $this->actingAs($this->amir)->post("/deposits/{$a->id}/confirm")->assertForbidden();
    $this->actingAs($this->hakim)->get("/deposits/{$a->id}")->assertOk()->assertSee('cannot be edited');
    $this->post("/deposits/{$a->id}/confirm")->assertSessionHasNoErrors();
    expect(DB::table('acrs')->where('id', $a->id)->value('status'))->toBe('verified')->and(DB::table('acrs')->where('id', $a->id)->value('confirmed_by'))->toBe($this->hakim->id)
        ->and(DB::table('tasks')->where('kind', 'acr_consent')->where('user_id', $this->amir->id)->whereNull('completed_at')->exists())->toBeTrue()
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'acr.confirmed')->exists())->toBeTrue();
    $this->post("/deposits/{$a->id}/confirm")->assertStatus(422);
});

// ---- consent (FR-dpconsent, FR-dpdecline)

it('keeps the ACR out of the deposit queue until the participant consents, and records the wording version (FR-dpconsent)', function () {
    $a = amirAcr();
    $this->actingAs($this->amir)->post("/my-credit/{$a->id}/consent", ['decision' => 'given'])->assertStatus(422); // not confirmed yet
    $this->actingAs($this->hakim)->post("/deposits/{$a->id}/confirm");
    depositOf($a)->assertStatus(422); // no consent, no deposit
    $this->post('/deposits/export', ['acrs' => [$a->id]])->assertStatus(422);
    $this->actingAs($this->amir)->post("/my-credit/{$a->id}/consent", ['decision' => 'given'])->assertSessionHasNoErrors();
    $c = DB::table('consents')->where('acr_id', $a->id)->first();
    expect($c->decision)->toBe('given')->and($c->channel)->toBe('web')->and($c->wording)->toContain('not a credit transfer')->and(json_decode($c->template_versions, true))->toHaveKeys(['en', 'ms'])
        ->and(DB::table('acrs')->where('id', $a->id)->value('status'))->toBe('consent_given')
        ->and(DB::table('tasks')->where('kind', 'abc_deposit')->whereNull('completed_at')->exists())->toBeTrue()->and(DB::table('tasks')->where('kind', 'acr_consent')->whereNull('completed_at')->exists())->toBeFalse();
    $this->actingAs($this->hakim)->get('/deposits')->assertSee('Record deposit');
});

it('keeps the ACR when the participant declines or postpones, and lets them consent later (FR-dpdecline)', function () {
    $a = consentedAcr('declined');
    expect(DB::table('acrs')->where('id', $a->id)->value('status'))->toBe('consent_declined')->and(DB::table('tasks')->where('kind', 'abc_deposit')->exists())->toBeFalse();
    depositOf($a)->assertStatus(422);
    $this->actingAs($this->amir)->post("/my-credit/{$a->id}/consent", ['decision' => 'given'])->assertSessionHasNoErrors();
    expect(DB::table('consents')->where('acr_id', $a->id)->count())->toBe(2)->and(DB::table('acrs')->where('id', $a->id)->value('status'))->toBe('consent_given');
    $this->post('/my-credit/'.$a->id.'/consent', ['decision' => 'given'])->assertStatus(422); // already given
});

it('lets a participant see only their own ACR', function () {
    $a = confirmedAcr();
    $this->actingAs($this->amir)->get("/my-credit/{$a->id}")->assertOk()->assertSee('separate record');
    $bee = learner('Bee Other', 'B032419999');
    $this->actingAs($bee)->get("/my-credit/{$a->id}")->assertNotFound();
});

// ---- export and deposit (FR-dpexport, FR-dprecord, FR-cvexpiry)

it('exports only consented and confirmed records (FR-dpexport)', function () {
    $a = consentedAcr();
    $r = $this->actingAs($this->hakim)->post('/deposits/export', ['acrs' => [$a->id]]);
    $r->assertOk();
    expect($r->headers->get('Content-Type'))->toContain('text/csv')->and($r->getContent())->toContain($a->reference)->and(DB::table('abc_batches')->count())->toBe(1);
    $cee = DB::table('acrs')->where('id', '!=', $a->id)->first();
    $this->post('/deposits/export', ['acrs' => [$cee->id]])->assertStatus(422); // not consented
});

it('records the deposit, sets the expiry from the setting, and stores the setting version (FR-dprecord, FR-cvexpiry)', function () {
    $a = consentedAcr();
    depositOf($a, ['credits' => '4'])->assertSessionHasErrors('credits'); // more than the ACR holds
    depositOf($a, ['deposit_date' => today()->addDay()->toDateString()])->assertSessionHasErrors('deposit_date');
    depositOf($a, ['deposit_status' => 'in_process'])->assertSessionHasNoErrors();
    expect(DB::table('abc_deposits')->where('acr_id', $a->id)->value('credit_status'))->toBeNull()->and(DB::table('acrs')->where('id', $a->id)->value('status'))->toBe('consent_given');
    depositOf($a, ['deposit_status' => 'correction'])->assertSessionHasNoErrors();
    expect(DB::table('acrs')->where('id', $a->id)->value('status'))->toBe('correction');
    depositOf($a)->assertSessionHasNoErrors()->assertSessionHas('status', fn ($s) => str_contains($s, 'not a credit transfer'));
    $d = DB::table('abc_deposits')->where('acr_id', $a->id)->first();
    expect($d->credit_status)->toBe('available')->and($d->credit_expiry)->toBe(today()->addYears(10)->toDateString())->and($d->setting_version_id)->not->toBeNull()->and($d->recorded_by)->toBe($this->hakim->id)
        ->and(DB::table('acrs')->where('id', $a->id)->value('status'))->toBe('deposited')->and(DB::table('abc_deposits')->where('acr_id', $a->id)->count())->toBe(1)
        ->and(DB::table('credit_ledger_entries')->where('abc_deposit_id', $d->id)->where('event', 'deposit')->count())->toBe(1)
        ->and(DB::table('tasks')->where('kind', 'abc_deposit')->whereNull('completed_at')->exists())->toBeFalse()
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'abc.deposited')->exists())->toBeTrue();
    depositOf($a)->assertStatus(422); // already deposited
});

it('shows the credit ledger with the total and the note that credits are not automatically accepted (FR-cvledger)', function () {
    deposited();
    $this->actingAs($this->amir)->get('/my-credit')->assertOk()->assertSee('Available')->assertSee('not automatically accepted by any programme')->assertSee('storage only');
    $this->actingAs($this->amir)->get('/my-credit')->assertSeeInOrder(['id="total"', '3']);
    $this->actingAs($this->amir)->post('/deposits/1/record', [])->assertForbidden();
});

// ---- expiry (FR-cvalert, FR-cvexpired, BR-28)

it('alerts the participant at each interval and expires the credit without deleting the ACR (FR-cvalert, FR-cvexpired)', function () {
    $d = deposited();
    $ledger = app(CreditLedger::class);
    expect($ledger->alert())->toBe(0);
    $this->travelTo(today()->addYears(10)->subDays(80)); // inside the 90-day interval
    expect($ledger->alert())->toBe(1)->and($ledger->alert())->toBe(1)->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'credit.expiring')->where('channel', 'in_system')->count())->toBe(1);
    expect($ledger->expire())->toBe(0);
    $this->travelTo(today()->addDays(81)->addYears(0));
    expect($ledger->expire())->toBe(1)->and($ledger->expire())->toBe(0)
        ->and(DB::table('abc_deposits')->where('id', $d->id)->value('credit_status'))->toBe('expired')->and(DB::table('acrs')->where('id', $d->acr_id)->exists())->toBeTrue()
        ->and(DB::table('credit_ledger_entries')->where('abc_deposit_id', $d->id)->where('event', 'expired')->count())->toBe(1)
        ->and($ledger->selectable($this->amir->id))->toHaveCount(0);
});

// ---- single redemption (FR-rdprov, FR-rdnoreuse, FR-rdconflict, FR-rdrelease, FR-rdkeep)

it('holds credit for one application only, and explains why the second is blocked (BR-29)', function () {
    $d = deposited();
    $ledger = app(CreditLedger::class);
    expect($ledger->selectable($this->amir->id))->toHaveCount(1);
    $ledger->provisional($d->id, 11, $this->hakim->id);
    expect(DB::table('abc_deposits')->where('id', $d->id)->value('credit_status'))->toBe('provisional')->and($ledger->selectable($this->amir->id))->toHaveCount(0)
        ->and($ledger->whyNot(DB::table('abc_deposits')->find($d->id)))->toContain('provisionally redeemed');
    expect(fn () => DB::transaction(fn () => $ledger->provisional($d->id, 12, $this->hakim->id)))->toThrow(ValidationException::class);
    expect(DB::table('acrs')->where('id', $d->acr_id)->exists())->toBeTrue(); // redemption never alters the ACR (FR-rdkeep)
});

it('moves provisional credit to Redeemed on completion, with the evidence reference (FR-rdfinal, FR-rdabcsync)', function () {
    $d = deposited();
    $ledger = app(CreditLedger::class);
    expect(fn () => $ledger->redeem($d->id, 'X', null, null, $this->hakim->id))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class); // only provisional credit
    $ledger->provisional($d->id, 1, $this->hakim->id);
    $ledger->redeem($d->id, 'Graduation list 2029/1', today()->toDateString(), 'ABC-UPD-7', $this->hakim->id);
    $e = DB::table('redemption_events')->where('event', 'final')->first();
    expect(DB::table('abc_deposits')->where('id', $d->id)->value('credit_status'))->toBe('redeemed')->and($e->evidence_ref)->toBe('Graduation list 2029/1')->and($e->abc_update_ref)->toBe('ABC-UPD-7')
        ->and($ledger->whyNot(DB::table('abc_deposits')->find($d->id)))->toContain('redeemed toward a qualification');
});

it('returns credit to Available on non-completion while valid, and to Expired when validity ended (FR-rdrelease)', function () {
    $d = deposited();
    $ledger = app(CreditLedger::class);
    $ledger->provisional($d->id, 1, $this->hakim->id);
    $ledger->release($d->id, 'Withdrawal letter', null, null, $this->hakim->id);
    expect(DB::table('abc_deposits')->where('id', $d->id)->value('credit_status'))->toBe('available');
    $ledger->provisional($d->id, 2, $this->hakim->id);
    $this->travelTo(today()->addYears(10)->addDay());
    $ledger->release($d->id, 'Non-completion record', null, null, $this->hakim->id);
    expect(DB::table('abc_deposits')->where('id', $d->id)->value('credit_status'))->toBe('expired')->and(DB::table('credit_ledger_entries')->where('abc_deposit_id', $d->id)->count())->toBe(5);
});
