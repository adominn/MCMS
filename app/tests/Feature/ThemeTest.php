<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

it('switches the theme for guest in session', function () {
    $this->post('/theme', ['theme' => 'dark'])->assertRedirect();
    expect(session('ui_theme'))->toBe('dark');

    $this->post('/theme', ['theme' => 'light'])->assertRedirect();
    expect(session('ui_theme'))->toBe('light');

    $this->post('/theme', ['theme' => 'invalid-theme'])->assertRedirect();
    expect(session('ui_theme'))->toBe('auto');
});

it('switches the theme for authenticated user and updates database record', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();

    $this->actingAs($amir)->post('/theme', ['theme' => 'dark'])->assertRedirect();
    expect(session('ui_theme'))->toBe('dark');
    expect($amir->fresh()->theme_preference)->toBe('dark');

    $this->actingAs($amir)->post('/theme', ['theme' => 'auto'])->assertRedirect();
    expect(session('ui_theme'))->toBe('auto');
    expect($amir->fresh()->theme_preference)->toBe('auto');
});

it('returns json response when theme is switched via ajax/json fetch', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();

    $response = $this->actingAs($amir)->postJson('/theme', ['theme' => 'dark']);
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'theme' => 'dark',
        ]);
    expect($amir->fresh()->theme_preference)->toBe('dark');
});

it('updates theme preference via profile preferences form', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();

    $this->actingAs($amir)->post('/profile/preferences', [
        'locale' => 'en',
        'theme' => 'dark',
        'phone' => '012-9998888',
        'name_on_certificate' => 'Amir Hakimi bin Kassim',
    ])->assertRedirect('/profile');

    expect($amir->fresh()->theme_preference)->toBe('dark');
    expect(session('ui_theme'))->toBe('dark');
});

it('renders theme toggle in the header and profile selector', function () {
    $amir = User::where('name', 'Amir Hakimi')->firstOrFail();

    $this->actingAs($amir)->get('/tasks')
        ->assertOk()
        ->assertSee('id="theme-toggle"', false)
        ->assertSee('theme-icon-light', false)
        ->assertSee('theme-icon-dark', false);

    $this->actingAs($amir)->get('/profile')
        ->assertOk()
        ->assertSee('Interface theme')
        ->assertSee('System (Auto)');
});

it('always renders printable documents in light mode even if user prefers dark mode', function () {
    $lecturer = User::whereHas('roleAssignments', fn ($q) => $q->where('role', 'lecturer'))->firstOrFail();
    $lecturer->update(['theme_preference' => 'dark']);
    session(['ui_theme' => 'dark']);

    // Check a printable report view
    $response = $this->actingAs($lecturer)->get('/records/reports/offerings/print');
    $response->assertOk()
        ->assertSee('data-theme="light"', false)
        ->assertSee('color-scheme', false);
});

it('renders signin page locked to light mode with high-contrast credentials guide', function () {
    $res = $this->get('/signin');
    $res->assertOk()
        ->assertSee('data-theme="light"', false)
        ->assertSee('UserId@utem.edu.my', false)
        ->assertSee('color:#0f62fe !important', false)
        ->assertSee('background:#edf5ff !important', false);
});

it('renders public pathways notice with accessible semantic tokens without black text on dark ground', function () {
    $res = $this->get('/public/pathways');
    $res->assertOk()
        ->assertSee('id="pathway-notes"', false)
        ->assertSee('note info', false)
        ->assertDontSee('color:#161616', false);
});

it('renders credential verification code with semantic badge class and white-padded QR code', function () {
    $html = view('credentials.show', [
        'c' => (object) [
            'id' => 1,
            'number' => 'UTEM-MC-2026-0001',
            'public_code' => 'CND01-7K9MJ',
            'status' => 'valid',
            'created_at' => now(),
        ],
        'live' => true,
        'url' => 'http://localhost:8110/public/verify/CND01-7K9MJ',
        'qr' => '<svg>QR</svg>',
        'modules' => collect(),
        'mc' => null,
        'version' => null,
        'offering' => null,
        'content' => [
            'name' => ['en' => 'Test MC'],
            'issued' => '2026-10-06',
            'statement' => ['en' => 'Statement in English', 'ms' => 'Kenyataan dalam BM'],
            'slt_hours' => 40,
            'signatory' => 'Vice Chancellor',
            'holder' => 'Amir Hakimi',
            'issuer' => 'Universiti Teknikal Malaysia Melaka',
        ],
        'history' => collect(),
        'revisions' => collect(),
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ])->render();

    expect($html)
        ->toContain('code class="vc"')
        ->not->toContain('color:var(--primary, #0f62fe)')
        ->toContain('id="qr-container"');
});

it('renders printable documents with solid black hairline table and panel borders per DESIGN.md', function () {
    $lecturer = User::whereHas('roleAssignments', fn ($q) => $q->where('role', 'lecturer'))->firstOrFail();

    $response = $this->actingAs($lecturer)->get('/records/reports/offerings/print');
    $response->assertOk()
        ->assertSee('--border: #161616 !important', false)
        ->assertSee('--hairline-strong: #161616 !important', false)
        ->assertSee('border-bottom: 1px solid #161616 !important', false)
        ->assertSee('border: 1px solid #161616 !important', false)
        ->assertDontSee('border-bottom: 1px solid #e0e0e0 !important', false);
});

it('renders breadcrumbs with hierarchical back button on subpages and excludes on main pages', function () {
    // 1. Two-level crumb with parent URL renders back button linking to parent
    $html1 = \Illuminate\Support\Facades\Blade::render(
        '<x-breadcrumb :crumbs="[\'Registrations and payments\' => \'/enrolments\', \'ENR-2026-0047\' => null]" />'
    );
    expect($html1)
        ->toContain('class="btn-breadcrumb-back"')
        ->toContain('href="/enrolments"')
        ->toContain('&larr;')
        ->toContain('Back');

    // 2. Multi-level crumb renders back button linking to immediate parent (hierarchy)
    $html2 = \Illuminate\Support\Facades\Blade::render(
        '<x-breadcrumb :crumbs="[\'Proposals\' => \'/proposals\', \'Version 1\' => \'/proposals/1\', \'Decision pack\' => null]" />'
    );
    expect($html2)
        ->toContain('class="btn-breadcrumb-back"')
        ->toContain('href="/proposals/1"');

    // 3. Main page / top-level crumbs with null URLs do NOT render back button
    $html3 = \Illuminate\Support\Facades\Blade::render(
        '<x-breadcrumb :crumbs="[\'Administration\' => null, \'Reference data\' => null]" />'
    );
    expect($html3)
        ->not->toContain('btn-breadcrumb-back');

    // 4. Single crumb does NOT render back button
    $html4 = \Illuminate\Support\Facades\Blade::render(
        '<x-breadcrumb :crumbs="[\'Traceability\' => null]" />'
    );
    expect($html4)
        ->not->toContain('btn-breadcrumb-back');

    // 5. Explicit back=false suppresses back button
    $html5 = \Illuminate\Support\Facades\Blade::render(
        '<x-breadcrumb :crumbs="[\'Parent\' => \'/parent\', \'Child\' => null]" :back="false" />'
    );
    expect($html5)
        ->not->toContain('btn-breadcrumb-back');

    // 6. Explicit custom back URL is honored
    $html6 = \Illuminate\Support\Facades\Blade::render(
        '<x-breadcrumb :crumbs="[\'Parent\' => \'/parent\', \'Child\' => null]" back="/custom-back" />'
    );
    expect($html6)
        ->toContain('class="btn-breadcrumb-back"')
        ->toContain('href="/custom-back"');
});

it('renders hierarchical back button beside breadcrumbs on live application pages', function () {
    $student = User::where('name', 'Amir Hakimi')->firstOrFail();

    // Student transfer application page (subpage of credit transfer)
    $response = $this->actingAs($student)->get('/transfer/new');
    $response->assertOk()
        ->assertSee('class="btn-breadcrumb-back"', false)
        ->assertSee('href="http://localhost/transfer"', false)
        ->assertSee('class="breadcrumb-bar"', false);

    // Main administration reference page (main page - must not have back button)
    $admin = User::whereHas('roleAssignments', fn ($q) => $q->where('role', 'codl_admin'))->firstOrFail();
    $adminResponse = $this->actingAs($admin)->get('/admin/reference/ptj');
    $adminResponse->assertOk()
        ->assertDontSee('class="btn-breadcrumb-back"', false);
});

it('renders student dashboard with clickable in-progress and waiting-for-verification links without route exceptions', function () {
    $student = User::where('name', 'Amir Hakimi')->firstOrFail();

    // 1. Home route is defined
    expect(route('home'))->toBe('http://localhost');

    // 2. Student dashboard renders cleanly without route exceptions
    $homeRes = $this->actingAs($student)->get('/tasks');
    $homeRes->assertOk()->assertSee('My learning');

    // 3. When an enrolment exists, test enrolment and record views
    $enrol = DB::table('enrolments')->where('user_id', $student->id)->first();
    if (! $enrol) {
        $offering = DB::table('offerings')->first();
        if ($offering) {
            $eid = DB::table('enrolments')->insertGetId([
                'user_id' => $student->id,
                'offering_id' => $offering->id,
                'mc_version_id' => $offering->mc_version_id,
                'status' => 'enrolled',
                'reference' => 'ENR-TEST-001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $enrol = DB::table('enrolments')->find($eid);
        }
    }

    if ($enrol) {
        $enrolRes = $this->actingAs($student)->get("/enrolments/{$enrol->id}");
        $enrolRes->assertOk()
            ->assertSee('class="breadcrumb-bar"', false);

        $recordRes = $this->actingAs($student)->get("/records/{$enrol->id}");
        $recordRes->assertOk()
            ->assertSee('class="breadcrumb-bar"', false);
    }
});

it('renders notification popover with mark as read actions and without blue text on dark backgrounds', function () {
    $user = User::where('name', 'Amir Hakimi')->firstOrFail();
    $nid = DB::table('notifications')->insertGetId([
        'user_id' => $user->id,
        'channel' => 'in_system',
        'locale' => 'en',
        'status' => 'sent',
        'template_key' => 'reviewer.assigned',
        'subject' => 'Peer Review Invitation',
        'body' => 'You are invited to review.',
        'created_at' => now(),
    ]);

    $res = $this->actingAs($user)->get('/tasks');
    $res->assertOk()
        ->assertSee('Mark all as read')
        ->assertSee('Mark read')
        ->assertSee(route('notifications.readall'))
        ->assertSee(route('notifications.read', $nid))
        ->assertDontSee('style="color:var(--primary)"', false);

    // Test mark as read action
    $this->post("/notifications/$nid/read")->assertRedirect();
    expect(DB::table('notifications')->where('id', $nid)->value('read_at'))->not->toBeNull();

    // Test mark all as read action
    $nid2 = DB::table('notifications')->insertGetId([
        'user_id' => $user->id,
        'channel' => 'in_system',
        'locale' => 'en',
        'status' => 'sent',
        'template_key' => 'reviewer.assigned',
        'subject' => 'Another Invitation',
        'body' => 'Another review.',
        'created_at' => now(),
    ]);
    $this->post('/notifications/read-all')->assertRedirect();
    expect(DB::table('notifications')->where('id', $nid2)->value('read_at'))->not->toBeNull();
});
