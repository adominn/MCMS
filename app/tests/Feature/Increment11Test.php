<?php

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Core\Templates;
use App\Modules\Notifications\Adapters\EmailAdapter;
use App\Modules\Notifications\IntegrationHub;
use App\Modules\Notifications\Notifier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

if (! function_exists('who')) {
function who(string $name): User
{
    return User::where('name', $name)->firstOrFail();
}
}


if (! function_exists('failingEmail')) {
function failingEmail(): void
{
    app()->bind(EmailAdapter::class, fn () => new class implements EmailAdapter
    {
        public function send(string $to, string $subject, string $body): string
        {
            throw new RuntimeException('smtp down');
        }
    });
}
}


// ---- sign-in, MFA, idle timeout (FR-05, FR-06, SR-02)

it('asks the deputy dean and the administrator for a second step, but not a student (SR-02)', function () {
    $this->post('/signin/stub', ['user' => who('Hakim Rahman')->id])->assertRedirect('/signin/mfa');
    $this->assertGuest();
    $this->post('/signin/mfa', ['code' => 'abcdef'])->assertSessionHasErrors('code');
    $this->assertGuest();
    $this->post('/signin/mfa', ['code' => '123456'])->assertRedirect('/tasks');
    $this->assertAuthenticatedAs(who('Hakim Rahman'));

    $this->post('/signout');
    $this->post('/signin/stub', ['user' => who('Amir Hakimi')->id])->assertRedirect('/tasks');
});

it('has no second-step page without a pending sign-in', function () {
    $this->get('/signin/mfa')->assertNotFound();
});

it('ends an idle session after the idle timeout setting and records it (FR-06)', function () {
    $amir = who('Amir Hakimi');
    $this->actingAs($amir)->withSession(['last_activity' => now()->subMinutes(29)->timestamp])->get('/tasks')->assertOk();
    $this->actingAs($amir)->withSession(['last_activity' => now()->subMinutes(31)->timestamp])->get('/tasks')->assertRedirect('/signin');
    expect(DB::table('audit_events')->where('action', 'auth.session_expired')->where('actor_id', $amir->id)->exists())->toBeTrue();
});

it('follows a changed idle timeout (FR-06, FR-20)', function () {
    app(Settings::class)->set('auth.idle_timeout_minutes', '10', 'Shorter for the test', who('Farid Hashim'));
    $this->actingAs(who('Amir Hakimi'))->withSession(['last_activity' => now()->subMinutes(11)->timestamp])->get('/tasks')->assertRedirect('/signin');
});

// ---- users, roles, scope (FR-13, FR-23)

it('assigns a scoped role with reason and date, audits it and tells the person (FR-13)', function () {
    $aina = who('Dr. Aina Zulkifli');
    $this->actingAs(who('Farid Hashim'))->post("/admin/users/{$aina->id}/roles", [
        'role' => 'reviewer', 'scope_type' => 'mc', 'scope_ref' => 'LE', 'valid_from' => today()->toDateString(), 'reason' => 'Reviews Leadership Essentials',
    ])->assertRedirect("/admin/users/{$aina->id}");

    expect($aina->hasAnyRole('reviewer'))->toBeTrue();
    $row = DB::table('role_assignments')->where('user_id', $aina->id)->where('role', 'reviewer')->first();
    expect($row->scope_type)->toBe('mc')->and($row->scope_ref)->toBe('LE');
    $e = DB::table('audit_events')->where('action', 'role.assigned')->first();
    expect($e->reason)->toBe('Reviews Leadership Essentials');
    expect(DB::table('notifications')->where('user_id', $aina->id)->where('template_key', 'role.assigned')->where('channel', 'in_system')->value('body'))->toContain('micro-credential LE');
});

it('rejects a role without its scope value or reason, and from anyone but the administrator', function () {
    $aina = who('Dr. Aina Zulkifli');
    $this->actingAs(who('Farid Hashim'))->post("/admin/users/{$aina->id}/roles", ['role' => 'reviewer', 'scope_type' => 'ptj', 'valid_from' => today()->toDateString(), 'reason' => 'x'])
        ->assertSessionHasErrors(['ptj_id', 'reason']);
    $this->actingAs(who('Hakim Rahman'))->post("/admin/users/{$aina->id}/roles", ['role' => 'reviewer', 'scope_type' => 'university', 'valid_from' => today()->toDateString(), 'reason' => 'Not allowed'])->assertForbidden();
});

it('starts a role on its effective date and keeps the row when it is revoked', function () {
    $amir = who('Amir Hakimi');
    $admin = who('Farid Hashim');
    $this->actingAs($admin)->post("/admin/users/{$amir->id}/roles", ['role' => 'lecturer', 'scope_type' => 'university', 'valid_from' => today()->addDay()->toDateString(), 'reason' => 'Starts tomorrow']);
    expect($amir->hasAnyRole('lecturer'))->toBeFalse();

    $a = DB::table('role_assignments')->where('user_id', $amir->id)->where('role', 'lecturer')->first();
    $this->travelTo(now()->addDays(2));
    $this->flushSession(); // two days idle would otherwise end the session
    expect($amir->hasAnyRole('lecturer'))->toBeTrue();
    $this->actingAs($admin)->post("/admin/users/{$amir->id}/roles/{$a->id}/revoke", ['reason' => 'No longer needed'])->assertRedirect();
    expect($amir->hasAnyRole('lecturer'))->toBeFalse()
        ->and(DB::table('role_assignments')->where('id', $a->id)->exists())->toBeTrue()
        ->and(DB::table('audit_events')->where('action', 'role.revoked')->exists())->toBeTrue();
});

it('searches users by name or email (FR-23)', function () {
    $this->actingAs(who('Farid Hashim'))->get('/admin/users?q=aina')->assertOk()->assertSee('Dr. Aina Zulkifli')->assertDontSee('Amir Hakimi');
});

it('deactivates and reactivates an account without touching its records (FR-23)', function () {
    $amir = who('Amir Hakimi');
    $admin = who('Farid Hashim');
    $this->actingAs($admin)->post("/admin/users/{$amir->id}/status/inactive", ['reason' => 'Left the programme']);
    expect($amir->fresh()->status)->toBe('inactive')->and(DB::table('role_assignments')->where('user_id', $amir->id)->count())->toBe(1);

    $this->post('/signout');
    $this->post('/signin/stub', ['user' => $amir->id])->assertRedirect('/signin');
    $this->assertGuest();
    expect(DB::table('notifications')->where('user_id', $amir->id)->where('template_key', 'account.deactivated')->count())->toBe(2); // in-system and email

    $this->actingAs($admin)->post("/admin/users/{$amir->id}/status/active", ['reason' => 'Returned']);
    $this->post('/signout');
    $this->post('/signin/stub', ['user' => $amir->id])->assertRedirect('/tasks');
});

// ---- reference data (FR-24)

it('adds and updates reference data by form', function () {
    $this->actingAs(who('Farid Hashim'));
    $row = ['code' => 'BITM', 'name' => 'Bachelor of Multimedia', 'mqf_level' => '6', 'mode' => 'conventional', 'accreditation_status' => 'accredited', 'total_credits' => '120'];
    $this->post('/admin/reference/programme', $row)->assertSessionHasNoErrors();
    $this->post('/admin/reference/programme', ['name' => 'Bachelor of Multimedia Computing'] + $row);
    expect(DB::table('programmes')->where('code', 'BITM')->count())->toBe(1)->and(DB::table('programmes')->where('code', 'BITM')->value('name'))->toBe('Bachelor of Multimedia Computing');
    $this->post('/admin/reference/programme', ['mode' => 'online'] + $row)->assertSessionHasErrors('mode');
});

it('imports reference data from CSV, listing bad rows and rejecting a wrong header (FR-24)', function () {
    $this->actingAs(who('Farid Hashim'));
    $countBefore = DB::table('ptjs')->count();
    $good = UploadedFile::fake()->createWithContent('ptj.csv', "code,name\nFTKEE,Faculty of Electronics\n,Missing code\n");
    $this->post('/admin/reference/ptj/import', ['file' => $good])->assertSessionHas('status', '1 rows imported.')->assertSessionHasErrors('rows');
    expect(DB::table('ptjs')->where('code', 'FTKEE')->exists())->toBeTrue();

    $bad = UploadedFile::fake()->createWithContent('ptj.csv', "id,title\n1,Nothing\n");
    $this->post('/admin/reference/ptj/import', ['file' => $bad])->assertSessionHasErrors('file');
    expect(DB::table('ptjs')->count())->toBe($countBefore + 1);
});

// ---- templates (FR-22)

it('versions a template per language and renders the newest (FR-22, FR-266)', function () {
    $t = app(Templates::class);
    $this->actingAs(who('Farid Hashim'))->post('/admin/templates/decision.made', ['locale' => 'en', 'subject' => 'Decision on {item}', 'body' => 'Dear {name}: {decision}.', 'reason' => 'Shorter wording'])->assertRedirect();
    expect($t->render('decision.made', 'en', ['name' => 'Amir', 'item' => 'X', 'decision' => 'Approved']))->toBe(['Decision on X', 'Dear Amir: Approved.'])
        ->and($t->render('decision.made', 'ms', ['name' => 'Amir', 'item' => 'X', 'decision' => 'Lulus'])[0])->toBe('Keputusan telah direkodkan bagi X');
    expect(DB::table('template_versions')->where('locale', 'en')->whereIn('template_id', DB::table('templates')->where('key', 'decision.made')->pluck('id'))->count())->toBe(2);
});

it('keeps the separate-stages statement in both languages (D-15)', function () {
    [, $en] = app(Templates::class)->render('statement.separate_stages', 'en', []);
    [, $ms] = app(Templates::class)->render('statement.separate_stages', 'ms', []);
    expect($en)->toContain('separate decisions')->and($ms)->toContain('berasingan');
});

// ---- notifications (FR-265 to FR-268)

it('sends in the recipient language, in the system and by email, and logs each (FR-266, FR-268)', function () {
    $siti = who('Ir. Siti Aminah'); // chooses Bahasa Melayu
    app(Notifier::class)->send($siti, 'decision.made', ['name' => 'Siti', 'item' => 'Cadangan A', 'decision' => 'Lulus']);
    $rows = DB::table('notifications')->where('user_id', $siti->id)->orderBy('id')->get();
    expect($rows)->toHaveCount(2)->and($rows[0]->channel)->toBe('in_system')->and($rows[0]->body)->toContain('Salam Siti')->and($rows[1]->channel)->toBe('email')->and($rows[1]->status)->toBe('sent');
});

it('lets a user switch off optional notices but never mandatory ones (FR-267)', function () {
    $amir = who('Amir Hakimi');
    $this->actingAs($amir)->post('/notifications/preferences', ['on' => []])->assertRedirect();
    app(Notifier::class)->send($amir, 'role.assigned', ['name' => 'Amir', 'role' => 'Student', 'scope' => 'x', 'from' => 'today', 'reason' => 'r']);
    app(Notifier::class)->send($amir, 'decision.made', ['name' => 'Amir', 'item' => 'X', 'decision' => 'Approved']);
    expect(DB::table('notifications')->where('user_id', $amir->id)->pluck('template_key')->unique()->values()->all())->toBe(['decision.made']);
});

it('does not notify the same event twice (IR-17)', function () {
    $amir = who('Amir Hakimi');
    foreach (range(1, 3) as $_) {
        app(Notifier::class)->send($amir, 'decision.made', ['name' => 'Amir', 'item' => 'X', 'decision' => 'Approved'], dedupe: 'case-1');
    }
    expect(DB::table('notifications')->where('user_id', $amir->id)->count())->toBe(2);
});

it('shows a user their notices and marks one as read', function () {
    $amir = who('Amir Hakimi');
    app(Notifier::class)->send($amir, 'decision.made', ['name' => 'Amir', 'item' => 'Case 7', 'decision' => 'Approved']);
    $this->actingAs($amir)->get('/notifications')->assertSee('A decision was recorded on Case 7')->assertSee('Mark as read')->assertSee('Mark all read');
    $id = DB::table('notifications')->where('user_id', $amir->id)->where('channel', 'in_system')->value('id');
    $this->actingAs($amir)->post("/notifications/$id/read");
    $this->actingAs($amir)->get('/notifications')->assertDontSee('Mark as read');
});

// ---- integration hub (FR-25, FR-270, FR-271, IR-14 to IR-17)

it('retries a failed call with growing delay without blocking the business step (FR-271)', function () {
    failingEmail();
    $amir = who('Amir Hakimi');
    app(Notifier::class)->send($amir, 'decision.made', ['name' => 'Amir', 'item' => 'X', 'decision' => 'Approved']); // must not throw
    $call = fn () => DB::table('integration_calls')->first();
    expect($call()->status)->toBe('retrying')->and($call()->attempts)->toBe(1);
    expect(DB::table('notifications')->where('channel', 'in_system')->value('status'))->toBe('sent');

    $waited = [];
    foreach ([1, 5, 30] as $i => $minutes) {
        $due = \Carbon\Carbon::parse($call()->next_attempt_at);
        $waited[] = (int) round(\Carbon\Carbon::parse($call()->last_attempt_at)->diffInMinutes($due));
        $this->travelTo($due->addSecond());
        app(IntegrationHub::class)->retryDue();
    }
    expect($waited)->toBe([1, 5, 30])->and($call()->status)->toBe('failed')->and($call()->attempts)->toBe(4)
        ->and(DB::table('notifications')->where('channel', 'email')->value('status'))->toBe('failed')
        ->and(DB::table('audit_events')->where('action', 'integration.call_failed')->exists())->toBeTrue();
});

it('lists failed calls for the administrator, who can retry them (FR-271)', function () {
    failingEmail();
    app(Notifier::class)->send(who('Amir Hakimi'), 'decision.made', ['name' => 'Amir', 'item' => 'X', 'decision' => 'Approved']);
    $id = DB::table('integration_calls')->value('id');
    DB::table('integration_calls')->where('id', $id)->update(['status' => 'failed', 'attempts' => 4]);

    $admin = who('Farid Hashim');
    $this->actingAs($admin)->get('/admin/adapters')->assertSee('smtp down')->assertSee('Retry now')->assertSee('Stub');
    app()->bind(EmailAdapter::class, fn () => new \App\Modules\Notifications\Adapters\StubEmailAdapter); // the provider is back
    $this->actingAs($admin)->post("/admin/adapters/calls/$id/retry")->assertSessionHas('status', 'The call went through.');
    expect(DB::table('integration_calls')->where('id', $id)->value('status'))->toBe('ok')->and(DB::table('notifications')->where('channel', 'email')->value('status'))->toBe('sent');
});

it('never sends the same outside call twice (IR-17)', function () {
    $hub = app(IntegrationHub::class);
    $hub->send('email', 'send', 'once', ['to' => 'a@b.test', 'subject' => 's', 'body' => 'b']);
    $hub->send('email', 'send', 'once', ['to' => 'a@b.test', 'subject' => 's', 'body' => 'b']);
    expect(DB::table('integration_calls')->where('external_ref', 'once')->count())->toBe(1);
});

it('shows each adapter with its mode, last run and the notification log (FR-25, FR-268)', function () {
    app(Notifier::class)->send(who('Amir Hakimi'), 'decision.made', ['name' => 'Amir', 'item' => 'X', 'decision' => 'Approved']);
    $this->actingAs(who('Farid Hashim'))->get('/admin/adapters')->assertSee('Learning platform (UCreds)')->assertSee('Manual')->assertSee('Never');
    $this->actingAs(who('Farid Hashim'))->get('/admin/notification-log')->assertSee('decision.made')->assertSee('Amir Hakimi');
    $this->actingAs(who('Amir Hakimi'))->get('/admin/adapters')->assertForbidden();
});

// ---- task queue (IR-07)

it('lists only the tasks of the signed-in roles, filtered by area and search, 10 to a page', function () {
    $hakim = who('Hakim Rahman');
    $this->actingAs($hakim)->get('/tasks')->assertOk()->assertSee('All (24)')->assertSee('Results (4)')->assertDontSee('Answer review comments');
    $this->actingAs($hakim)->get('/tasks?area=results')->assertSee('Verify results')->assertDontSee('Record ABC deposit');
    $this->actingAs($hakim)->get('/tasks?q=deposit')->assertSee('All (4)')->assertSee('Record ABC deposit');
    // paging, sorting and search of the list run in the browser (UI/UX 4.5); the Playwright flow FL-00 covers them
    $this->actingAs(who('Dr. Aina Zulkifli'))->get('/tasks')->assertSee('Answer review comments')->assertDontSee('Verify results');
});

it('flags a task as overdue in words once it passes the reminder days (FR-71)', function () {
    $this->actingAs(who('Hakim Rahman'))->get('/tasks')->assertSee('Overdue');
    DB::table('tasks')->update(['created_at' => now()]);
    $this->actingAs(who('Hakim Rahman'))->get('/tasks')->assertDontSee('Overdue');
});

it('opens a task only for the role it belongs to', function () {
    $t = DB::table('tasks')->where('assignee_role', 'deputy_dean')->first();
    $this->actingAs(who('Hakim Rahman'))->get("/tasks/{$t->id}")->assertOk()->assertSee($t->title);
    $this->actingAs(who('Amir Hakimi'))->get("/tasks/{$t->id}")->assertNotFound();
});

// ---- settings with an effective date, and the integrity check (FR-21, FR-254)

it('keeps a setting at its old value until its effective date (FR-21)', function () {
    $s = app(Settings::class);
    $this->actingAs(who('Farid Hashim'))->post('/admin/settings/alerts.decision_overdue_days', ['value' => '7', 'reason' => 'Faster follow-up', 'effective_from' => today()->addDays(3)->toDateString()])->assertSessionHasNoErrors();
    expect($s->get('alerts.decision_overdue_days'))->toBe(14);
    $this->travelTo(now()->addDays(4));
    expect($s->get('alerts.decision_overdue_days'))->toBe(7);
});

it('checks the audit chain on request, records the check, and reports a break (FR-254)', function () {
    $admin = who('Farid Hashim');
    $this->actingAs($admin)->post('/admin/audit/verify')->assertSessionHas('status');
    expect(DB::table('audit_events')->where('action', 'audit.integrity_checked')->exists())->toBeTrue();

    if (DB::getDriverName() === 'pgsql') {
        DB::unprepared('ALTER TABLE audit_events DISABLE TRIGGER audit_events_no_change');
        DB::table('audit_events')->where('seq', 1)->update(['action' => 'tampered']);
        DB::unprepared('ALTER TABLE audit_events ENABLE TRIGGER audit_events_no_change');
    } else {
        DB::statement('SET @mcms_disable_triggers = 1');
        DB::table('audit_events')->where('seq', 1)->update(['action' => 'tampered']);
        DB::statement('SET @mcms_disable_triggers = 0');
    }
    $this->actingAs($admin)->post('/admin/audit/verify')->assertSessionHas('broken');
});
