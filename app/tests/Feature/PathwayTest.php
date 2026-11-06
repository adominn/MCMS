<?php

use App\Models\User;
use App\Modules\Pathways\Plans;
use Database\Seeders\DatabaseSeeder;
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

/** BITS has 12 graduating credits here, so that three 3-credit courses are 75% from outside and two are 50%. */
if (! function_exists('smallBits')) {
function smallBits(): object
{
    DB::table('programmes')->where('code', 'BITS')->update(['total_credits' => 12, 'mode' => 'conventional']);
    foreach (['BITS 1124' => 'Algorithms', 'BITS 1125' => 'Databases'] as $code => $name) {
        DB::table('parent_courses')->insertOrIgnore(['programme_id' => bits()->id, 'code' => $code, 'name' => $name, 'credit' => 3, 'slt_hours' => 120, 'clos' => 'One;Two', 'created_at' => now(), 'updated_at' => now()]);
    }

    return bits();
}
}


if (! function_exists('course')) {
function course(string $code): int
{
    return (int) DB::table('parent_courses')->where('code', $code)->value('id');
}
}


if (! function_exists('newPathway')) {
function newPathway(array $over = [])
{
    smallBits();

    return test()->actingAs(test()->aina)->post('/pathways', $over + ['programme_id' => bits()->id, 'plos' => "PLO1\nPLO2", 'bok' => 'Algorithms']);
}
}


if (! function_exists('mapItem')) {
function mapItem(int $pw, int $mc, string $courseCode, array $over = [])
{
    return test()->post("/pathways/$pw/items", $over + ['micro_credential_id' => $mc, 'parent_course_id' => course($courseCode), 'credits' => 3, 'lo_equivalence' => 'high', 'content_equivalence' => 'high', 'preliminary' => 'suitable', 'plo' => ['PLO1'], 'contribution' => ['PLO1' => 'main']]);
}
}


/** A published pathway mapping one approved UMC to the given courses. Returns [pathway id, micro-credential id, item ids]. */
if (! function_exists('publishedPathway')) {
function publishedPathway(array $courses = ['BITS 1123', 'BITS 1124', 'BITS 1125']): array
{
    [, $mc] = approvedV1();
    newPathway()->assertSessionHasNoErrors();
    $pw = (int) DB::table('pathways')->max('id');
    foreach ($courses as $c) {
        mapItem($pw, $mc, $c)->assertSessionHasNoErrors();
    }
    test()->actingAs(test()->hakim)->post("/pathways/$pw/approve", ['approval_ref' => 'Senate 9/2026', 'approved_on' => today()->toDateString()])->assertSessionHasNoErrors();
    test()->post("/pathways/$pw/publish")->assertSessionHasNoErrors();

    return [$pw, $mc, DB::table('pathway_items')->where('pathway_id', $pw)->orderBy('id')->pluck('id')->all()];
}
}


if (! function_exists('plan')) {
function plan(array $items, array $over = [])
{
    $pw = (int) DB::table('pathways')->max('id');

    return test()->actingAs(test()->hakim)->post('/plans', $over + ['user_id' => test()->amir->id, 'pathway_id' => $pw, 'items' => $items, 'other_credits' => 0, 'residency_semesters' => 1, 'delivery_method' => 'conventional',
        'remaining' => [['kind' => 'project', 'name' => 'Final year project', 'status' => 'todo']]]);
}
}


// ---- mapping (FR-spdefine, FR-spmap, FR-spcreditbearing, FR-spapprove, FR-spnotransfer)

it('maps only credit-bearing credentials: an MCE or a SAMC without valid QV is refused (BR-35)', function () {
    [, $mc] = approvedV1();
    newPathway()->assertSessionHasNoErrors();
    $pw = (int) DB::table('pathways')->max('id');
    DB::table('micro_credentials')->where('id', $mc)->update(['category' => 'mce']);
    mapItem($pw, $mc, 'BITS 1123')->assertSessionHasErrors('micro_credential_id');
    DB::table('micro_credentials')->where('id', $mc)->update(['category' => 'samc']);
    mapItem($pw, $mc, 'BITS 1123')->assertSessionHasErrors('micro_credential_id'); // no valid Quality Verification
    DB::table('qv_records')->insert(['micro_credential_id' => $mc, 'cycle_no' => 1, 'status' => 'verified', 'valid_from' => today()->subMonth(), 'valid_until' => today()->addYear(), 'created_at' => now(), 'updated_at' => now()]);
    mapItem($pw, $mc, 'BITS 1123')->assertSessionHasNoErrors();
    expect(DB::table('pathway_items')->count())->toBe(1);
});

it('checks the course, the credits and the preliminary result of a mapping (FR-spmap)', function () {
    [, $mc] = approvedV1();
    newPathway();
    $pw = (int) DB::table('pathways')->max('id');
    mapItem($pw, $mc, 'BITS 1123', ['credits' => 4])->assertSessionHasErrors('credits'); // the course carries 3
    mapItem($pw, $mc, 'BITS 1123', ['lo_equivalence' => 'none'])->assertSessionHasErrors('preliminary');
    $other = DB::table('programmes')->insertGetId(['code' => 'BITE', 'name' => 'Other', 'mqf_level' => 6, 'mode' => 'conventional', 'accreditation_status' => 'accredited', 'total_credits' => 120, 'created_at' => now(), 'updated_at' => now()]);
    $oc = DB::table('parent_courses')->insertGetId(['programme_id' => $other, 'code' => 'BITE 1', 'name' => 'x', 'credit' => 3, 'slt_hours' => 120, 'clos' => 'a', 'created_at' => now(), 'updated_at' => now()]);
    test()->post("/pathways/$pw/items", ['micro_credential_id' => $mc, 'parent_course_id' => $oc, 'credits' => 3, 'lo_equivalence' => 'high', 'content_equivalence' => 'high', 'preliminary' => 'suitable'])->assertSessionHasErrors('parent_course_id');
});

it('warns when a programme learning outcome or domain has no coverage (FR-spplo, BR-36)', function () {
    [, $mc] = approvedV1();
    newPathway();
    $pw = (int) DB::table('pathways')->max('id');
    mapItem($pw, $mc, 'BITS 1123')->assertSessionHasNoErrors();
    $this->get("/pathways/$pw")->assertSee('No coverage yet for')->assertSee('PLO PLO2')->assertSee('domain Algorithms');
});

it('needs the authority to approve before publishing, and the lecturer cannot (FR-spapprove, D-11)', function () {
    [, $mc] = approvedV1();
    newPathway();
    $pw = (int) DB::table('pathways')->max('id');
    $this->post("/pathways/$pw/approve", ['approval_ref' => 'X', 'approved_on' => today()->toDateString()])->assertForbidden();
    $this->actingAs($this->hakim)->post("/pathways/$pw/approve", ['approval_ref' => 'X', 'approved_on' => today()->toDateString()])->assertStatus(422); // nothing mapped
    $this->actingAs($this->aina);
    mapItem($pw, $mc, 'BITS 1123');
    $this->actingAs($this->hakim)->post("/pathways/$pw/publish")->assertStatus(422); // not approved yet
    $this->post("/pathways/$pw/approve", ['approved_on' => today()->toDateString()])->assertSessionHasErrors('approval_ref');
    $this->post("/pathways/$pw/approve", ['approval_ref' => 'Senate 9/2026', 'approved_on' => today()->toDateString()])->assertSessionHasNoErrors();
    expect(DB::table('pathways')->find($pw)->status)->toBe('approved');
    $this->get('/public/pathways')->assertDontSee('BITS'); // approved is not yet published
    $this->post("/pathways/$pw/publish")->assertSessionHasNoErrors();
    $this->get('/public/pathways')->assertSee('Bachelor of Computer Science')->assertSee('transfers no credit')->assertSee('leads to a degree automatically');
    $this->get("/public/pathways/$pw/print")->assertOk()->assertSee('Degree Articulation Pathway Specification')->assertSee('BITS 1123');
    $this->actingAs($this->aina);
    mapItem($pw, $mc, 'BITS 1124')->assertStatus(422); // a published mapping is not edited
});

it('transfers no credit by itself: publishing a mapping creates no transfer, deposit or ledger entry (FR-spnotransfer)', function () {
    publishedPathway();
    expect(DB::table('transfer_applications')->count())->toBe(0)->and(DB::table('abc_deposits')->count())->toBe(0)->and(DB::table('credit_ledger_entries')->count())->toBe(0);
});

// ---- individual plan (FR-spplan, FR-sp30, FR-spresidency, FR-spdecision, FR-spadmit, FR-spaward)

it('marks a plan below the home-credit share as not meeting the rule, and it cannot be suitable (FR-sp30, BR-05)', function () {
    [, , $items] = publishedPathway();
    plan($items)->assertSessionHasNoErrors(); // 9 of 12 credits from outside: 25% earned at UTeM
    $s = DB::table('stackable_plans')->first();
    expect((float) $s->utem_percent)->toBe(25.0)->and($s->setting_version_id)->not->toBeNull()->and($s->status)->toBe('needs_review')
        ->and((bool) DB::table('plan_requirements')->where('stackable_plan_id', $s->id)->where('requirement_key', 'home_credit')->value('met'))->toBeFalse();
    $this->get("/plans/{$s->id}")->assertSee('does not mean the rest can be transferred automatically');
    DB::table('admissions')->insert(['user_id' => $this->amir->id, 'programme_id' => bits()->id, 'intake' => '2027/1', 'recorded_by' => $this->hakim->id, 'recorded_at' => now()]);
    $this->post("/plans/{$s->id}/decision", ['decision' => 'suitable'])->assertSessionHasErrors('decision');
    $this->post("/plans/{$s->id}/decision", ['decision' => 'conditions', 'conditions' => 'Take more courses at UTeM'])->assertSessionHasErrors('decision');
    $this->post("/plans/{$s->id}/decision", ['decision' => 'not_yet', 'conditions' => 'Earn more credit at UTeM'])->assertSessionHasNoErrors();
    expect(DB::table('stackable_plans')->find($s->id)->status)->toBe('not_yet')->and(DB::table('notifications')->where('user_id', $this->amir->id)->where('template_key', 'plan.decided')->exists())->toBeTrue();
});

it('needs a recorded admission for Suitable to proceed, however good the plan (FR-spadmit, BR-34)', function () {
    [, , $items] = publishedPathway();
    plan(array_slice($items, 0, 2))->assertSessionHasNoErrors(); // 6 of 12: 50% at UTeM
    $s = DB::table('stackable_plans')->first();
    expect((float) $s->utem_percent)->toBe(50.0);
    $this->post("/plans/{$s->id}/decision", ['decision' => 'suitable'])->assertSessionHasErrors('decision');
    expect(DB::table('stackable_plans')->find($s->id)->status)->toBe('needs_review');
    DB::table('admissions')->insert(['user_id' => $this->amir->id, 'programme_id' => bits()->id, 'intake' => '2027/1', 'recorded_by' => $this->hakim->id, 'recorded_at' => now()]);
    $this->post("/plans/{$s->id}/decision", ['decision' => 'suitable'])->assertSessionHasNoErrors();
    expect(DB::table('stackable_plans')->find($s->id)->status)->toBe('suitable');
});

it('needs conditions for Suitable with conditions (FR-spdecision)', function () {
    [, , $items] = publishedPathway();
    plan(array_slice($items, 0, 2));
    $s = DB::table('stackable_plans')->first();
    $this->post("/plans/{$s->id}/decision", ['decision' => 'conditions'])->assertSessionHasErrors('conditions');
    $this->post("/plans/{$s->id}/decision", ['decision' => 'conditions', 'conditions' => 'Residency in semester 3'])->assertSessionHasNoErrors();
    expect(DB::table('stackable_plans')->find($s->id)->decision)->toBe('conditions')->and(DB::table('stackable_plans')->find($s->id)->status)->toBe('suitable');
});

it('requires residency for a conventional programme and flags ODL delivery for review (FR-spresidency, BR-06)', function () {
    [, , $items] = publishedPathway();
    plan(array_slice($items, 0, 2), ['residency_semesters' => 0]);
    $s = DB::table('stackable_plans')->first();
    expect((bool) DB::table('plan_requirements')->where('stackable_plan_id', $s->id)->where('requirement_key', 'residency')->value('met'))->toBeFalse();
    $this->post("/plans/{$s->id}", ['items' => array_slice($items, 0, 2), 'residency_semesters' => 1, 'delivery_method' => 'odl'])->assertSessionHasNoErrors();
    $r = DB::table('plan_requirements')->where('stackable_plan_id', $s->id)->where('requirement_key', 'residency')->first();
    expect((bool) $r->met)->toBeFalse()->and($r->detail)->toContain('ODL scope');
    $this->post("/plans/{$s->id}", ['items' => array_slice($items, 0, 2), 'residency_semesters' => 1, 'delivery_method' => 'conventional']);
    expect((bool) DB::table('plan_requirements')->where('stackable_plan_id', $s->id)->where('requirement_key', 'residency')->value('met'))->toBeTrue();
});

it('sends an updated plan back to Needs review (FR-spplan)', function () {
    [, , $items] = publishedPathway();
    plan($items);
    $s = DB::table('stackable_plans')->first();
    $this->post("/plans/{$s->id}/decision", ['decision' => 'not_yet', 'conditions' => 'More UTeM credit']);
    expect(DB::table('stackable_plans')->find($s->id)->status)->toBe('not_yet');
    $this->post("/plans/{$s->id}", ['items' => array_slice($items, 0, 2), 'residency_semesters' => 1, 'delivery_method' => 'conventional'])->assertSessionHasNoErrors();
    expect(DB::table('stackable_plans')->find($s->id)->status)->toBe('needs_review')->and((float) DB::table('stackable_plans')->find($s->id)->utem_percent)->toBe(50.0);
});

it('records an award only after admission, a suitable plan and every requirement, and never gives one (BR-37)', function () {
    [, , $items] = publishedPathway();
    plan(array_slice($items, 0, 2));
    $s = DB::table('stackable_plans')->first();
    $this->post("/plans/{$s->id}/award", ['evidence_ref' => 'Graduation list', 'awarded_on' => today()->toDateString()])->assertSessionHasErrors('evidence_ref');
    DB::table('admissions')->insert(['user_id' => $this->amir->id, 'programme_id' => bits()->id, 'intake' => '2027/1', 'recorded_by' => $this->hakim->id, 'recorded_at' => now()]);
    $this->post("/plans/{$s->id}/decision", ['decision' => 'suitable']);
    $this->post("/plans/{$s->id}/award", ['evidence_ref' => 'Graduation list', 'awarded_on' => today()->toDateString()])->assertSessionHasNoErrors();
    expect(DB::table('qualification_awards')->count())->toBe(1);
    $this->get("/plans/{$s->id}")->assertSee('does not give it', false)->assertSee('Award recorded');
});

it('uses a published mapping only, and keeps a plan inside the faculty (FR-spplan, FR-12)', function () {
    [, $mc] = approvedV1();
    newPathway();
    $pw = (int) DB::table('pathways')->max('id');
    mapItem($pw, $mc, 'BITS 1123');
    $item = DB::table('pathway_items')->value('id');
    $this->actingAs($this->hakim)->post('/plans', ['user_id' => $this->amir->id, 'pathway_id' => $pw, 'items' => [$item], 'residency_semesters' => 1, 'delivery_method' => 'conventional'])->assertNotFound();
    [, , $items] = publishedPathway();
    plan($items);
    $fke = User::create(['name' => 'Dean FKE', 'email' => 'fke@utem.example.test']);
    $fke->roleAssignments()->create(['role' => 'deputy_dean', 'scope_type' => 'ptj', 'ptj_id' => DB::table('ptjs')->where('code', 'FTKE')->value('id')]);
    $this->actingAs($fke)->get('/plans/'.DB::table('stackable_plans')->value('id'))->assertNotFound();
    $this->actingAs($this->farid)->get('/pathways')->assertForbidden();
});

it('tells the participant the truth about their pathway (FR-spinfo)', function () {
    [, , $items] = publishedPathway();
    plan(array_slice($items, 0, 2));
    $this->actingAs($this->amir)->get('/my-plan')->assertOk()->assertSee('advice')->assertSee('leads to a degree automatically')->assertSee('credit transfer evaluation')->assertSee('graduation conditions');
    $bee = learner('Bee Other', 'B032419998');
    $this->actingAs($bee)->get('/my-plan')->assertDontSee('SP-');
});

// ---- review flags (FR-spcurrchange, EV-55)

it('flags a mapping for review when the micro-credential changes or the target course changes, and returns Suitable plans to Needs review', function () {
    [$pw, $mc, $items] = publishedPathway();
    plan(array_slice($items, 0, 2));
    $s = DB::table('stackable_plans')->first();
    DB::table('admissions')->insert(['user_id' => $this->amir->id, 'programme_id' => bits()->id, 'intake' => '2027/1', 'recorded_by' => $this->hakim->id, 'recorded_at' => now()]);
    $this->post("/plans/{$s->id}/decision", ['decision' => 'suitable']);
    expect(DB::table('stackable_plans')->find($s->id)->status)->toBe('suitable');
    Plans::flagMicroCredential($mc, 'A material change was approved');
    expect(DB::table('pathways')->find($pw)->status)->toBe('review')->and(DB::table('pathways')->find($pw)->review_reason)->toContain('material change')->and(DB::table('stackable_plans')->find($s->id)->status)->toBe('needs_review');
    $this->get('/public/pathways')->assertDontSee('Bachelor of Computer Science'); // no longer shown as approved
    $this->get("/pathways/$pw")->assertSee('Flagged for review');
    DB::table('pathways')->where('id', $pw)->update(['status' => 'published', 'review_reason' => null]);
    Plans::flagCourse('BITS 1124', 'The target course BITS 1124 changed');
    expect(DB::table('pathways')->find($pw)->status)->toBe('review');
});
