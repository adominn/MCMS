<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

it('renders profile for student matching prototype structure', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();

    $response = $this->actingAs($amir)->get('/profile');

    $response->assertOk()
        ->assertSee('Amir Hakimi &middot; Participant', false)
        ->assertSee('Learner ID')
        ->assertSee('Participant')
        ->assertSee('Preferences')
        ->assertSee('Language for screens, email and certificates')
        ->assertSee('Contact phone')
        ->assertSee('Name on certificates')
        ->assertSee('Save preferences')
        ->assertSee('Decisions on my records')
        ->assertSee('Credential issued, corrected or revoked')
        ->assertSee('Consent requests')
        ->assertSee('Enrolment changes')
        ->assertSee('Sign-in')
        ->assertSee('Preview the idle warning')
        ->assertSee('Active sessions')
        ->assertSee('Edge on Windows, Melaka')
        ->assertSee('This session')
        ->assertSee('Personal data we hold')
        ->assertSee('Request a correction')
        ->assertSee('See my achievement record');
});

it('renders profile for lecturer with staff number, department, and digest option', function () {
    $aina = User::where('name', 'Dr. Aina Zulkifli')->firstOrFail();

    $response = $this->actingAs($aina)->get('/profile');

    $response->assertOk()
        ->assertSee('Dr. Aina Zulkifli &middot; Senior Lecturer', false)
        ->assertSee('Staff number')
        ->assertSee('Tasks assigned to me')
        ->assertSee('Reminders for overdue tasks')
        ->assertSee('Daily digest instead of single emails')
        ->assertSee('Preview the idle warning');
});

it('saves user preferences and updates session locale', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();

    $response = $this->actingAs($amir)->post('/profile/preferences', [
        'locale' => 'ms',
        'phone' => '012-987 6543',
        'name_on_certificate' => 'Amir Hakimi bin Ahmad',
    ]);

    $response->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'Preferences saved.')
        ->assertSessionHas('ui_lang', 'ms')
        ->assertSessionHas('lang', 'ms');

    $amir->refresh();
    expect($amir->locale)->toBe('ms')
        ->and($amir->phone)->toBe('012-987 6543')
        ->and($amir->name_on_certificate)->toBe('Amir Hakimi bin Ahmad');
});

it('saves notification preferences with prototype switches', function () {
    $aina = User::where('name', 'Dr. Aina Zulkifli')->firstOrFail();

    $response = $this->actingAs($aina)->post('/notifications/preferences', [
        'tasks' => '1',
        'reminders' => '0',
        'digest' => '1',
    ]);

    $response->assertRedirect(route('profile').'#notif')
        ->assertSessionHas('status', 'Notification preferences saved.');

    $digestEnabled = DB::table('notification_preferences')
        ->where('user_id', $aina->id)
        ->where('template_key', 'notification.daily_digest')
        ->value('enabled');

    expect((bool) $digestEnabled)->toBeTrue();
});

it('handles personal data correction requests', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();

    $response = $this->actingAs($amir)->post('/profile/data-correction', [
        'what' => 'Name spelling',
        'should' => 'Amir Hakimi bin Ahmad',
    ]);

    $response->assertRedirect(route('profile').'#data')
        ->assertSessionHas('status', 'Correction requested. Reference DC-2026-0118. You will be notified of the outcome.');
});

it('terminates an active session from the sessions list', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();

    $response = $this->actingAs($amir)->post('/profile/sessions/terminate', [
        'device' => 'Chrome on Android, Ayer Keroh',
    ]);

    $response->assertRedirect(route('profile').'#security')
        ->assertSessionHas('status', 'Signed out of Chrome on Android, Ayer Keroh.');
});
