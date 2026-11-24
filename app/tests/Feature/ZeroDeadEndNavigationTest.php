<?php

declare(strict_types=1);

use App\Modules\Identity\Role;
use App\Modules\Notifications\Notifier;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('ensures every sidebar link across all active roles opens cleanly without a 403 dead end', function () {
    $actors = [
        'student' => userAmir(),
        'lecturer' => userAina(),
        'deputy_dean' => userHakim(),
        'codl_admin' => userFarid(),
        'academic_authority' => userZulkifli(),
        'qa_officer' => userNorazlina(),
        'records_custodian' => userHasnah(),
        'abc_depositor' => userNurul(),
    ];

    foreach ($actors as $roleName => $actor) {
        $home = $this->actingAs($actor)->get($actor->homeUrl())->assertOk();
        $html = $home->getContent();

        // Extract sidebar nav block
        expect(preg_match('#<nav class="side"[^>]*>(.*?)</nav>#s', $html, $navMatch))->toBe(1, "Sidebar missing for $roleName");
        preg_match_all('#<a\s+href="([^"]+)"#', $navMatch[1], $links);
        expect($links[1])->not->toBeEmpty("No sidebar links found for $roleName");

        // Role-specific sidebar visibility checks
        if ($roleName === 'deputy_dean') {
            expect($navMatch[1])
                ->not->toContain('/credentials')
                ->not->toContain('/transfers');
        }
        if ($roleName === 'records_custodian') {
            expect($navMatch[1])->not->toContain('"/results"');
        }

        foreach (array_unique($links[1]) as $href) {
            $path = parse_url($href, PHP_URL_PATH) ?: $href;
            $res = $this->actingAs($actor)->get($path);
            expect($res->getStatusCode())
                ->toBeIn([200, 302], "Role [$roleName] hit status {$res->getStatusCode()} clicking sidebar link [$path]");
        }
    }
});

it('redirects DD on direct GET /micro-credentials/{mc} to Proposals and approval, and owns-lecturer on GET /results/{o} to results entry', function () {
    [$v] = approvedV1();
    $mcId = (int) DB::table('mc_versions')->where('id', $v)->value('micro_credential_id');
    $o = newCohort($v);

    // DD opening /micro-credentials/{mc} redirects seamlessly to /approvals/{v}
    $this->actingAs(userHakim())->get("/micro-credentials/$mcId")
        ->assertRedirect(route('approvals.show', $v))
        ->assertSessionHas('status');

    // DD opening /micro-credentials index or /proposals/new still gets unified 403
    $this->actingAs(userHakim())->get('/micro-credentials')
        ->assertForbidden()
        ->assertSee('Access handoff')
        ->assertSee('Handled by:')
        ->assertSee('Course Coordinator / Lecturer')
        ->assertDontSee('contact CODL');

    // Owning lecturer opening /results/{o} redirects seamlessly to /offerings/{o}/results
    $this->actingAs(userAina())->get("/results/$o")
        ->assertRedirect(route('results.cohort', $o))
        ->assertSessionHas('status');
});

it('hides forbidden in-page actions for DD, P6 Records Custodian, and P7 ABC Depositor and enforces unified 403 handoffs', function () {
    [$v] = approvedV1();
    $o = newCohort($v);

    // 1. DD viewing /credentials does NOT see retry form; direct POST /credentials/retry/6 returns unified 403
    $this->actingAs(userHakim())->get('/credentials')
        ->assertOk()
        ->assertDontSee('/credentials/retry/', false)
        ->assertDontSee('Check again');

    foreach (['en', 'ms'] as $locale) {
        $res = $this->actingAs(userHakim())
            ->withSession(['ui_lang' => $locale])
            ->post('/credentials/retry/6')
            ->assertForbidden();
        expect($res->getContent())
            ->not->toContain('contact CODL')
            ->not->toContain('Appendix ')
            ->not->toContain('Lampiran ')
            ->not->toContain('(D-52)');
    }

    // 2. Records Custodian viewing /completion/{o} sees read-only checks with zero Confirm form or /results links
    $this->actingAs(userHasnah())->get("/completion/$o")
        ->assertOk()
        ->assertDontSee("/completion/$o\"", false)
        ->assertDontSee('name="enrolments[]"', false)
        ->assertDontSee(route('results.verify', $o));

    // 3. Pure ABC Depositor viewing /records/discrepancies does NOT see Retention tab; direct GET /records/retention gives unified 403
    $pureAbc = \App\Models\User::create([
        'name' => 'Test ABC Depositor',
        'email' => 'abc_only_'.uniqid().'@utem.example.test',
        'upn' => 'abc_only_'.uniqid().'@utem.example.test',
        'status' => 'active',
        'locale' => 'en',
    ]);
    $pureAbc->roleAssignments()->create(['role' => 'abc_depositor', 'scope_type' => 'university', 'reason' => 'Test pure P7']);

    $this->actingAs($pureAbc)->withSession(['ui_lang' => 'en'])->get('/records/discrepancies')
        ->assertOk()
        ->assertDontSee(route('retention'));

    $this->actingAs($pureAbc)->withSession(['ui_lang' => 'en'])->get('/records/retention')
        ->assertForbidden()
        ->assertSee('Access handoff')
        ->assertSee('Handled by:')
        ->assertSee('Academic Authority (Senate)')
        ->assertDontSee('contact CODL');
});

it('ensures Notifier::linkFor never routes any role to a 403 dead end', function () {
    $keys = [
        'abc.deposited', 'acr.confirmed', 'credit.expiring', 'completion.confirmed',
        'results.verified', 'results.staged', 'credential.issued', 'enrolment.confirmed',
        'proposal.submitted', 'decision.made', 'qv.lapsed', 'transfer.decided', 'suspension.notice',
    ];
    $actors = [
        userAmir(), userAina(), userHakim(), userFarid(),
        userZulkifli(), userNorazlina(), userHasnah(), userNurul(),
    ];

    foreach ($actors as $actor) {
        foreach ($keys as $key) {
            $url = Notifier::linkFor($key, $actor);
            $res = $this->actingAs($actor)->get($url);
            expect($res->getStatusCode())
                ->toBeIn([200, 302], "Notification [$key] routed {$actor->name} to [$url] which returned {$res->getStatusCode()}");
        }
    }
});

it('uses clean canonical role names without internal P1-P8 codes across EN and BM', function () {
    foreach (Role::cases() as $role) {
        foreach (['en', 'ms'] as $locale) {
            $label = $role->label($locale);
            expect($label)->not->toMatch('/\(P[1-8]\)/');
        }
    }
});
