<?php

use App\Models\User;
use App\Modules\Core\Settings;
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

if (! function_exists('submittedProposal')) {
function submittedProposal(): int
{
    $id = test()->aina ? completeUmc() : 0;
    test()->actingAs(test()->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1])->assertSessionHasNoErrors();

    return $id;
}
}


if (! function_exists('assign')) {
function assign(int $id, array $who, ?string $due = null): void
{
    test()->actingAs(test()->hakim)->post("/approvals/$id/reviewers", ['reviewers' => array_map(fn ($u) => $u->id, $who), 'due_on' => $due ?? today()->addDays(7)->toDateString(), 'scope' => 'Academic content'])->assertSessionHasNoErrors()->assertRedirect();
}
}


if (! function_exists('ratings')) {
function ratings(string $rating = 'satisfactory'): array
{
    $r = [];
    foreach (array_keys(config('proposals.review_items')) as $k) {
        $r[$k] = $rating;
    }

    return $r;
}
}


if (! function_exists('reviewDone')) {
function reviewDone(int $id, User $by, string $rec = 'support', array $needs = [], array $blocking = []): int
{
    $a = DB::table('review_assignments')->where('mc_version_id', $id)->where('reviewer_id', $by->id)->value('id');
    test()->actingAs($by)->post("/reviews/$a/coi", ['conflict' => 'none']);
    $rating = ratings();
    $comment = [];
    foreach ($needs as $k) {
        $rating[$k] = 'needs_amendment';
        $comment[$k] = 'Please fix '.$k;
    }
    test()->actingAs($by)->post("/reviews/$a/submit", ['rating' => $rating, 'comment' => $comment, 'blocking' => array_fill_keys($blocking, 1), 'recommendation' => $rec, 'summary' => 'A careful review of the content.'])->assertSessionHasNoErrors();

    return $a;
}
}


if (! function_exists('inReviewWithReview')) {
function inReviewWithReview(): int
{
    $id = submittedProposal();
    assign($id, [test()->lim]);
    reviewDone($id, test()->lim);

    return $id;
}
}


// ---- assigning reviewers (FR-60, FR-14)

it('lets the deputy dean assign reviewers with a due date and scope, moving the proposal into review (FR-60)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim, $this->siti]);
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('in_review')
        ->and(DB::table('review_assignments')->where('mc_version_id', $id)->orderBy('id')->pluck('kind')->all())->toBe(['reviewer', 'expert'])
        ->and(DB::table('audit_events')->where('action', 'review.assigned')->count())->toBe(2)
        ->and(DB::table('notifications')->where('user_id', $this->lim->id)->where('template_key', 'reviewer.assigned')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('user_id', $this->lim->id)->where('kind', 'review')->whereNull('completed_at')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'assign_reviewers')->where('ref_id', $id)->whereNull('completed_at')->exists())->toBeFalse()
        ->and(DB::table('tasks')->where('kind', 'decide')->where('ref_id', $id)->whereNull('completed_at')->exists())->toBeTrue();
});

it('refuses a reviewer who is the proposer, a past due date, and anyone but the deputy dean of the PTj (FR-14)', function () {
    $id = submittedProposal();
    $this->actingAs($this->hakim)->post("/approvals/$id/reviewers", ['reviewers' => [$this->aina->id], 'due_on' => today()->addDay()->toDateString(), 'scope' => 'Academic content'])->assertStatus(422);
    $this->actingAs($this->hakim)->post("/approvals/$id/reviewers", ['reviewers' => [$this->lim->id], 'due_on' => today()->subDay()->toDateString(), 'scope' => 'Academic content'])->assertSessionHasErrors('due_on');
    $this->actingAs($this->aina)->post("/approvals/$id/reviewers", ['reviewers' => [$this->lim->id], 'due_on' => today()->addDay()->toDateString(), 'scope' => 'x'])->assertForbidden();
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->get("/approvals/$id")->assertForbidden();
});

it('does not let a deputy dean decide on their own proposal (FR-14)', function () {
    $id = completeUmc();
    $this->aina->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id')]);
    $this->actingAs($this->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1]);
    $this->actingAs($this->aina)->post("/approvals/$id/reviewers", ['reviewers' => [$this->lim->id], 'due_on' => today()->addDay()->toDateString(), 'scope' => 'Academic content'])->assertForbidden();
});

// ---- conflict of interest (FR-17)

it('keeps the proposal closed until the conflict-of-interest answer is given (FR-17)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    $a = DB::table('review_assignments')->value('id');
    $this->actingAs($this->lim)->get("/reviews/$a")->assertSee('Step 1: conflict of interest')->assertDontSee('Employers cannot find')->assertDontSee('Proposal summary');
    $this->actingAs($this->lim)->post("/reviews/$a/coi", ['conflict' => 'none'])->assertRedirect("/reviews/$a");
    $this->actingAs($this->lim)->get("/reviews/$a")->assertSee('Proposal summary')->assertSee('Step 2: rate each item');
});

it('removes a reviewer who declares a conflict and tells the deputy dean (FR-17)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    $a = DB::table('review_assignments')->value('id');
    $this->actingAs($this->lim)->post("/reviews/$a/coi", ['conflict' => 'conflict'])->assertRedirect('/reviews');
    expect(DB::table('review_assignments')->find($a)->status)->toBe('declined')
        ->and(DB::table('tasks')->where('user_id', $this->lim->id)->where('kind', 'review')->whereNull('completed_at')->exists())->toBeFalse()
        ->and(DB::table('notifications')->where('user_id', $this->hakim->id)->where('template_key', 'reviewer.declined')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'assign_reviewers')->where('ref_id', $id)->whereNull('completed_at')->exists())->toBeTrue();
    $this->actingAs($this->lim)->get("/reviews/$a")->assertSee('removed from this review')->assertDontSee('Proposal summary');
    assign($id, [$this->lim]); // can be asked again
});

it('opens an invitation only for the person invited', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    $a = DB::table('review_assignments')->value('id');
    $this->actingAs($this->aina)->get("/reviews/$a")->assertForbidden();
    $this->actingAs($this->lim)->get('/reviews')->assertSee('Software Testing');
});

// ---- the review (FR-61 to FR-63)

it('needs every item rated, a comment for each amendment, and a recommendation (FR-61, FR-62)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    $a = DB::table('review_assignments')->value('id');
    $this->actingAs($this->lim)->post("/reviews/$a/coi", ['conflict' => 'none']);
    $this->actingAs($this->lim)->post("/reviews/$a/submit", ['rating' => [], 'recommendation' => 'support', 'summary' => 'Fine, thank you.'])->assertSessionHasErrors('rating.outcomes');
    $r = ratings();
    $r['slt'] = 'needs_amendment';
    $this->actingAs($this->lim)->post("/reviews/$a/submit", ['rating' => $r, 'recommendation' => 'amend', 'summary' => 'Needs work on hours.'])->assertSessionHasErrors('comment.slt');
    $this->actingAs($this->lim)->post("/reviews/$a/submit", ['rating' => ratings(), 'summary' => 'Fine, thank you.'])->assertSessionHasErrors('recommendation');
});

it('records the review, makes a tracked comment of each amendment, and asks the proposer to respond (FR-63)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    $a = reviewDone($id, $this->lim, 'amend', needs: ['slt', 'outcomes'], blocking: ['slt']);
    expect(DB::table('review_assignments')->find($a)->status)->toBe('submitted')
        ->and(DB::table('reviews')->where('review_assignment_id', $a)->value('recommendation'))->toBe('amend')
        ->and(DB::table('review_comments')->where('mc_version_id', $id)->count())->toBe(2)
        ->and(DB::table('review_comments')->where('mc_version_id', $id)->where('blocking', true)->count())->toBe(1)
        ->and(DB::table('tasks')->where('user_id', $this->aina->id)->where('kind', 'respond')->whereNull('completed_at')->exists())->toBeTrue();
    $this->actingAs($this->lim)->post("/reviews/$a/submit", ['rating' => ratings(), 'recommendation' => 'support', 'summary' => 'Second try.'])->assertForbidden(); // once only
});

it('asks about the parent course mapping only for a UMC (FR-61)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    $a = DB::table('review_assignments')->value('id');
    $this->actingAs($this->lim)->post("/reviews/$a/coi", ['conflict' => 'none']);
    $this->actingAs($this->lim)->get("/reviews/$a")->assertSee('Evidence of mapping to the parent course');
    $own = newOwn(80);
    expect(config('proposals.review_items'))->toHaveKey('parent_mapping');
    DB::table('mc_versions')->where('id', $own)->update(['status' => 'in_review']);
    DB::table('review_assignments')->insert(['mc_version_id' => $own, 'reviewer_id' => $this->lim->id, 'kind' => 'reviewer', 'assigned_by' => $this->hakim->id, 'due_on' => today(), 'scope' => 'x', 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()]);
    $b = DB::table('review_assignments')->where('mc_version_id', $own)->value('id');
    $this->actingAs($this->lim)->get("/reviews/$b")->assertDontSee('Evidence of mapping to the parent course');
});

it('lets an industry expert comment only, until the decision is recorded (FR-18)', function () {
    $id = submittedProposal();
    assign($id, [$this->siti]);
    $a = DB::table('review_assignments')->value('id');
    $this->actingAs($this->siti)->post("/reviews/$a/coi", ['conflict' => 'none']);
    $this->actingAs($this->siti)->get("/reviews/$a")->assertSee('no rating and no recommendation')->assertDontSee('Submit review');
    $this->actingAs($this->siti)->post("/reviews/$a/submit", ['rating' => ratings(), 'recommendation' => 'support', 'summary' => 'Looks fine to me.'])->assertForbidden();
    $this->actingAs($this->siti)->post("/reviews/$a/comment", ['text' => 'Add a module on observability'])->assertSessionHasNoErrors();
    expect(DB::table('review_comments')->where('mc_version_id', $id)->where('blocking', false)->value('text'))->toBe('Add a module on observability');
    DB::table('mc_versions')->where('id', $id)->update(['status' => 'approved']);
    $this->actingAs($this->siti)->post("/reviews/$a/comment", ['text' => 'Too late now'])->assertForbidden();
});

it('lets the proposer answer a comment, and the deputy dean resolve only an answered one (FR-63)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    reviewDone($id, $this->lim, 'amend', needs: ['slt'], blocking: ['slt']);
    $c = DB::table('review_comments')->value('id');
    $this->actingAs($this->hakim)->post("/approvals/$id/comments/$c/resolve", ['reason' => 'Looks fine now'])->assertStatus(422); // no answer yet
    $this->actingAs($this->aina)->get("/proposals/$id?tab=comments")->assertSee('Please fix slt')->assertSee('Blocking');
    $this->actingAs($this->lim)->post("/proposals/$id/comments/$c/respond", ['response' => 'x'])->assertForbidden();
    $this->actingAs($this->aina)->post("/proposals/$id/comments/$c/respond", ['response' => 'Hours corrected to 120'])->assertRedirect();
    expect(DB::table('review_comments')->find($c)->status)->toBe('answered')
        ->and(DB::table('tasks')->where('kind', 'respond')->whereNull('completed_at')->exists())->toBeFalse();
    $this->actingAs($this->hakim)->post("/approvals/$id/comments/$c/resolve", ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->hakim)->post("/approvals/$id/comments/$c/resolve", ['reason' => 'Looks fine now'])->assertSessionHasNoErrors();
    expect(DB::table('review_comments')->find($c)->status)->toBe('resolved');
});

// ---- endorsement (FR-64, FR-66)

it('refuses to endorse while a blocking comment is open or with no academic review (FR-63)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    $this->actingAs($this->hakim)->post("/approvals/$id/endorse", ['category_verified' => 1])->assertSessionHasErrors('endorse');
    reviewDone($id, $this->lim, 'amend', needs: ['slt'], blocking: ['slt']);
    $this->actingAs($this->hakim)->post("/approvals/$id/endorse", ['category_verified' => 1])->assertSessionHasErrors('endorse');
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('in_review');
    $this->actingAs($this->hakim)->get("/approvals/$id")->assertSee('Endorse will be refused');
});

it('needs the category to be verified, then endorses and hands the Senate task over (FR-64)', function () {
    $id = inReviewWithReview();
    $this->actingAs($this->hakim)->post("/approvals/$id/endorse", [])->assertSessionHasErrors('category_verified');
    $this->actingAs($this->hakim)->post("/approvals/$id/endorse", ['category_verified' => 1])->assertSessionHasNoErrors();
    $d = DB::table('approval_decisions')->first();
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('endorsed')
        ->and($d->stage)->toBe('endorsement')->and($d->verified_category)->toBe('umc')->and($d->decided_by)->toBe($this->hakim->id)
        ->and(DB::table('micro_credentials')->value('category_status'))->toBe('verified')
        ->and(DB::table('tasks')->where('kind', 'senate')->where('ref_id', $id)->whereNull('completed_at')->exists())->toBeTrue()
        ->and(DB::table('tasks')->where('kind', 'decide')->whereNull('completed_at')->exists())->toBeFalse()
        ->and(DB::table('notifications')->where('user_id', $this->aina->id)->where('template_key', 'proposal.endorsed')->exists())->toBeTrue();
});

it('returns a proposal for amendment, unlocks it, and takes the resubmission (FR-69)', function () {
    $id = submittedProposal();
    assign($id, [$this->lim]);
    $this->actingAs($this->hakim)->post("/approvals/$id/return", ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($this->hakim)->post("/approvals/$id/return", ['reason' => 'The hours do not add up'])->assertSessionHasNoErrors();
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('returned')
        ->and(DB::table('notifications')->where('user_id', $this->aina->id)->where('template_key', 'proposal.returned')->exists())->toBeTrue();
    $this->actingAs($this->aina)->post("/proposals/$id/basics", ['name_en' => 'Software Testing v2', 'name_ms' => 'Ujian Perisian', 'delivery_mode' => 'online', 'delivery_language' => 'English'])->assertRedirect(); // unlocked
    $this->actingAs($this->aina)->get("/proposals/$id")->assertSee('Returned for amendment')->assertSee('The hours do not add up');
    $this->actingAs($this->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1])->assertSessionHasNoErrors();
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('submitted')
        ->and(DB::table('tasks')->where('kind', 'assign_reviewers')->where('ref_id', $id)->whereNull('completed_at')->exists())->toBeTrue();
});

it('records a proposal as not supported and ends the cycle', function () {
    $id = submittedProposal();
    $this->actingAs($this->hakim)->post("/approvals/$id/reject", ['reason' => 'Outside the faculty scope'])->assertSessionHasNoErrors();
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('not_approved')
        ->and(DB::table('approval_decisions')->value('decision'))->toBe('not_supported');
    $this->actingAs($this->aina)->post("/proposals/$id/basics", ['name_en' => 'x'])->assertForbidden();
});

// ---- Senate decision (D-11, FR-67, FR-68, FR-70)

if (! function_exists('endorsed')) {
function endorsed(): int
{
    $id = inReviewWithReview();
    test()->actingAs(test()->hakim)->post("/approvals/$id/endorse", ['category_verified' => 1]);

    return $id;
}
}


if (! function_exists('senate')) {
function senate(int $id, array $over = []): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(test()->hakim)->post("/approvals/$id/senate", $over + ['meeting_ref' => 'Senate 8/2026', 'meeting_date' => today()->toDateString(), 'decision' => 'approved', 'comments' => 'Approved as presented in the minutes.']);
}
}


it('records an approval with the meeting reference and fixes the category, setting no credit status (FR-67, FR-70)', function () {
    $id = endorsed();
    senate($id)->assertSessionHasNoErrors();
    $d = DB::table('approval_decisions')->where('stage', 'senate')->first();
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('approved')
        ->and($d->meeting_ref)->toBe('Senate 8/2026')->and($d->decided_by)->toBe($this->hakim->id)->and($d->decided_role)->toBe('deputy_dean')
        ->and(DB::table('micro_credentials')->value('category_status'))->toBe('confirmed')
        ->and(DB::table('audit_events')->where('action', 'senate.decision_recorded')->exists())->toBeTrue()
        ->and(DB::table('notifications')->where('user_id', $this->aina->id)->where('template_key', 'decision.made')->where('channel', 'in_system')->value('body'))->toContain('Senate 8/2026')
        ->and(DB::table('tasks')->where('kind', 'senate')->whereNull('completed_at')->exists())->toBeFalse();
    foreach (['qv_status' => 'verified'] as $col => $bad) {
        expect(DB::table('mc_versions')->where('id', $id)->value($col))->not->toBe($bad); // approval does not imply Quality Verification
    }
    $this->actingAs($this->aina)->get("/proposals/$id")->assertSee('Approved', false)->assertSee('locked');
});

it('records the other Senate outcomes: conditions, not approved, returned, deferred', function () {
    $id = endorsed();
    senate($id, ['decision' => 'approved_conditions'])->assertSessionHasErrors('conditions');
    senate($id, ['decision' => 'deferred'])->assertSessionHasNoErrors();
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('endorsed')->and(DB::table('tasks')->where('kind', 'senate')->whereNull('completed_at')->exists())->toBeTrue();
    senate($id, ['decision' => 'approved_conditions', 'conditions' => 'Add a data protection note'])->assertSessionHasNoErrors();
    expect(DB::table('mc_versions')->where('id', $id)->value('status'))->toBe('approved')->and(DB::table('approval_decisions')->where('decision', 'approved_conditions')->value('conditions'))->toBe('Add a data protection note');

    $second = endorsedSecond();
    senate($second, ['decision' => 'returned'])->assertSessionHasNoErrors();
    expect(DB::table('mc_versions')->where('id', $second)->value('status'))->toBe('returned');
    $third = endorsedSecond();
    senate($third, ['decision' => 'not_approved'])->assertSessionHasNoErrors();
    expect(DB::table('mc_versions')->where('id', $third)->value('status'))->toBe('not_approved');
});

if (! function_exists('endorsedSecond')) {
function endorsedSecond(): int
{
    $id = completeUmc();
    test()->actingAs(test()->aina)->post("/proposals/$id/submit", ['ip_confirmed' => 1]);
    DB::table('mc_versions')->where('id', $id)->update(['status' => 'endorsed']);
    DB::table('approval_decisions')->insert(['mc_version_id' => $id, 'stage' => 'endorsement', 'decision' => 'endorsed', 'decided_by' => test()->hakim->id, 'decided_role' => 'deputy_dean', 'created_at' => now()]);

    return $id;
}
}


it('records a Senate decision only after endorsement, not dated before it, with a reference (FR-67)', function () {
    $id = inReviewWithReview();
    senate($id)->assertForbidden(); // not endorsed yet
    $this->actingAs($this->hakim)->post("/approvals/$id/endorse", ['category_verified' => 1]);
    senate($id, ['meeting_date' => today()->subDays(3)->toDateString()])->assertSessionHasErrors('meeting_date');
    senate($id, ['meeting_ref' => ''])->assertSessionHasErrors('meeting_ref');
    senate($id, ['meeting_date' => today()->addDay()->toDateString()])->assertSessionHasErrors('meeting_date');
    $this->actingAs($this->hakim)->get("/approvals/$id/senate")->assertSee('does not set any credit');
});

it('takes the approving role from the settings and never from the administrator (FR-16, D-12)', function () {
    $id = endorsed();
    $admin = User::where('name', 'Farid Hashim')->first();
    $this->actingAs($admin)->post('/admin/settings/approval.route.senate', ['value' => 'codl_admin', 'reason' => 'Trying to give it to CODL'])->assertSessionHasErrors('value');
    $this->actingAs($admin)->post("/approvals/$id/senate", ['meeting_ref' => 'x', 'meeting_date' => today()->toDateString(), 'decision' => 'approved', 'comments' => 'trying it'])->assertForbidden();
    $this->actingAs($admin)->post('/admin/settings/approval.route.senate', ['value' => 'lecturer', 'reason' => 'Moves to another academic role']);
    senate($id)->assertForbidden(); // Hakim no longer holds the configured role
    expect(app(Settings::class)->get('approval.route.senate'))->toBe('lecturer');
});

it('binds an approval to the exact version and shows the decision pack (FR-65, FR-68, FR-73)', function () {
    $id = endorsed();
    $this->actingAs($this->hakim)->get("/approvals/$id/pack")->assertOk()->assertSee('Category verification')->assertSee('Verified by Hakim Rahman')->assertSee('Review summary')->assertSee('sets no Quality Verification');
    senate($id);
    expect(DB::table('approval_decisions')->where('stage', 'senate')->value('mc_version_id'))->toBe($id);
    $other = completeUmc();
    expect(DB::table('mc_versions')->where('id', $other)->value('status'))->toBe('draft');
});

it('lists proposals in the deputy dean scope with status counts (DD-02)', function () {
    $id = submittedProposal();
    $this->actingAs($this->hakim)->get('/approvals')->assertSee('Software Testing')->assertSee('Submitted (1)');
    expect(\Illuminate\Support\Str::after($this->actingAs($this->hakim)->get('/approvals?status=approved')->getContent(), '<main'))->not->toContain('Software Testing'); // the notices menu may name it
    $this->actingAs($this->hakim)->get('/tasks?area=proposals')->assertSee('Assign reviewers: Software Testing');
    $t = DB::table('tasks')->where('kind', 'assign_reviewers')->value('id');
    $this->actingAs($this->hakim)->get("/tasks/$t")->assertRedirect("/approvals/$id");
});
