<?php

use App\Models\User;
use App\Modules\Credentials\Jwt;
use App\Modules\Credentials\KeyStore;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

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

/** Amir and Cee completed; Bee did not. Returns the cohort, the people and Amir's credential. */
if (! function_exists('issued')) {
function issued(): array
{
    [$o, $v1, $mc, $people] = verifiedCohort();
    confirmCompletion($o, [eid($o, test()->amir), eid($o, $people[2])]);

    return [$o, $people, DB::table('credentials')->where('enrolment_id', eid($o, test()->amir))->first(), $v1];
}
}


if (! function_exists('codeOf')) {
function codeOf(object $c): string
{
    return $c->public_code;
}
}


// ---- the signing test (SDD risk: no mature PHP library for eddsa-rdfc-2022; the fallback is a JWT proof with the same key)

it('signs and verifies a credential with an Ed25519 key, and refuses any change to it', function () {
    $key = KeyStore::active();
    $jwt = Jwt::sign(['iss' => 'https://mcms.example.test', 'jti' => 'x', 'vc' => ['name' => 'Data Structures']], $key);
    expect(Jwt::verify($jwt)['ok'])->toBeTrue()->and(Jwt::verify($jwt)['payload']['vc']['name'])->toBe('Data Structures')->and(Jwt::verify($jwt)['kid'])->toBe($key->kid);
    [$h, $p, $s] = explode('.', $jwt);
    $forged = KeyStore::b64(json_encode(['iss' => 'https://mcms.example.test', 'jti' => 'x', 'vc' => ['name' => 'Medicine']]));
    expect(Jwt::verify("$h.$forged.$s")['ok'])->toBeFalse()->and(Jwt::verify("$h.$p.".KeyStore::b64(random_bytes(64)))['ok'])->toBeFalse()
        ->and(Jwt::verify('not.a.jwt')['ok'])->toBeFalse()->and(Jwt::verify('')['ok'])->toBeFalse();
    $none = KeyStore::b64(json_encode(['alg' => 'none', 'typ' => 'JWT', 'kid' => $key->kid]));
    expect(Jwt::verify("$none.$p.$s")['ok'])->toBeFalse(); // the algorithm cannot be switched off
});

it('rotates the key without invalidating credentials already issued (SR-08)', function () {
    [, , $c] = issued();
    $old = $c->kid;
    $new = KeyStore::rotate();
    expect($new->kid)->not->toBe($old)->and(Jwt::verify($c->jwt)['ok'])->toBeTrue()->and(Jwt::verify($c->jwt)['kid'])->toBe($old)
        ->and(DB::table('signing_keys')->where('kid', $old)->value('status'))->toBe('retired')->and(collect(KeyStore::jwks()['keys'])->pluck('kid')->all())->toContain($old, $new->kid);
    $this->getJson('/.well-known/jwks.json')->assertOk()->assertJsonPath('keys.0.kty', 'OKP')->assertJsonPath('keys.0.crv', 'Ed25519');
});

// ---- issuing (D-29, FR-137, FR-144)

it('issues the credential by itself when completion is confirmed, only to those confirmed (FR-144)', function () {
    [$o, $people, $c] = issued();
    expect(DB::table('credentials')->count())->toBe(2)->and(DB::table('credentials')->where('enrolment_id', eid($o, $people[1]))->exists())->toBeFalse()
        ->and($c->number)->toMatch('/^MCMS-C-\d{4}-\d{6}$/')->and($c->public_code)->toMatch('/^[0-9A-HJKMNP-TV-Z]{5}-[0-9A-HJKMNP-TV-Z]{5}$/')->and($c->status)->toBe('valid')
        ->and(DB::table('audit_events')->where('action', 'credential.issued')->count())->toBe(2)
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'credential.issued')->where('channel', 'in_system')->value('body'))->toContain($c->number)
        ->and(session('status'))->toContain('Credentials issued: 2');
    $this->actingAs($this->hakim)->post('/credentials/issue')->assertNotFound(); // there is no manual issuing step
});

it('signs what it issues in the Open Badges 3.0 / VC shape, in both languages (FR-142, FR-145)', function () {
    [, , $c] = issued();
    $v = Jwt::verify($c->jwt);
    $vc = $v['payload']['vc'];
    $x = json_decode($c->content, true);
    expect($v['ok'])->toBeTrue()->and($vc['type'])->toBe(['VerifiableCredential', 'OpenBadgeCredential'])->and($vc['issuer']['name'])->toBe('Universiti Teknikal Malaysia Melaka')
        ->and($vc['credentialSubject']['identifier'][0]['identityHash'])->toBe('Amir Hakimi')->and($v['payload']['jti'])->toBe(url('/public/verify/'.$c->public_code))
        ->and($x['statement']['ms'])->toContain('Ini ialah Unbundled Micro-Credential')->and($x['name']['ms'])->not->toBeEmpty()->and($x['signatory'])->toBe('Hakim Rahman')->and($x['slt_hours'])->toBe(80);
});

it('fills credit, MQF level and Quality Verification only from verified records (FR-143, D-17)', function () {
    [, , $c] = issued();
    $x = json_decode($c->content, true);
    expect($x['claims'])->toEqual(['credit' => 3, 'mqf' => null, 'qv' => false, 'qv_until' => null]); // a UMC carries the credit of its parent course; nothing else
});

it('gives a SAMC no credit claim without a valid Quality Verification, and keeps the status of the issue date (FR-143, FR-152)', function () {
    [$o, $v1, $mc, $people] = runningCohort();
    DB::table('micro_credentials')->where('id', $mc)->update(['category' => 'samc']);
    DB::table('mc_versions')->where('id', $v1)->update(['proposed_credit' => 2.0, 'proposed_mqf_level' => 4, 'qv_status' => 'will_apply']);
    submittedResults($o, $people);
    verifyAll($o);
    confirmCompletion($o, [eid($o, $this->amir)]);
    $c = DB::table('credentials')->first();
    expect(json_decode($c->content, true)['claims'])->toEqual(['credit' => null, 'mqf' => null, 'qv' => false, 'qv_until' => null]);

    // a second, Quality-Verified cohort member: the claim shows, and stays as it was on the issue date
    DB::table('mc_versions')->where('id', $v1)->update(['qv_status' => 'verified', 'qv_valid_until' => today()->addYear()->toDateString()]);
    confirmCompletion($o, [eid($o, $people[2])]);
    $q = DB::table('credentials')->where('enrolment_id', eid($o, $people[2]))->first();
    expect(json_decode($q->content, true)['claims'])->toEqual(['credit' => '2', 'mqf' => 4, 'qv' => true, 'qv_until' => today()->addYear()->toDateString()]);
    DB::table('mc_versions')->where('id', $v1)->update(['qv_valid_until' => today()->subDay()->toDateString()]); // expired later
    expect(json_decode(DB::table('credentials')->find($q->id)->content, true)['claims']['qv'])->toBeTrue();
});

it('blocks issuing when a check fails, lists the participant with the reason, and issues after the fix (FR-137, FR-144)', function () {
    [$o, , , $people] = verifiedCohort();
    DB::table('users')->where('id', $this->amir->id)->update(['institution_id' => null]);
    confirmCompletion($o, [eid($o, $this->amir), eid($o, $people[2])]);
    expect(DB::table('credentials')->count())->toBe(1)->and(session('status'))->toContain('Credentials issued: 1')->toContain('1 not issued');
    $f = DB::table('credential_issue_failures')->first();
    expect(json_decode($f->reasons, true)[0])->toContain('identity does not match the official record');
    $this->actingAs($this->hakim)->get('/credentials')->assertSee('Not issued')->assertSee('Amir Hakimi')->assertSee('identity does not match');
    DB::table('users')->where('id', $this->amir->id)->update(['institution_id' => 'B032410001']);
    $this->actingAs($this->hakim)->post('/credentials/retry/'.eid($o, $this->amir))->assertSessionHas('status');
    expect(DB::table('credentials')->count())->toBe(2)->and(DB::table('credential_issue_failures')->count())->toBe(0);
});

it('never issues twice for the same completion (IR-17)', function () {
    [$o, , $c] = issued();
    $again = app(\App\Modules\Credentials\IssueCredential::class)->issue(eid($o, $this->amir), $this->hakim->id);
    expect($again->id)->toBe($c->id)->and(DB::table('credentials')->count())->toBe(2);
});

// ---- the participant (FR-147)

it('lets a participant see, share and download their own credential only (FR-147)', function () {
    [, , $c] = issued();
    $this->actingAs($this->amir)->get('/my-credentials')->assertSee($c->number)->assertSee('Issued');
    $this->actingAs($this->amir)->get("/my-credentials/{$c->id}")->assertSee(url('/public/verify/'.$c->public_code))->assertSee('Copy link')->assertSee('<svg', false)->assertSee('only your name');
    $json = $this->actingAs($this->amir)->get("/my-credentials/{$c->id}/download/json");
    expect($json->headers->get('Content-Disposition'))->toContain($c->number.'.json')->and(json_decode($json->getContent(), true)['type'])->toBe(['VerifiableCredential', 'OpenBadgeCredential'])->and(Jwt::verify(json_decode($json->getContent(), true)['proof_jwt'])['ok'])->toBeTrue();
    expect($this->actingAs($this->amir)->get("/my-credentials/{$c->id}/download/jwt")->getContent())->toBe($c->jwt);
    $this->actingAs($this->amir)->get("/my-credentials/{$c->id}/download/exe")->assertNotFound();
    $other = learner('Zed Other', 'B032410088');
    $this->actingAs($other)->get("/my-credentials/{$c->id}")->assertForbidden();
    $this->actingAs($other)->get("/my-credentials/{$c->id}/download/jwt")->assertForbidden();
});

it('lays out the certificate with the FR-140 content and a QR code, in English and Bahasa Melayu (FR-140, FR-145)', function () {
    [, , $c] = issued();
    $this->actingAs($this->amir)->get("/my-credentials/{$c->id}/certificate")->assertSee('Certificate')->assertSee('Amir Hakimi')->assertSee('Software Testing')->assertSee($c->number)->assertSee('Hakim Rahman')->assertSee('Date awarded')->assertSee('<svg', false)
        ->assertSee('UNIVERSITI TEKNIKAL MALAYSIA MELAKA')->assertSee('Credit 3');
    $this->actingAs($this->amir)->get("/my-credentials/{$c->id}/certificate?lang=ms")->assertSee('Sijil')->assertSee('Tarikh anugerah')->assertSee('Penandatangan')->assertSee('Kredit 3');
});

// ---- public verification (FR-153 to FR-157)

it('shows only the minimum information for a valid credential, with no login (FR-153, FR-154)', function () {
    [, , $c] = issued();
    $page = $this->get('/public/verify/'.$c->public_code);
    $page->assertOk()->assertSee('Valid')->assertSee('Amir Hakimi')->assertSee('Software Testing')->assertSee('Universiti Teknikal Malaysia Melaka')
        ->assertDontSee('B032410001')->assertDontSee('amir.hakimi@learner')->assertDontSee('70 /')->assertDontSee('proctoring');
    $json = $this->getJson('/public/verify/'.$c->public_code)->assertOk()->json();
    expect(array_keys($json))->toBe(['status', 'recipient', 'credential', 'issuer', 'issued'])->and($json['status'])->toBe('valid');
});

it('accepts the code in lower case and without the dash', function () {
    [, , $c] = issued();
    $this->get('/public/verify/'.strtolower(str_replace('-', '', $c->public_code)))->assertOk()->assertSee('Amir Hakimi');
});

it('shows only the fields listed in the setting (FR-154)', function () {
    [, , $c] = issued();
    app(\App\Modules\Core\Settings::class)->set('verification.public_fields', 'Credential, Date, Status', 'Do not show names', $this->farid);
    $this->get('/public/verify/'.$c->public_code)->assertSee('Software Testing')->assertDontSee('Amir Hakimi')->assertDontSee('Universiti Teknikal Malaysia Melaka');
});

it('answers an unknown and a malformed code with the same neutral message (FR-157)', function () {
    [, , $c] = issued();
    $unknown = $this->get('/public/verify/ZZZZZ-00000');
    $malformed = $this->get('/public/verify/not-a-code');
    $near = $this->get('/public/verify/'.substr($c->public_code, 0, 9).(substr($c->public_code, -1) === '0' ? '1' : '0'));
    foreach ([$unknown, $malformed, $near] as $r) {
        $r->assertNotFound()->assertSee('We could not find this credential')->assertSee('the letters O and I are not used');
    }
    $body = fn ($r) => preg_replace('/\s+/', '', substr(strip_tags($r->getContent()), strpos(strip_tags($r->getContent()), 'Verification result')));
    expect($body($unknown))->toBe($body($near))->toBe($body($malformed));
    $this->getJson('/public/verify/ZZZZZ-00000')->assertNotFound();
});

it('logs each verification with the outcome and the network address only (FR-156)', function () {
    [, , $c] = issued();
    $this->get('/public/verify/'.$c->public_code);
    $this->get('/public/verify/ZZZZZ-00000');
    $rows = DB::table('verification_log')->orderBy('id')->get();
    expect($rows->pluck('outcome')->all())->toBe(['valid', 'not_found'])->and($rows[0]->credential_id)->toBe($c->id)->and($rows[1]->credential_id)->toBeNull()->and($rows[0]->network_address)->not->toBeNull()
        ->and(array_keys((array) $rows[0]))->toBe(['id', 'credential_id', 'outcome', 'via', 'network_address', 'created_at']);
});

it('limits how fast the verification page can be asked (SR-17)', function () {
    foreach (range(1, 60) as $_) {
        $this->get('/public/verify/ZZZZZ-00000');
    }
    $this->get('/public/verify/ZZZZZ-00000')->assertStatus(429);
});

it('checks the signature of an uploaded Digital Credential file (FR-155)', function () {
    [, , $c] = issued();
    $ok = $this->post('/public/verify/file', ['file' => UploadedFile::fake()->createWithContent('c.jwt', $c->jwt)]);
    $ok->assertSee('The signature is valid')->assertSee($c->kid)->assertSee('Valid');
    [$h, $p, $s] = explode('.', $c->jwt);
    $changed = json_decode(KeyStore::unb64($p), true);
    $changed['vc']['name'] = 'Doctor of Medicine';
    $this->post('/public/verify/file', ['file' => UploadedFile::fake()->createWithContent('c.jwt', "$h.".KeyStore::b64(json_encode($changed)).".$s")])->assertSee('The signature does not match the issuer key');
    $this->post('/public/verify/file', ['file' => UploadedFile::fake()->createWithContent('c.txt', 'hello')])->assertSee('not a signed credential file');
    expect(DB::table('verification_log')->where('via', 'file')->pluck('outcome')->all())->toBe(['signature_ok', 'signature_bad', 'signature_bad']);
});

// ---- correction (FR-158, FL-10)

it('corrects a name for a confirmed error, keeps the original, and shows Corrected on the same link (FR-158, FL-10)', function () {
    [, , $c] = issued();
    $this->actingAs($this->hakim)->post("/credentials/{$c->id}/correct", ['holder' => 'Amir Hakimi bin Ismail', 'reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->hakim)->post("/credentials/{$c->id}/correct", ['holder' => 'Amir Hakimi bin Ismail', 'reason' => 'Name must include the father\'s name'])->assertSessionHasNoErrors();
    $now = DB::table('credentials')->find($c->id);
    $revs = DB::table('credential_revisions')->where('credential_id', $c->id)->orderBy('revision')->get();
    expect($now->status)->toBe('corrected')->and($now->revision)->toBe(2)->and($now->public_code)->toBe($c->public_code)->and(json_decode($now->content, true)['holder'])->toBe('Amir Hakimi bin Ismail')
        ->and($revs->pluck('event')->all())->toBe(['issued', 'corrected'])->and(json_decode($revs[0]->content, true)['holder'])->toBe('Amir Hakimi') // the original is kept
        ->and(Jwt::verify($now->jwt)['payload']['vc']['credentialSubject']['identifier'][0]['identityHash'])->toBe('Amir Hakimi bin Ismail')
        ->and(DB::table('audit_events')->where('action', 'credential.corrected')->value('reason'))->toContain('father')
        ->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'credential.corrected')->where('channel', 'in_system')->value('body'))->toContain('Reason: Name must include');
    $this->get('/public/verify/'.$c->public_code)->assertSee('Valid, corrected')->assertSee('Amir Hakimi bin Ismail')->assertDontSee('Revoked');
    $this->actingAs($this->hakim)->get("/credentials/{$c->id}")->assertSee('History')->assertSee('Corrected');
});

it('lets only the deputy dean of the owning PTj correct, replace or revoke (FR-158)', function () {
    [, , $c] = issued();
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    foreach ([$this->aina, $this->farid, $this->amir] as $u) {
        $this->actingAs($u)->post("/credentials/{$c->id}/correct", ['holder' => 'X Y', 'reason' => 'Trying it'])->assertForbidden();
        $this->actingAs($u)->post("/credentials/{$c->id}/revoke", ['reason' => 'Trying it'])->assertForbidden();
        $this->actingAs($u)->get('/credentials')->assertForbidden();
    }
    $this->actingAs($fke)->get("/credentials/{$c->id}")->assertForbidden();
    $this->actingAs($fke)->post("/credentials/{$c->id}/replace", ['reason' => 'Trying it'])->assertForbidden();
    expect(DB::table('credentials')->find($c->id)->status)->toBe('valid');
});

// ---- replacement and revocation (FR-159, FR-160, FL-23)

it('replaces a credential with a new one and points the old code to it (FR-159)', function () {
    [, , $c] = issued();
    $this->actingAs($this->hakim)->post("/credentials/{$c->id}/replace", ['reason' => 'Wrong category printed'])->assertSessionHasNoErrors();
    $old = DB::table('credentials')->find($c->id);
    $new = DB::table('credentials')->find($old->replaced_by_id);
    expect($old->status)->toBe('replaced')->and($new->status)->toBe('valid')->and($new->public_code)->not->toBe($old->public_code)->and($new->number)->not->toBe($old->number)->and($new->replaces_id)->toBe($old->id)
        ->and(Jwt::verify($new->jwt)['ok'])->toBeTrue()->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'credential.replaced')->exists())->toBeTrue();
    $this->get('/public/verify/'.$old->public_code)->assertSee('Replaced')->assertSee($new->public_code)->assertDontSee('Valid, corrected');
    $this->get('/public/verify/'.$new->public_code)->assertSee('Valid');
    $this->actingAs($this->hakim)->post("/credentials/{$c->id}/correct", ['holder' => 'Late Edit', 'reason' => 'Too late now'])->assertForbidden();
    $this->actingAs($this->hakim)->post("/credentials/{$c->id}/revoke", ['reason' => 'Too late now'])->assertForbidden();
});

it('revokes a credential with a reason, and the public page says Revoked and shows no result (FR-160)', function () {
    [, , $c] = issued();
    $this->actingAs($this->hakim)->post("/credentials/{$c->id}/revoke", ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->hakim)->post("/credentials/{$c->id}/revoke", ['reason' => 'Result found to be invalid after an integrity case'])->assertSessionHasNoErrors();
    $page = $this->get('/public/verify/'.$c->public_code);
    $page->assertSee('Revoked')->assertSee('must not be relied on')->assertSee(today()->format('Y-m-d'), false)->assertDontSee('Amir Hakimi');
    $this->getJson('/public/verify/'.$c->public_code)->assertOk()->assertJson(['status' => 'revoked'])->assertJsonMissing(['recipient' => 'Amir Hakimi']);
    expect(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'credential.revoked')->where('channel', 'in_system')->value('body'))->toContain('Reason: Result found to be invalid')
        ->and(DB::table('credentials')->find($c->id)->status_reason)->toContain('integrity case');
    $this->actingAs($this->amir)->get("/my-credentials/{$c->id}")->assertSee('This credential was revoked');
});

it('never deletes or edits a credential or an earlier revision (FR-161)', function () {
    [, , $c] = issued();
    $this->actingAs($this->hakim)->post("/credentials/{$c->id}/correct", ['holder' => 'Amir Hakimi bin Ismail', 'reason' => 'Add the father\'s name']);
    expect(fn () => DB::transaction(fn () => DB::table('credentials')->where('id', $c->id)->delete()))->toThrow(\Illuminate\Database\QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('credential_revisions')->where('credential_id', $c->id)->delete()))->toThrow(\Illuminate\Database\QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('credential_revisions')->where('credential_id', $c->id)->update(['reason' => 'rewritten'])))->toThrow(\Illuminate\Database\QueryException::class);
    expect(DB::table('credential_revisions')->where('credential_id', $c->id)->count())->toBe(2);
});

it('opens a correction task when a verified result changes after the credential was issued (FR-125)', function () {
    [$o, $people, $c] = issued();
    $res = DB::table('assessment_results')->where('enrolment_id', eid($o, $this->amir))->where('assessment_component_id', DB::table('assessment_components')->where('csv_key', 'project_mark')->value('id'))->first();
    $this->actingAs($this->aina)->post("/results/{$res->id}/change", ['new_score' => '85', 'reason' => 'Marking error found'])->assertSessionHasNoErrors();
    $q = DB::table('result_change_requests')->first();
    $this->actingAs($this->hakim)->post("/results/$o/changes/{$q->id}", ['decision' => 'approve', 'note' => 'Checked against the script'])->assertSessionHasNoErrors();
    $t = DB::table('tasks')->where('kind', 'credential_correction')->first();
    expect($t)->not->toBeNull()->and($t->link)->toBe("/credentials/{$c->id}");
});

it('lists the credentials awarded on the achievement record', function () {
    [$o, , $c] = issued();
    $this->actingAs($this->amir)->get('/my-record')->assertSee($c->number);
});

it('verifies a module digital badge on the public verification page', function () {
    [$o, , $c] = issued();
    $b = DB::table('digital_badges')->first();
    expect($b)->not->toBeNull();
    $this->get('/public/verify/'.$b->code)->assertOk()
        ->assertSee('Valid (Digital Badge)')
        ->assertSee($b->code)
        ->assertSee('Open Badges 3.0');
    $json = $this->getJson('/public/verify/'.$b->code)->assertOk()->json();
    expect($json['status'])->toBe('valid')->and($json['type'])->toBe('digital_badge')->and($json['badge_code'])->toBe($b->code);
});
