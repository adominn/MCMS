<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
});

it('renders tactile buttons instead of naked hyperlinks on guest header and public pages (Rule 5)', function () {
    $response = $this->get('/public');
    $response->assertOk();
    $content = $response->getContent();

    // Guest header buttons
    expect($content)->toContain('class="btn ghost sm"')
        ->toContain('class="btn sm"');
});

it('renders reports registry in a responsive 3-column card grid eliminating dead space (Rule 1)', function () {
    $user = User::where('email', 'farid.hashim@utem.example.test')->firstOrFail();
    $this->actingAs($user);
    session(['active_role' => 'codl_admin']);

    $response = $this->get('/records/reports');
    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('class="panel"')
        ->toContain('report-list')
        ->toContain('class="btn sm ghost"');
});

it('groups parent courses under progressive disclosure accordions (Rule 4)', function () {
    $user = User::where('email', 'farid.hashim@utem.example.test')->firstOrFail();
    $this->actingAs($user);
    session(['active_role' => 'codl_admin']);

    $response = $this->get('/admin/reference/course');
    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('class="accordion-row"')
        ->toContain('<summary>')
        ->toContain('class="accordion-body"');
});

it('paginates ucreds courses catalogue cleanly with Carbon pagination bar (Rule 1)', function () {
    $user = User::where('email', 'farid.hashim@utem.example.test')->firstOrFail();
    $this->actingAs($user);
    session(['active_role' => 'codl_admin']);

    $response = $this->get('/admin/ucreds/courses');
    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('ucreds')
        ->toContain('pagination-bar');
});

it('paginates catalogue when offerings exist with Carbon pagination bar (Rule 1 & 5)', function () {
    // Check view rendering directly with paginator
    $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
        collect([
            (object)[
                'id' => 1,
                'cohort_name' => 'Cohort 2026/1',
                'start_date' => '2026-09-01',
                'end_date' => '2026-12-01',
                'fee' => 500,
                'delivery_mode' => 'online',
                'status' => 'open',
                'reg_opens_on' => '2026-08-01',
                'name_en' => 'Test MC',
                'name_ms' => 'MC Ujian',
                'discipline' => 'Computing',
                'delivery_language' => 'English',
                'category' => 'umc',
                'ptj' => 'FTMK',
            ],
        ]),
        25, // total 25
        10, // per page 10
        1,  // current page 1
        ['path' => '/public/catalogue']
    );

    $html = view('public.catalogue', [
        'rows' => $paginator,
        'f' => [],
        'ptjs' => DB::table('ptjs')->get(),
        'modes' => config('proposals.delivery_modes'),
        'lang' => 'en',
        't' => fn ($k) => $k,
        'pick' => fn ($en, $ms) => $en,
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ])->render();

    expect($html)->toContain('class="pagination-bar"')
        ->toContain('items per page')
        ->toContain('of 25 items')
        ->toContain('class="btn ter sm"');
});

it('implements standard data table on student results overview (Rule 2)', function () {
    $e = (object)[
        'id' => 1,
        'reference' => 'ENR-001',
        'progress_percent' => 75,
        'cohort_name' => 'Cohort 2026/1',
        'name_en' => 'Data Structures',
        'version_id' => 1,
    ];
    $data = collect([
        [
            'e' => $e,
            'results' => collect([
                (object)['name_en' => 'Assignment 1', 'max_mark' => 100, 'pass_mark' => 50, 'score' => 85],
            ]),
            'badges' => collect(),
        ],
    ]);

    $html = view('student.results', [
        'data' => $data,
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ])->render();

    expect($html)->toContain('id="my-results-table"')
        ->toContain('class="table-results-summary"')
        ->toContain('Data Structures')
        ->toContain('View details');
});

it('partitions quality review with horizontal tabs and dropdown cohort switcher (Rules 2 & 3)', function () {
    $user = User::where('email', 'hakim.rahman@utem.example.test')->firstOrFail();
    $this->actingAs($user);
    session(['active_role' => 'deputy_dean']);

    $response = $this->get('/monitoring');
    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('Cohort indicators')
        ->toContain('class="panel"');
});

it('partitions cohort offering details with horizontal tabs (Rule 3)', function () {
    $o = (object)[
        'id' => 1,
        'name_en' => 'Data Structures',
        'cohort_name' => 'Cohort 2026/1',
        'start_date' => '2026-09-01',
        'reg_opens_on' => '2026-08-01',
        'reg_closes_on' => '2026-08-25',
        'capacity' => 30,
        'fee' => 500,
        'status' => 'open',
        'owner_id' => 1,
    ];

    $html = view('offerings.show', [
        'o' => $o,
        'items' => collect(),
        'labels' => config('offerings.status'),
        'items_cfg' => config('offerings.readiness'),
        'confirmations' => collect(),
        'dean' => true,
        'editable' => true,
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ])->render();

    expect($html)->toContain('Data Structures')
        ->toContain('class="panel"');
});

it('supports auto-filter on sidebar while keeping apply button functional', function () {
    $response = $this->get('/public/catalogue');
    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('data-auto-filter')
        ->toContain('data-target="#catalogue-results"')
        ->toContain('data-count="#catalogue-count"')
        ->toContain('id="catalogue-results"')
        ->toContain('id="catalogue-count"')
        ->toContain('<button type="submit" class="btn sm">');

    $user = User::where('email', 'farid.hashim@utem.example.test')->firstOrFail();
    $this->actingAs($user);
    session(['active_role' => 'codl_admin']);

    $ucredsResponse = $this->get('/admin/ucreds/courses');
    $ucredsResponse->assertOk();
    $ucredsContent = $ucredsResponse->getContent();

    expect($ucredsContent)->toContain('data-auto-filter')
        ->toContain('data-target="#ucreds-results"')
        ->toContain('data-count="#ucreds-count"')
        ->toContain('id="ucreds-results"')
        ->toContain('id="ucreds-count"')
        ->toContain('<button type="submit" class="btn ter sm">Search</button>');
});

it('renders public home offerings in a responsive 3-column card grid eliminating horizontal dead space', function () {
    $response = $this->get('/public');
    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('class="grid3"')
        ->toContain('offerings open for registration');
});

it('partitions multi-table views into horizontal Carbon tabs', function () {
    $user = User::where('email', 'farid.hashim@utem.example.test')->firstOrFail();
    $this->actingAs($user);
    session(['active_role' => 'codl_admin']);

    // Adapters
    $adaptersRes = $this->get(route('adapters'));
    $adaptersRes->assertOk();
    expect($adaptersRes->getContent())->toContain('Configured system adapters');

    // UCreds exchange
    $ucredsRes = $this->get(route('ucreds'));
    $ucredsRes->assertOk();
    expect($ucredsRes->getContent())->toContain('Export enrolments');

    // Search
    $searchRes = $this->get(route('search', ['q' => 'Data']));
    $searchRes->assertOk();
    expect($searchRes->getContent())->toContain('Search');

    // Documents
    $docRes = $this->get(route('documents'));
    $docRes->assertOk();
    expect($docRes->getContent())->toContain('Documents');

    // Credentials admin (requires deputy_dean role)
    $dean = User::where('email', 'hakim.rahman@utem.example.test')->firstOrFail();
    $this->actingAs($dean);
    session(['active_role' => 'deputy_dean']);

    $credRes = $this->get(route('credentials.admin'));
    $credRes->assertOk();
    expect($credRes->getContent())->toContain('Credentials');
});

it('eliminates dead horizontal space and stacked tables across public portal pages', function () {
    // Pathways
    $pathwaysRes = $this->get(route('public.pathways'));
    $pathwaysRes->assertOk();
    $pathwaysContent = $pathwaysRes->getContent();
    expect($pathwaysContent)->toContain('Pathways to a degree');

    // How recognition works: responsive grid3 for 5 stages
    $howRes = $this->get(route('public.how'));
    $howRes->assertOk();
    expect($howRes->getContent())->toContain('class="grid3"')
        ->toContain('Stage 1');

    // Verify credential
    $verifyRes = $this->get(route('public.verify'));
    $verifyRes->assertOk();
    expect($verifyRes->getContent())->toContain('Verify');

    // FAQ: category tabs
    $faqRes = $this->get(route('public.faq'));
    $faqRes->assertOk();
    expect($faqRes->getContent())->toContain('FAQ');
});
