<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->siti = User::where('name', 'Ir. Siti Aminah')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create(['role' => 'lecturer', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
});

// An approved version 1 (helpers of the approval tests: endorsed(), senate()).
if (! function_exists('approvedV1')) {
function approvedV1(): array
{
    $v1 = endorsed();
    senate($v1)->assertSessionHasNoErrors();

    return [$v1, (int) DB::table('mc_versions')->where('id', $v1)->value('micro_credential_id')];
}
}


if (! function_exists('requestChange')) {
function requestChange(int $mc, array $triggers, array $over = [])
{
    return test()->actingAs(test()->aina)->post("/micro-credentials/$mc/change", $over + ['triggers' => $triggers, 'reason' => 'Update the outcomes after employer feedback', 'impact_note' => 'Participants see the new outcomes']);
}
}


if (! function_exists('v2Of')) {
function v2Of(int $mc): ?object
{
    return DB::table('mc_versions')->where('micro_credential_id', $mc)->where('version_no', 2)->first();
}
}


if (! function_exists('approveV2')) {
function approveV2(int $v2, string $rule = 'move_new'): void
{
    test()->actingAs(test()->aina)->post("/proposals/$v2/submit", ['ip_confirmed' => 1])->assertSessionHasNoErrors();
    assign($v2, [test()->lim]);
    reviewDone($v2, test()->lim);
    test()->actingAs(test()->hakim)->post("/approvals/$v2/endorse", ['category_verified' => 1])->assertSessionHasNoErrors();
    senate($v2, ['transition_rule' => $rule])->assertSessionHasNoErrors();
}
}


// ---- version history (FR-74)

it('sets the effective date at approval and lists the version in My micro-credentials (FR-74)', function () {
    [$v1, $mc] = approvedV1();
    expect(DB::table('mc_versions')->where('id', $v1)->value('effective_date'))->toStartWith(today()->toDateString());
    $this->actingAs($this->aina)->get('/micro-credentials')->assertSee('Software Testing')->assertSee('Version 1')->assertSee('Approved');
    $this->actingAs($this->aina)->get("/micro-credentials/$mc")->assertSee('First version')->assertSee('Senate 8/2026')->assertSee('Request a change');
});

it('offers changes only on an approved version, and only to the owner', function () {
    $id = completeUmc(); // still a draft
    $mc = DB::table('mc_versions')->where('id', $id)->value('micro_credential_id');
    $this->actingAs($this->aina)->get("/micro-credentials/$mc/change")->assertSee('Only an approved version can be changed');
    requestChange($mc, ['outcomes'])->assertForbidden();
    $this->actingAs($this->aina)->post("/micro-credentials/$mc/reoffer", ['summary' => 'Offer again'])->assertForbidden();
    $this->actingAs($this->lim)->get("/micro-credentials/$mc")->assertForbidden();
});

// ---- classification (FR-75)

it('classifies a change as material when it touches a listed item, and never lets it be classified down (FR-75)', function (string $trigger) {
    [$v1, $mc] = approvedV1();
    requestChange($mc, [$trigger], ['impact_note' => ''])->assertSessionHasNoErrors();
    $cr = DB::table('change_requests')->first();
    expect($cr->class)->toBe('material')->and(json_decode($cr->triggers, true))->toBe([$trigger])->and(v2Of($mc))->not->toBeNull();
})->with(['outcomes', 'slt_credit', 'mqf', 'content', 'assessment', 'structure', 'parent', 'qv', 'delivery', 'partner', 'integrity']);

it('records a minor change with a description and impact note, and creates no version (FR-77)', function () {
    [$v1, $mc] = approvedV1();
    requestChange($mc, [], ['impact_note' => ''])->assertSessionHasErrors('impact_note');
    requestChange($mc, ['not-a-trigger'])->assertSessionHasNoErrors(); // an unknown item counts for nothing
    $cr = DB::table('change_requests')->first();
    expect($cr->class)->toBe('minor')->and($cr->status)->toBe('recorded')->and($cr->to_version_id)->toBeNull()->and(v2Of($mc))->toBeNull()
        ->and(DB::table('audit_events')->where('action', 'change.minor_logged')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'ack_change')->where('ref_id', $cr->id)->whereNull('completed_at')->exists())->toBeTrue()
        ->and(DB::table('notifications')->where('user_id', $this->hakim->id)->where('template_key', 'change.minor')->exists())->toBeTrue();
});

it('lets the deputy dean acknowledge a minor change and logs it for the quality record (FR-77)', function () {
    [$v1, $mc] = approvedV1();
    requestChange($mc, []);
    $cr = DB::table('change_requests')->value('id');
    $this->actingAs($this->hakim)->get("/changes/$cr")->assertSee('Acknowledge')->assertSee('Participants see the new outcomes');
    $this->actingAs($this->aina)->post("/changes/$cr/acknowledge")->assertForbidden();
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->post("/changes/$cr/acknowledge")->assertForbidden();
    $this->actingAs($this->hakim)->post("/changes/$cr/acknowledge")->assertRedirect();
    $row = DB::table('change_requests')->find($cr);
    expect($row->status)->toBe('acknowledged')->and($row->acknowledged_by)->toBe($this->hakim->id)->and($row->cqi_logged_at)->not->toBeNull()
        ->and(DB::table('tasks')->where('kind', 'ack_change')->whereNull('completed_at')->exists())->toBeFalse();
    $this->actingAs($this->hakim)->post("/changes/$cr/acknowledge")->assertForbidden(); // once
});

// ---- material change (FR-76, FR-78)

it('makes a material change a new draft version with the content copied, not yet in effect (FR-76)', function () {
    [$v1, $mc] = approvedV1();
    requestChange($mc, ['outcomes'])->assertRedirect();
    $v2 = v2Of($mc);
    expect($v2->status)->toBe('draft')->and($v2->proposal_type)->toBe('revision')->and($v2->supersedes_id)->toBe($v1)->and($v2->change_class)->toBe('material')
        ->and($v2->change_summary)->toBe('Update the outcomes after employer feedback')->and((bool) $v2->ip_confirmed)->toBeFalse()->and($v2->effective_date)->toBeNull()
        ->and(DB::table('mc_versions')->where('id', $v1)->value('status'))->toBe('approved') // version 1 stays in force
        ->and(DB::table('mc_modules')->where('mc_version_id', $v2->id)->sum('slt_hours'))->toBe(DB::table('mc_modules')->where('mc_version_id', $v1)->sum('slt_hours'))
        ->and(DB::table('learning_outcomes')->where('mc_version_id', $v2->id)->count())->toBe(DB::table('learning_outcomes')->where('mc_version_id', $v1)->count())
        ->and(DB::table('assessment_component_outcomes')->whereIn('assessment_component_id', DB::table('assessment_components')->where('mc_version_id', $v2->id)->pluck('id'))->count())->toBe(1)
        ->and(DB::table('proposal_readiness')->where('mc_version_id', $v2->id)->where('status', 'yes')->count())->toBe(count(config('proposals.readiness')))
        ->and(DB::table('change_requests')->value('status'))->toBe('new_version')
        ->and(DB::table('audit_events')->where('action', 'change.material_recorded')->exists())->toBeTrue();
    // the copy is independent: editing it leaves version 1 alone
    $lo = DB::table('learning_outcomes')->where('mc_version_id', $v2->id)->value('id');
    $this->actingAs($this->aina)->post("/proposals/{$v2->id}/outcomes", ['rows' => [$lo => ['text_en' => 'A new outcome']]]);
    expect(DB::table('learning_outcomes')->where('mc_version_id', $v1)->value('text_en'))->not->toBe('A new outcome');
    $this->actingAs($this->aina)->get("/proposals/{$v2->id}")->assertSee('Version 2')->assertSee('Revision')->assertSee('Update the outcomes');
});

it('allows one version in progress at a time', function () {
    [$v1, $mc] = approvedV1();
    requestChange($mc, ['outcomes']);
    requestChange($mc, [])->assertForbidden();
    $this->actingAs($this->aina)->post("/micro-credentials/$mc/reoffer", ['summary' => 'Offer again'])->assertForbidden();
    $this->actingAs($this->aina)->get("/micro-credentials/$mc/change")->assertSee('still in progress');
});

it('flags a material change to a Quality-Verified SAMC and asks for a new verification (FR-78)', function () {
    [$v1, $mc] = approvedV1();
    DB::table('mc_versions')->where('id', $v1)->update(['qv_status' => 'verified']);
    requestChange($mc, ['content']);
    expect((bool) DB::table('change_requests')->value('requires_qv'))->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'qv_reverify')->whereNull('completed_at')->exists())->toBeTrue();
    $this->actingAs($this->aina)->get("/micro-credentials/$mc")->assertSee('Quality Verification repeats');
});

it('does not flag QV when the version is not Quality-Verified', function () {
    [$v1, $mc] = approvedV1();
    requestChange($mc, ['content']);
    expect((bool) DB::table('change_requests')->value('requires_qv'))->toBeFalse()->and(DB::table('tasks')->where('kind', 'qv_reverify')->exists())->toBeFalse();
});

// ---- approval and the transition rule (FR-80)

it('takes a new version through approval again, supersedes the old one, and records the transition rule (FR-76, FR-80)', function () {
    [$v1, $mc] = approvedV1();
    requestChange($mc, ['outcomes']);
    $v2 = v2Of($mc)->id;
    approveV2($v2, 'finish_old');

    expect(DB::table('mc_versions')->where('id', $v2)->value('status'))->toBe('approved')
        ->and(DB::table('mc_versions')->where('id', $v1)->value('status'))->toBe('superseded')
        ->and(DB::table('mc_versions')->where('id', $v2)->value('effective_date'))->not->toBeNull()
        ->and(DB::table('version_transition_rules')->where('mc_version_id', $v2)->value('rule'))->toBe('finish_old')
        ->and(DB::table('version_transition_rules')->value('from_version_id'))->toBe($v1)
        ->and(DB::table('change_requests')->value('status'))->toBe('approved')
        ->and(DB::table('audit_events')->where('action', 'version.superseded')->exists())->toBeTrue();
    $this->actingAs($this->aina)->get("/micro-credentials/$mc")->assertSee('Version 2')->assertSee('Superseded')->assertSee('Current participants finish on the old version');
    $this->actingAs($this->aina)->get('/micro-credentials')->assertSee('Version 2');
});

it('refuses to approve a replacing version without a transition rule, and asks for a note on equivalence (FR-80)', function () {
    [$v1, $mc] = approvedV1();
    requestChange($mc, ['outcomes']);
    $v2 = v2Of($mc)->id;
    $this->actingAs($this->aina)->post("/proposals/$v2/submit", ['ip_confirmed' => 1]);
    assign($v2, [$this->lim]);
    reviewDone($v2, $this->lim);
    $this->actingAs($this->hakim)->post("/approvals/$v2/endorse", ['category_verified' => 1]);
    $this->actingAs($this->hakim)->get("/approvals/$v2/senate")->assertSee('replaces version 1');
    senate($v2)->assertSessionHasErrors('transition_rule');
    senate($v2, ['transition_rule' => 'equivalence'])->assertSessionHasErrors('transition_note');
    expect(DB::table('mc_versions')->where('id', $v1)->value('status'))->toBe('approved');
    senate($v2, ['transition_rule' => 'equivalence', 'transition_note' => 'Old outcome 2 equals new outcome 2'])->assertSessionHasNoErrors();
    expect(DB::table('version_transition_rules')->value('note'))->toBe('Old outcome 2 equals new outcome 2');
});

it('does not ask for a transition rule on a first version', function () {
    $v1 = endorsed();
    $this->actingAs($this->hakim)->get("/approvals/$v1/senate")->assertDontSee('replaces version');
    senate($v1)->assertSessionHasNoErrors();
});

it('leaves the old version in force when the new one is not approved, and allows a new request (FR-76)', function () {
    [$v1, $mc] = approvedV1();
    requestChange($mc, ['outcomes']);
    $v2 = v2Of($mc)->id;
    $this->actingAs($this->aina)->post("/proposals/$v2/submit", ['ip_confirmed' => 1]);
    $this->actingAs($this->hakim)->post("/approvals/$v2/reject", ['reason' => 'The new outcomes are out of scope']);
    expect(DB::table('mc_versions')->where('id', $v1)->value('status'))->toBe('approved')->and(DB::table('mc_versions')->where('id', $v2)->value('status'))->toBe('not_approved');
    requestChange($mc, [], ['impact_note' => 'No effect'])->assertSessionHasNoErrors(); // the micro-credential can be changed again
});

it('starts a re-offer from the approved version (FR-34)', function () {
    [$v1, $mc] = approvedV1();
    $this->actingAs($this->aina)->post("/micro-credentials/$mc/reoffer", ['summary' => ''])->assertSessionHasErrors('summary');
    $this->actingAs($this->aina)->post("/micro-credentials/$mc/reoffer", ['summary' => 'Second cohort in 2027'])->assertRedirect();
    $v2 = v2Of($mc);
    expect($v2->proposal_type)->toBe('reoffer')->and($v2->change_class)->toBe('reoffer')->and($v2->supersedes_id)->toBe($v1)->and($v2->status)->toBe('draft');
    $this->actingAs($this->aina)->get("/proposals/{$v2->id}")->assertSee('Re-offer')->assertSee('Second cohort in 2027');
});

// ---- never deleted (FR-79, BR-39)

it('never deletes a version that was submitted (FR-79)', function () {
    [$v1] = approvedV1();
    expect(fn () => DB::transaction(fn () => DB::table('mc_versions')->where('id', $v1)->delete()))->toThrow(\Illuminate\Database\QueryException::class);
    $draft = completeUmc();
    DB::table('proposal_readiness')->where('mc_version_id', $draft)->delete();
    expect(DB::table('mc_versions')->where('id', $v1)->exists())->toBeTrue();
});
