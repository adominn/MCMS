<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'MCMS') - UTeM Micro-Credential Management System</title>
  <link rel="stylesheet" href="{{ asset('css/mcms.css') }}?v={{ filemtime(public_path('css/mcms.css')) }}">
  <script>
    (function(){
      try {
        if(localStorage.getItem('mcms_side_collapsed')==='1'){document.documentElement.classList.add('side-collapsed');}
      } catch(e){}
    })();
  </script>
</head>
<body>
@php
  $u = auth()->user();
  $activeRoles = $u ? $u->activeRoles() : collect();
  $currentRoleObj = $u?->currentRole();
  $activeRole = $currentRoleObj?->value;
  $roleLabel = $currentRoleObj?->label();
  $homeUrl = $u ? $u->homeUrl() : route('public.home');

  $unread = $u ? \Illuminate\Support\Facades\DB::table('notifications')->where('user_id', $u->id)->where('channel', 'in_system')->whereNull('read_at')->count() : 0;
  $tasksOpen = $u ? app(\App\Modules\Identity\TaskController::class)->visible($u)->whereNull('completed_at')->count() : 0;
  $searchHint = $u ? (in_array($activeRole, ['codl_admin']) ? 'Search users, settings, files' : (in_array($activeRole, ['deputy_dean', 'ptj_authority', 'academic_authority']) ? 'Search micro-credentials, learners, credentials' : (in_array($activeRole, ['lecturer', 'qa_officer']) ? 'Search micro-credentials, cohorts, learners' : (in_array($activeRole, ['records_custodian', 'abc_depositor', 'records_depositor']) ? 'Search credentials, deposits, learners' : null)))) : null;
  $ptjCode = $u ? \Illuminate\Support\Facades\DB::table('role_assignments as ra')->join('ptjs', 'ptjs.id', '=', 'ra.ptj_id')->where('ra.user_id', $u->id)->value('ptjs.code') : null;
  $recent = $u && ! request()->routeIs('public.*') ? \Illuminate\Support\Facades\DB::table('notifications')->where('user_id', $u->id)->where('channel', 'in_system')->orderByDesc('created_at')->limit(5)->get() : collect();
  $initials = $u ? collect(explode(' ', preg_replace('/^(Dr\.|Ir\.|Prof\.)\s+/', '', $u->name)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') : '';
  $icons = [
    'home' => '<path d="M2 8l6-5 6 5v6H2z"/>', 'list' => '<path d="M3 4h10M3 8h10M3 12h10"/>', 'cart' => '<path d="M2 3h2l1.5 7h6L13 5H5"/><circle cx="6" cy="13" r="1"/><circle cx="11" cy="13" r="1"/>',
    'check' => '<rect x="2.5" y="2.5" width="11" height="11"/><path d="M5 8l2 2 4-4"/>', 'award' => '<circle cx="8" cy="6" r="3.5"/><path d="M5.5 9l-1 5 3.5-2 3.5 2-1-5"/>', 'bank' => '<path d="M2 6l6-3 6 3M3 6v6M6.5 6v6M9.5 6v6M13 6v6M2 13h12"/>',
    'swap' => '<path d="M3 5h10l-3-3M13 11H3l3 3"/>', 'cap' => '<path d="M1 6l7-3 7 3-7 3zM4 8v3c2 1.5 6 1.5 8 0V8"/>', 'doc' => '<path d="M4 2h5l3 3v9H4z"/><path d="M9 2v3h3"/>', 'chart' => '<path d="M3 13V8M7 13V4M11 13V7M15 13H1"/>',
    'shield' => '<path d="M8 2l5 2v4c0 3-2 5-5 6-3-1-5-3-5-6V4z"/>', 'users' => '<circle cx="6" cy="5" r="2.5"/><path d="M1.5 13c0-3 2-4.5 4.5-4.5S10.5 10 10.5 13M11 4.5a2 2 0 010 4M12 9c1.5.4 2.5 1.7 2.5 4"/>', 'gear' => '<circle cx="8" cy="8" r="2.5"/><path d="M8 1.5v2M8 12.5v2M1.5 8h2M12.5 8h2M3.4 3.4l1.4 1.4M11.2 11.2l1.4 1.4M12.6 3.4l-1.4 1.4M4.8 11.2l-1.4 1.4"/>',
    'bell' => '<path d="M4 11V7a4 4 0 018 0v4l1 1H3zM6.5 14a1.5 1.5 0 003 0"/>', 'search' => '<circle cx="7" cy="7" r="4.5"/><path d="M10.5 10.5L14 14"/>', 'clock' => '<circle cx="8" cy="8" r="6"/><path d="M8 4.5V8l2.5 1.5"/>', 'edit' => '<path d="M2 14l1-4 8-8 3 3-8 8z"/>',
  ];

  $areaCount = fn ($a) => $u && in_array($activeRole, ['deputy_dean', 'ptj_authority', 'academic_authority', 'records_depositor', 'records_custodian', 'abc_depositor', 'qa_officer', 'codl_admin'], true) ? app(\App\Modules\Identity\TaskController::class)->visible($u)->whereNull('completed_at')->where('area', $a)->count() : 0;
  $counts = match ($activeRole) {
    'codl_admin' => [
      'approvals' => $areaCount('proposals'),
      'transfers' => $areaCount('transfers'),
    ],
    'deputy_dean', 'ptj_authority' => [
      'approvals' => $areaCount('proposals'),
      'offerings' => $areaCount('offerings'),
      'results' => $areaCount('results'),
      'transfers' => $areaCount('transfers'),
      'credentials.admin' => $areaCount('credentials'),
    ],
    'academic_authority' => [
      'approvals' => $areaCount('proposals'),
      'transfers' => $areaCount('transfers'),
    ],
    'qa_officer' => [
      'monitoring' => $areaCount('proposals'),
    ],
    'records_custodian' => [
      'credentials.admin' => $areaCount('credentials'),
      'results' => $areaCount('results'),
    ],
    'abc_depositor' => [
      'deposits' => $areaCount('deposits'),
      'transfers' => $areaCount('transfers'),
    ],
    'records_depositor' => [
      'deposits' => $areaCount('deposits'),
      'credentials.admin' => $areaCount('credentials'),
    ],
    'lecturer' => [
      'proposals' => \Illuminate\Support\Facades\DB::table('micro_credentials as m')->join('mc_versions as v', 'v.micro_credential_id', '=', 'm.id')->where('m.owner_id', $u->id)->whereIn('v.status', ['draft', 'returned'])->count(),
      'reviews' => \Illuminate\Support\Facades\DB::table('review_assignments')->where('reviewer_id', $u->id)->whereIn('status', ['invited', 'in_progress'])->count(),
    ],
    default => [],
  };

  $nav = match ($activeRole) {
    'student' => [
      ['Learning', 'My learning', 'tasks', ['tasks*'], 'home'],
      ['Learning', 'Find a micro-credential', 'catalogue', ['catalogue*', 'public.catalogue*', 'offering*', 'public.offering*'], 'search'],
      ['Learning', 'Registrations and payments', 'enrolments', ['enrolments*', 'register*'], 'cart'],
      ['Learning', 'My results', 'results.mine', ['results.mine*'], 'check'],
      ['Learning', 'Feedback', 'feedback', ['feedback*'], 'edit'],
      ['Achievements', 'Credentials', 'credentials.wallet', ['credentials.wallet*', 'credentials.show*', 'credentials.certificate*', 'credentials.sheet*'], 'award'],
      ['Achievements', 'Achievement record', 'records.mine', ['records.mine*'], 'doc'],
      ['Credit', 'Credit ledger and ABC', 'credit', ['credit*'], 'bank'],
      ['Credit', 'Credit transfer', 'transfer', ['transfer*'], 'swap'],
      ['Credit', 'My degree plan', 'plan.mine', ['plan.mine*'], 'cap'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'lecturer' => [
      ['Teaching', 'My tasks', 'tasks', ['tasks*'], 'home'],
      ['Teaching', 'My micro-credentials', 'mcs', ['mcs*'], 'list'],
      ['Teaching', 'Proposals and versions', 'proposals', ['proposals*', 'comments.respond*'], 'edit'],
      ['Teaching', 'Cohorts and results', 'offerings', ['offerings*', 'results.cohort*', 'results.save*', 'results.flag*', 'completion.signoff*', 'cohorts.credits*'], 'cap'],
      ['Teaching', 'Reviews for colleagues', 'reviews', ['reviews*'], 'check'],
      ['Quality', 'Quality review (CQI)', 'monitoring', ['monitoring*', 'cqi*', 'reviews.periodic*'], 'chart'],
      ['Quality', 'QV evidence', 'quality', ['quality*'], 'shield'],
      ['Quality', 'Stackable pathways', 'pathways', ['pathways*', 'plans*'], 'cap'],
      ['Insight', 'Reports', 'reports', ['reports*'], 'chart'],
      ['Insight', 'Traceability', 'trace', ['trace*', 'cohort.records*'], 'search'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'deputy_dean', 'ptj_authority' => [
      ['Academic Governance', 'Task queue', 'tasks', ['tasks*'], 'home'],
      ['Academic Governance', 'Proposals and approval', 'approvals', ['approvals*', 'changes*'], 'edit'],
      ['Academic Governance', 'Quality Verification', 'quality', ['quality*'], 'shield'],
      ['Academic Governance', 'Quality review', 'monitoring', ['monitoring*', 'cqi*', 'suspensions*'], 'chart'],
      ['Academic Governance', 'Stackable pathways', 'pathways', ['pathways*', 'plans*'], 'cap'],
      ['Cohort Operations', 'Offerings and readiness', 'offerings', ['offerings*'], 'cap'],
      ['Cohort Operations', 'Results and completion', 'results', ['results*', 'completion*'], 'check'],
      ['Credit & Certification', 'Credentials', 'credentials.admin', ['credentials.admin*'], 'award'],
      ['Credit & Certification', 'Transfer monitor', 'transfers', ['transfers*'], 'swap'],
      ['Insight & Audit', 'Reports', 'reports', ['reports*'], 'chart'],
      ['Insight & Audit', 'Traceability', 'trace', ['trace*', 'records.audit*', 'discrepancies*'], 'search'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'academic_authority' => [
      ['Senate & Academic Decisions', 'Task queue', 'tasks', ['tasks*'], 'home'],
      ['Senate & Academic Decisions', 'Senate approvals', 'approvals', ['approvals*', 'changes*'], 'edit'],
      ['Senate & Academic Decisions', 'Credit transfer decisions', 'transfers', ['transfers*'], 'swap'],
      ['Senate & Academic Decisions', 'Stackable pathways', 'pathways', ['pathways*', 'plans*'], 'cap'],
      ['Quality & Standards', 'Quality Verification', 'quality', ['quality*'], 'shield'],
      ['Quality & Standards', 'Quality review (CQI)', 'monitoring', ['monitoring*', 'cqi*', 'suspensions*'], 'chart'],
      ['Insight & Audit', 'Reports', 'reports', ['reports*'], 'chart'],
      ['Insight & Audit', 'Traceability', 'trace', ['trace*', 'records.audit*', 'discrepancies*'], 'search'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'qa_officer' => [
      ['Quality Assurance', 'Task queue', 'tasks', ['tasks*'], 'home'],
      ['Quality Assurance', 'Quality Verification (QV)', 'quality', ['quality*'], 'shield'],
      ['Quality Assurance', 'Quality review (CQI)', 'monitoring', ['monitoring*', 'cqi*', 'suspensions*'], 'chart'],
      ['Quality Assurance', 'Proposals review', 'approvals', ['approvals*'], 'edit'],
      ['Insight & Audit', 'Cohort records', 'trace', ['trace*', 'cohort.records*'], 'search'],
      ['Insight & Audit', 'Reports', 'reports', ['reports*'], 'chart'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'records_custodian' => [
      ['Registry & Credentials', 'Task queue', 'tasks', ['tasks*'], 'home'],
      ['Registry & Credentials', 'Credentials management', 'credentials.admin', ['credentials.admin*'], 'award'],
      ['Registry & Credentials', 'Results & completion', 'results', ['results*', 'completion*'], 'check'],
      ['Registry & Credentials', 'Discrepancies & corrections', 'discrepancies', ['discrepancies*'], 'check'],
      ['Insight & Audit', 'Cohort records', 'trace', ['trace*', 'cohort.records*'], 'search'],
      ['Insight & Audit', 'Audit trail', 'records.audit', ['records.audit*'], 'list'],
      ['Insight & Audit', 'Reports', 'reports', ['reports*'], 'chart'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'abc_depositor' => [
      ['Credit Bank & ABC', 'Task queue', 'tasks', ['tasks*'], 'home'],
      ['Credit Bank & ABC', 'ACR & ABC deposits', 'deposits', ['deposits*'], 'bank'],
      ['Credit Bank & ABC', 'Credit transfer redemptions', 'transfers', ['transfers*'], 'swap'],
      ['Credit Bank & ABC', 'Discrepancies & corrections', 'discrepancies', ['discrepancies*'], 'check'],
      ['Insight & Audit', 'Traceability', 'trace', ['trace*'], 'search'],
      ['Insight & Audit', 'Audit trail', 'records.audit', ['records.audit*'], 'list'],
      ['Insight & Audit', 'Reports', 'reports', ['reports*'], 'chart'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'records_depositor' => [
      ['Registry & ABC', 'Task queue', 'tasks', ['tasks*'], 'home'],
      ['Registry & ABC', 'Credentials', 'credentials.admin', ['credentials.admin*'], 'award'],
      ['Registry & ABC', 'ACR & ABC deposits', 'deposits', ['deposits*'], 'bank'],
      ['Registry & ABC', 'Discrepancies & corrections', 'discrepancies', ['discrepancies*'], 'check'],
      ['Insight & Audit', 'Traceability', 'trace', ['trace*'], 'search'],
      ['Insight & Audit', 'Audit trail', 'records.audit', ['records.audit*'], 'list'],
      ['Insight & Audit', 'Reports', 'reports', ['reports*'], 'chart'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'codl_admin' => [
      ['Academic Governance', 'Proposals & Senate', 'approvals', ['approvals*', 'changes*'], 'edit'],
      ['Credit & Certification', 'Credit transfers', 'transfers', ['transfers*'], 'swap'],
      ['Administration', 'Users and roles', 'users', ['users*'], 'users'],
      ['Administration', 'Reference data', 'reference', ['reference*'], 'list', 'ptj'],
      ['Administration', 'Settings', 'settings', ['settings*'], 'gear'],
      ['Administration', 'Templates', 'templates', ['templates*'], 'doc'],
      ...(\App\Modules\Core\DemoClock::enabled() ? [['Administration', 'Demo clock', 'demo.clock', ['demo.clock*'], 'clock']] : []),
      ['Data exchange', 'UCreds exchange', 'ucreds', ['ucreds', 'ucreds.export*', 'ucreds.check*', 'ucreds.report*', 'ucreds.stage*', 'ucreds.errors*'], 'swap'],
      ['Data exchange', 'UCreds content', 'ucreds.courses', ['ucreds.courses*'], 'list'],
      ['Data exchange', 'Adapter status', 'adapters', ['adapters*'], 'gear'],
      ['Data exchange', 'Notification log', 'notification.log', ['notification.log*'], 'bell'],
      ['Records', 'Audit trail', 'audit', ['audit*'], 'list'],
      ['Records', 'Reports', 'reports', ['reports*'], 'chart'],
      ['Records', 'Retention', 'retention', ['retention*'], 'shield'],
      ['General', 'Documents', 'documents', ['documents*'], 'doc'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    'industry_expert', 'reviewer' => [
      ['Invitations', 'My review invitations', 'reviews', ['reviews*'], 'check'],
      ['General', 'Notifications', 'notifications', ['notifications*'], 'bell'],
    ],
    default => [],
  };
@endphp
@php $isPublicRoute = request()->routeIs('public.*', 'verify*'); @endphp
<div class="app">
  <header class="top">
    @auth
      @if(!$isPublicRoute)
      <button type="button" class="iconbtn nav-toggle" id="nav-toggle" aria-label="{{ __('Toggle navigation') }}" title="{{ __('Toggle navigation') }}">
        <svg viewBox="0 0 16 16" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
          <path d="M2.5 4h11M2.5 8h11M2.5 12h11"/>
        </svg>
      </button>
      @endif
    @endauth
    <a class="brand" href="{{ $isPublicRoute ? route('public.home') : $homeUrl }}">
      <img src="{{ asset('images/LogoJawiUTeM_blue-01.png') }}" alt="UTeM" class="brand-logo">
      <span class="brand-sep" aria-hidden="true"></span>
      <b>UTeM MCMS</b>
      <span class="brand-sub">{{ $isPublicRoute ? __('Public Verification & Credential Portal') : __('Micro-Credential Management') }}</span>
    </a>
    @if($u && $searchHint && !$isPublicRoute)
      <form class="search" role="search" method="get" action="{{ route('search') }}" style="margin-left:24px"><svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">{!! $icons['search'] !!}</svg><input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="{{ __($searchHint) }}" aria-label="Search"></form>
    @endif
    <div class="right" style="gap:8px">
      @guest
        <a class="btn sm" href="{{ route('signin') }}">{{ __('Sign in') }}</a>
        <form method="post" action="{{ route('language') }}" class="lang" aria-label="Language" style="margin-left:8px">@csrf<button type="submit" name="lang" value="en" class="{{ app()->getLocale() === 'en' ? 'on' : 'off' }}">EN</button><button type="submit" name="lang" value="ms" class="{{ app()->getLocale() === 'ms' ? 'on' : 'off' }}">BM</button></form>
      @endguest
      @auth
        <form method="post" action="{{ route('language') }}" class="lang" style="margin-right:4px" aria-label="Language">@csrf<button type="submit" name="lang" value="en" class="{{ app()->getLocale() === 'en' ? 'on' : 'off' }}" aria-pressed="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}">EN</button><button type="submit" name="lang" value="ms" class="{{ app()->getLocale() === 'ms' ? 'on' : 'off' }}" aria-pressed="{{ app()->getLocale() === 'ms' ? 'true' : 'false' }}">BM</button></form>
        @if(!$isPublicRoute)
        <a class="iconbtn" href="{{ route('help') }}" title="Help" aria-label="Help"><svg viewBox="0 0 16 16" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true"><circle cx="8" cy="8" r="6.5"/><path d="M6 6.2a2 2 0 013.9.6c0 1.3-1.9 1.6-1.9 2.8M8 11.6v.1"/></svg></a>
        <div class="anchor">
          <button type="button" class="iconbtn" data-menu="pop-notices" aria-haspopup="true" aria-expanded="false" aria-label="Notices: {{ $unread }} unread"><svg viewBox="0 0 16 16" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons['bell'] !!}</svg>@if($unread)<span class="badge">{{ $unread }}</span>@endif</button>
          <div class="pop notif" id="pop-notices" hidden>
            <div class="ph" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
              <div>
                <b>{{ __('Notices') }}</b>
                <span class="small muted" style="margin-left:4px">{{ __(':count unread', ['count' => $unread]) }}</span>
              </div>
              @if($unread > 0)
                <form method="post" action="{{ route('notifications.readall') }}" style="margin:0">
                  @csrf
                  <button type="submit" class="btn ghost sm notif-markall-btn" title="{{ __('Mark all notices as read') }}">
                    {{ __('Mark all as read') }}
                  </button>
                </form>
              @endif
            </div>
            <div class="notif-list" style="max-height:360px;overflow-y:auto">
              @forelse($recent as $n)
                <div class="ni {{ $n->read_at ? '' : 'unread' }}">
                  <a class="ni-link" href="{{ route('notifications.open', $n->id) }}">
                    <div class="t">{{ $n->subject }}</div>
                    <div class="s">{{ \Carbon\Carbon::parse($n->created_at)->format('d/m/Y H:i') }}</div>
                  </a>
                  @if(!$n->read_at)
                    <form method="post" action="{{ route('notifications.read', $n->id) }}" style="margin:0;flex-shrink:0">
                      @csrf
                      <button type="submit" class="notif-read-btn" title="{{ __('Mark as read') }}" aria-label="{{ __('Mark as read') }}">
                        <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="margin-right:2px"><path d="M2.5 8.5l3.5 3.5 7.5-7.5"/></svg>
                        <span>{{ __('Mark read') }}</span>
                      </button>
                    </form>
                  @endif
                </div>
              @empty
                <div class="ni muted" style="padding:16px;text-align:center">{{ __('No notices yet.') }}</div>
              @endforelse
            </div>
            <a class="notif-all-link" href="{{ route('notifications') }}">{{ __('See all notices') }} &rarr;</a>
          </div>
        </div>
        <div class="anchor">
          <button type="button" class="who-btn menu-btn" data-menu="pop-user" aria-haspopup="true" aria-expanded="false"><span class="avatar" aria-hidden="true">{{ $initials }}</span><span>{{ $u->name }}<small>{{ $roleLabel }}@if($ptjCode), {{ $ptjCode }}@endif</small></span><svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M4 6l4 4 4-4"/></svg></button>
          <div class="pop" id="pop-user" hidden style="width:280px">
            <div class="ph"><div style="font-weight:600">{{ $u->name }}</div><div class="small muted">{{ $u->email }}</div><div class="small muted">{{ $roleLabel }}@if($ptjCode), {{ $ptjCode }}@endif</div></div>
            @if($activeRoles->count() > 1)
              <div class="role-switcher">
                <span class="role-lbl">{{ __('Switch workspace') }}</span>
                <div class="role-btns">
                  @foreach($activeRoles as $r)
                    <form method="post" action="{{ route('role.switch') }}" style="display:inline">
                      @csrf
                      <button type="submit" name="role" value="{{ $r->value }}" class="role-btn {{ $activeRole === $r->value ? 'on' : '' }}">
                        {{ $r->label() }}
                      </button>
                    </form>
                  @endforeach
                </div>
              </div>
            @endif
            <a class="mi" href="{{ route('profile') }}">{{ __('My profile') }}</a>
            <a class="mi" href="{{ route('profile') }}#notifications">{{ __('Notification preferences') }}</a>
            <a class="mi" href="{{ route('profile') }}#sessions">{{ __('Sign-in and sessions') }}</a>
            @if($u->hasAnyRole('student', 'lecturer', 'deputy_dean', 'records_depositor', 'codl_admin'))
              <a class="mi" href="{{ route('documents') }}">{{ __('My documents') }}</a>
            @endif
            <a class="mi" href="{{ route('help') }}">{{ __('Help Center') }}</a>
            <div class="sep"></div>
            <form method="post" action="{{ route('signout') }}">@csrf <button type="submit" class="mi" style="width:100%;background:none;color:var(--ink);height:auto;border:0">{{ __('Sign out') }}</button></form>
          </div>
        </div>
        @else
          <a class="btn sm" href="{{ $homeUrl }}" style="margin-left:8px">{{ __('Return to portal') }} &rarr;</a>
        @endif
      @endauth
    </div>
  </header>
  <div class="body">
    @auth
    @if(!$isPublicRoute)
    <nav class="side" aria-label="Main">
      @foreach(collect($nav)->groupBy(0) as $group => $items)
        @if($group !== 'General' && $group !== 'Messages')<div class="grp">{{ __($group) }}</div>@endif
        @foreach($items as $i)
          <a href="{{ isset($i[5]) ? route($i[2], $i[5]) : route($i[2]) }}" @if(request()->routeIs(...$i[3])) aria-current="page" @endif title="{{ __($i[1]) }}"><svg viewBox="0 0 16 16" aria-hidden="true">{!! $icons[$i[4]] !!}</svg><span class="lbl">{{ __($i[1]) }}</span>@if($i[1] === 'Notifications' && $unread)<span class="c">{{ $unread }}</span>@elseif($i[2] === 'tasks' && $tasksOpen)<span class="c">{{ $tasksOpen }}</span>@elseif(($counts[$i[2]] ?? 0) > 0)<span class="c">{{ $counts[$i[2]] }}</span>@endif</a>
        @endforeach
      @endforeach
      <div class="side-foot" style="margin-top:auto;padding-top:12px;border-top:1px solid var(--blue-60)">
        <button type="button" class="side-collapse-btn" id="side-collapse-btn" aria-label="{{ __('Collapse sidebar') }}" title="{{ __('Collapse sidebar') }}">
          <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon-collapse" aria-hidden="true">
            <path d="M11 3l-5 5 5 5M6 3l-5 5 5 5"/>
          </svg>
          <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon-expand" aria-hidden="true" style="display:none">
            <path d="M5 3l5 5-5 5M10 3l5 5-5 5"/>
          </svg>
          <span class="lbl">{{ __('Collapse sidebar') }}</span>
        </button>
      </div>
    </nav>
    <div class="side-backdrop" id="side-backdrop"></div>
    @endif
    @endauth
    <main>
      @if(session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
      @if(session('broken'))<div class="status error" role="alert">{{ session('broken') }}</div>@endif
      @if($errors->any())<div class="status error" role="alert"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
      @yield('content')
    </main>
  </div>
</div>
<script src="{{ asset('js/mcms.js') }}" defer></script>
</body>
</html>
