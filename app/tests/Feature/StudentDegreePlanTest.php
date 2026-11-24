<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first(); // Lecturer
    $this->hakim = User::where('name', 'Hakim Rahman')->first();     // Deputy Dean
    $this->amir = User::where('name', 'Amir Hakimi')->first();       // Student
    $this->bee = learner('Bee Other', 'B032419999');                 // Another student
});

function createPublishedDegreePathway(): array
{
    [$v1, $mc] = approvedV1();
    $bits = bits();
    DB::table('programmes')->where('id', $bits->id)->update(['total_credits' => 120, 'mode' => 'conventional']);
    $pcId = DB::table('parent_courses')->where('code', 'BITS 1123')->value('id');

    $pwId = DB::table('pathways')->insertGetId([
        'reference' => 'PW-BITS-1',
        'programme_id' => $bits->id,
        'version_no' => 1,
        'plos' => json_encode(['PLO1', 'PLO2']),
        'bok' => json_encode(['Algorithms']),
        'status' => 'published',
        'approval_ref' => 'Senate 9/2026',
        'approved_on' => today()->toDateString(),
        'approved_by' => 1,
        'published_at' => now(),
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $itemId = DB::table('pathway_items')->insertGetId([
        'pathway_id' => $pwId,
        'micro_credential_id' => $mc,
        'parent_course_id' => $pcId,
        'credits' => 3,
        'lo_equivalence' => 'high',
        'content_equivalence' => 'high',
        'preliminary' => 'suitable',
        'plo_map' => json_encode([['plo' => 'PLO1', 'contribution' => 'main']]),
        'bok_map' => json_encode(['Algorithms']),
    ]);

    return [$pwId, $itemId];
}

it('allows an independent student to view the degree pathway selector', function () {
    [$pwId] = createPublishedDegreePathway();

    $this->actingAs($this->amir)
        ->get('/my-plan/choose')
        ->assertOk()
        ->assertSee('Choose a degree pathway')
        ->assertSee('BITS');
});

it('allows an independent student to autonomously create and save their degree plan', function () {
    [$pwId, $itemId] = createPublishedDegreePathway();

    $this->actingAs($this->amir)
        ->post('/my-plan', [
            'pathway_id' => $pwId,
        ])
        ->assertRedirect('/my-plan')
        ->assertSessionHas('status', 'Degree pathway added to your plan.');

    $plan = DB::table('stackable_plans')->where('user_id', $this->amir->id)->first();
    expect($plan)->not->toBeNull()
        ->and($plan->user_id)->toBe($this->amir->id)
        ->and($plan->pathway_id)->toBe($pwId)
        ->and((float) $plan->utem_percent)->toBeGreaterThan(90.0);

    // Verify it renders on /my-plan without any Academic Advisor UI
    $this->actingAs($this->amir)
        ->get('/my-plan')
        ->assertOk()
        ->assertSee('BITS')
        ->assertSee('Full Degree Plan')
        ->assertDontSee('Academic Advisor');
});

it('allows a student to update their self-service degree plan', function () {
    [$pwId, $itemId] = createPublishedDegreePathway();

    $this->actingAs($this->amir)->post('/my-plan', [
        'pathway_id' => $pwId,
    ]);

    $plan = DB::table('stackable_plans')->where('user_id', $this->amir->id)->first();

    $this->actingAs($this->amir)
        ->get("/my-plan/{$plan->id}/edit")
        ->assertRedirect('/my-plan/choose');

    $this->actingAs($this->amir)
        ->post("/my-plan/{$plan->id}", [
            'items' => [$itemId],
            'other_credits' => 6,
            'residency_semesters' => 2,
            'delivery_method' => 'conventional',
        ])
        ->assertRedirect('/my-plan')
        ->assertSessionHas('status', 'Your degree plan has been updated.');

    $updated = DB::table('stackable_plans')->find($plan->id);
    expect((float) $updated->other_credits)->toBe(6.0)
        ->and($updated->residency_semesters)->toBe(2);
});

it('prevents a student from editing or deleting another student plan', function () {
    [$pwId, $itemId] = createPublishedDegreePathway();

    $this->actingAs($this->amir)->post('/my-plan', [
        'pathway_id' => $pwId,
        'items' => [$itemId],
        'other_credits' => 0,
        'residency_semesters' => 1,
        'delivery_method' => 'conventional',
    ]);

    $plan = DB::table('stackable_plans')->where('user_id', $this->amir->id)->first();

    // Bee attempts to edit or update Amir's plan
    $this->actingAs($this->bee)
        ->get("/my-plan/{$plan->id}/edit")
        ->assertNotFound();

    $this->actingAs($this->bee)
        ->post("/my-plan/{$plan->id}", [
            'items' => [$itemId],
            'residency_semesters' => 1,
            'delivery_method' => 'conventional',
        ])
        ->assertNotFound();

    $this->actingAs($this->bee)
        ->delete("/my-plan/{$plan->id}")
        ->assertNotFound();
});

it('isolates the lecturer view so student plans and plan creation buttons are removed', function () {
    [$pwId, $itemId] = createPublishedDegreePathway();

    // Amir creates a plan
    $this->actingAs($this->amir)->post('/my-plan', [
        'pathway_id' => $pwId,
        'items' => [$itemId],
        'other_credits' => 0,
        'residency_semesters' => 1,
        'delivery_method' => 'conventional',
    ]);
    $plan = DB::table('stackable_plans')->where('user_id', $this->amir->id)->first();

    // Lecturer Aina visits /pathways
    $response = $this->actingAs($this->aina)->get('/pathways');
    $response->assertOk();
    $response->assertSee('Degree Articulation Roadmaps');
    // Lecturer should NOT see the Individual Student Plans tab or button or Amir's plan reference
    $response->assertDontSee('Individual Student Plans');
    $response->assertDontSee('+ New individual plan');
    $response->assertDontSee($plan->reference);

    // Lecturer is also forbidden from accessing /plans/new
    $this->actingAs($this->aina)
        ->get('/plans/new')
        ->assertForbidden();
});

it('allows deputy dean to see individual student plans in pathways', function () {
    [$pwId, $itemId] = createPublishedDegreePathway();

    $this->actingAs($this->amir)->post('/my-plan', [
        'pathway_id' => $pwId,
        'items' => [$itemId],
        'other_credits' => 0,
        'residency_semesters' => 1,
        'delivery_method' => 'conventional',
    ]);
    $plan = DB::table('stackable_plans')->where('user_id', $this->amir->id)->first();

    // Deputy Dean Hakim visits /pathways
    $response = $this->actingAs($this->hakim)->get('/pathways');
    $response->assertOk();
    $response->assertSee('Degree Articulation Roadmaps');
    $response->assertSee('Individual Student Plans');
    $response->assertSee($plan->reference);
    $response->assertSee('+ New individual plan');
});
