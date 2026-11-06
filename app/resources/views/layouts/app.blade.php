<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'MCMS') - UTeM Micro-Credential Management System</title>
  <link rel="stylesheet" href="{{ asset('css/mcms.css') }}">
  <script>try{if(localStorage.getItem('mcms_side_collapsed')==='1'){document.documentElement.classList.add('side-collapsed');}}catch(e){}</script>
</head>
<body>
@php
  $u = auth()->user();
  $admin = $u && $u->hasAnyRole('codl_admin');
  $unread = $u ? \Illuminate\Support\Facades\DB::table('notifications')->where('user_id', $u->id)->where('channel', 'in_system')->whereNull('read_at')->count() : 0;
  $tasksOpen = $u ? app(\App\Modules\Identity\TaskController::class)->visible($u)->whereNull('completed_at')->count() : 0;
  $searchHint = $u ? ($u->hasAnyRole('codl_admin') ? 'Search users, settings, files' : ($u->hasAnyRole('deputy_dean') ? 'Search micro-credentials, learners, credentials' : ($u->hasAnyRole('lecturer') ? 'Search micro-credentials, cohorts, learners' : null))) : null;
  $ptjCode = $u ? \Illuminate\Support\Facades\DB::table('role_assignments as ra')->join('ptjs', 'ptjs.id', '=', 'ra.ptj_id')->where('ra.user_id', $u->id)->value('ptjs.code') : null;
  $recent = $u && ! request()->routeIs('public.*') ? \Illuminate\Support\Facades\DB::table('notifications')->where('user_id', $u->id)->where('channel', 'in_system')->orderByDesc('created_at')->limit(5)->get() : collect();
  $roleLabel =$u ? \App\Modules\Identity\Role::tryFrom((string) session('active_role'))?->label() : null;
  $initials = $u ? collect(explode(' ', preg_replace('/^(Dr\.|Ir\.|Prof\.)\s+/', '', $u->name)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') : '';
  $icons = [
    'home' => '<path d="M2 8l6-5 6 5v6H2z"/>', 'list' => '<path d="M3 4h10M3 8h10M3 12h10"/>', 'cart' => '<path d="M2 3h2l1.5 7h6L13 5H5"/><circle cx="6" cy="13" r="1"/><circle cx="11" cy="13" r="1"/>',
    'check' => '<rect x="2.5" y="2.5" width="11" height="11"/><path d="M5 8l2 2 4-4"/>', 'award' => '<circle cx="8" cy="6" r="3.5"/><path d="M5.5 9l-1 5 3.5-2 3.5 2-1-5"/>', 'bank' => '<path d="M2 6l6-3 6 3M3 6v6M6.5 6v6M9.5 6v6M13 6v6M2 13h12"/>',
    'swap' => '<path d="M3 5h10l-3-3M13 11H3l3 3"/>', 'cap' => '<path d="M1 6l7-3 7 3-7 3zM4 8v3c2 1.5 6 1.5 8 0V8"/>', 'doc' => '<path d="M4 2h5l3 3v9H4z"/><path d="M9 2v3h3"/>', 'chart' => '<path d="M3 13V8M7 13V4M11 13V7M15 13H1"/>',
    'shield' => '<path d="M8 2l5 2v4c0 3-2 5-5 6-3-1-5-3-5-6V4z"/>', 'users' => '<circle cx="6" cy="5" r="2.5"/><path d="M1.5 13c0-3 2-4.5 4.5-4.5S10.5 10 10.5 13M11 4.5a2 2 0 010 4M12 9c1.5.4 2.5 1.7 2.5 4"/>', 'gear' => '<circle cx="8" cy="8" r="2.5"/><path d="M8 1.5v2M8 12.5v2M1.5 8h2M12.5 8h2M3.4 3.4l1.4 1.4M11.2 11.2l1.4 1.4M12.6 3.4l-1.4 1.4M4.8 11.2l-1.4 1.4"/>',
    'bell' => '<path d="M4 11V7a4 4 0 018 0v4l1 1H3zM6.5 14a1.5 1.5 0 003 0"/>', 'search' => '<circle cx="7" cy="7" r="4.5"/><path d="M10.5 10.5L14 14"/>', 'clock' => '<circle cx="8" cy="8" r="6"/><path d="M8 4.5V8l2.5 1.5"/>', 'edit' => '<path d="M2 14l1-4 8-8 3 3-8 8z"/>',
  ];
  $r = fn (string ...$roles) => $u && $u->hasAnyRole(...$roles);
  // group, label, route, route patterns that mark it current, visible when, icon
  $nav = [
    ['Work', ($u && $u->hasAnyRole('student') && ! $u->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin', 'industry_expert')) ? 'My learning' : 'My tasks', 'tasks', ['tasks*'], true, 'home'],
    ['Learning', 'Registrations and payments', 'enrolments', ['enrolments*', 'register*'], $r('student'), 'cart'],
    ['Learning', 'My results', 'results.mine', ['results.mine'], $r('student'), 'check'],
    ['Learning', 'Find a micro-credential', 'catalogue', ['catalogue*', 'public.catalogue*', 'offering*', 'public.offering*'], $r('student'), 'search'],
    ['Learning', 'Feedback', 'feedback', ['feedback*'], $r('student'), 'edit'],
    ['Achievements', 'Credentials', 'credentials.wallet', ['credentials.wallet', 'credentials.show', 'credentials.certificate'], $r('student'), 'award'],
    ['Achievements', 'Achievement record', 'records.mine', ['records.mine'], $r('student'), 'doc'],
    ['Credit', 'Credit ledger and ABC', 'credit', ['credit*'], $r('student'), 'bank'],
    ['Credit', 'Credit transfer', 'transfer', ['transfer', 'transfer.*'], $r('student'), 'swap'],
    ['Credit', 'My degree plan', 'plan.mine', ['plan.mine'], $r('student'), 'cap'],
    ['Teaching', 'My proposals', 'proposals', ['proposals*'], $r('lecturer'), 'edit'],
    ['Teaching', 'My micro-credentials', 'mcs', ['mcs*'], $r('lecturer'), 'list'],
    ['Teaching', 'Offerings', 'offerings', ['offerings*'], $r('lecturer', 'deputy_dean'), 'cap'],
    ['Teaching', 'Reviews', 'reviews', ['reviews*'], $r('lecturer', 'industry_expert'), 'check'],
    ['Quality', 'Quality Verification', 'quality', ['quality*'], $r('lecturer', 'deputy_dean'), 'shield'],
    ['Quality', 'Quality review', 'monitoring', ['monitoring*', 'cqi*', 'reviews.periodic*', 'suspensions*'], $r('lecturer', 'deputy_dean'), 'chart'],
    ['Quality', 'Stackable pathways', 'pathways', ['pathways*', 'plans*'], $r('lecturer', 'deputy_dean'), 'cap'],
    ['Decisions', 'Proposals and approval', 'approvals', ['approvals*'], $r('deputy_dean'), 'edit'],
    ['Decisions', 'Results and completion', 'results', ['results', 'results.*', 'completion*'], $r('deputy_dean'), 'check'],
    ['Decisions', 'Credentials', 'credentials.admin', ['credentials.admin*'], $r('deputy_dean'), 'award'],
    ['Decisions', 'ACR and ABC deposits', 'deposits', ['deposits*'], $r('deputy_dean'), 'bank'],
    ['Decisions', 'Credit transfer evaluation', 'transfers', ['transfers*'], $r('deputy_dean'), 'swap'],
    ['Records', 'Reports', 'reports', ['reports*'], $r('lecturer', 'deputy_dean', 'codl_admin'), 'chart'],
    ['Records', 'Traceability and records', 'trace', ['trace*', 'cohort*', 'discrepancies*', 'records.audit*'], $r('lecturer', 'deputy_dean'), 'search'],
    ['Records', 'Documents', 'documents', ['documents'], true, 'doc'],
    ['Administration', 'Users and roles', 'users', ['users*'], $admin, 'users'],
    ['Administration', 'Settings', 'settings', ['settings*'], $admin, 'gear'],
    ['Administration', 'Reference data', 'reference', ['reference*'], $admin, 'list', 'ptj'],
    ['Administration', 'Templates', 'templates', ['templates*'], $admin, 'doc'],
    ['Administration', 'UCreds exchange', 'ucreds', ['ucreds', 'ucreds.export', 'ucreds.check', 'ucreds.report', 'ucreds.stage', 'ucreds.errors'], $admin, 'swap'],
    ['Administration', 'UCreds content', 'ucreds.courses', ['ucreds.courses'], $admin, 'list'],
    ['Administration', 'Adapters', 'adapters', ['adapters'], $admin, 'gear'],
    ['Administration', 'Notification log', 'notification.log', ['notification.log'], $admin, 'bell'],
    ['Administration', 'Demo clock', 'demo.clock', ['demo.clock*'], $admin && \App\Modules\Core\DemoClock::enabled(), 'clock'],
    ['Administration', 'Retention', 'retention', ['retention*'], $admin, 'shield'],
    ['Administration', 'Audit trail', 'audit', ['audit'], $admin, 'list'],
    ['Messages', 'Notifications', 'notifications', ['notifications'], true, 'bell'],
  ];
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
    <a class="brand" href="{{ $isPublicRoute ? route('public.home') : ($u ? route('tasks') : route('public.home')) }}"><b>UTeM MCMS</b><span>{{ $isPublicRoute ? __('Public Verification & Credential Portal') : __('Micro-Credential Management') }}</span></a>
    @if($u && $searchHint && !$isPublicRoute)
      <form class="search" role="search" method="get" action="{{ route('search') }}" style="margin-left:24px"><svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">{!! $icons['search'] !!}</svg><input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="{{ __($searchHint) }}" aria-label="Search"></form>
    @endif
    <div class="right" style="gap:8px">
      @guest
        <a class="btn sm" href="{{ route('signin') }}">{{ __('Sign in') }}</a>
        <form method="post" action="{{ route('language') }}" class="lang" aria-label="Language" style="margin-left:8px">@csrf<button type="submit" name="lang" value="en" class="{{ app()->getLocale() === 'en' ? 'on' : 'off' }}">EN</button><button type="submit" name="lang" value="ms" class="{{ app()->getLocale() === 'ms' ? 'on' : 'off' }}">BM</button></form>
      @endguest
      @auth
        <form method="post" action="{{ route('language') }}" class="lang" style="margin-right:8px" aria-label="Language">@csrf<button type="submit" name="lang" value="en" class="{{ app()->getLocale() === 'en' ? 'on' : 'off' }}" aria-pressed="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}">EN</button><button type="submit" name="lang" value="ms" class="{{ app()->getLocale() === 'ms' ? 'on' : 'off' }}" aria-pressed="{{ app()->getLocale() === 'ms' ? 'true' : 'false' }}">BM</button></form>
        @if(!$isPublicRoute)
        <a class="iconbtn" href="{{ route('help') }}" title="Help" aria-label="Help"><svg viewBox="0 0 16 16" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true"><circle cx="8" cy="8" r="6.5"/><path d="M6 6.2a2 2 0 013.9.6c0 1.3-1.9 1.6-1.9 2.8M8 11.6v.1"/></svg></a>
        <div class="anchor">
          <button type="button" class="iconbtn" data-menu="pop-notices" aria-haspopup="true" aria-expanded="false" aria-label="Notices: {{ $unread }} unread"><svg viewBox="0 0 16 16" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons['bell'] !!}</svg>@if($unread)<span class="badge">{{ $unread }}</span>@endif</button>
          <div class="pop notif" id="pop-notices" hidden>
            <div class="ph"><b>{{ __('Notices') }}</b> <span class="small muted">{{ __(':count unread', ['count' => $unread]) }}</span></div>
            @forelse($recent as $n)
              <a class="ni {{ $n->read_at ? '' : 'unread' }}" href="{{ route('notifications.open', $n->id) }}" style="color:var(--ink)"><div><div class="t">{{ $n->subject }}</div><div class="s">{{ \Carbon\Carbon::parse($n->created_at)->format('d/m/Y H:i') }}</div></div></a>
            @empty
              <div class="ni muted">{{ __('No notices yet.') }}</div>
            @endforelse
            <a class="mi" href="{{ route('notifications') }}" style="color:var(--primary)">{{ __('See all notices') }}</a>
          </div>
        </div>
        <div class="anchor">
          <button type="button" class="who-btn menu-btn" data-menu="pop-user" aria-haspopup="true" aria-expanded="false"><span class="avatar" aria-hidden="true">{{ $initials }}</span><span>{{ $u->name }}<small>{{ $roleLabel }}@if($ptjCode), {{ $ptjCode }}@endif</small></span><svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M4 6l4 4 4-4"/></svg></button>
          <div class="pop" id="pop-user" hidden style="width:280px">
            <div class="ph"><div style="font-weight:600">{{ $u->name }}</div><div class="small muted">{{ $u->email }}</div><div class="small muted">{{ $roleLabel }}@if($ptjCode), {{ $ptjCode }}@endif</div></div>
            <a class="mi" href="{{ route('profile') }}">{{ __('My profile') }}</a>
            <a class="mi" href="{{ route('profile') }}#notifications">{{ __('Notification preferences') }}</a>
            <a class="mi" href="{{ route('profile') }}#sessions">{{ __('Sign-in and sessions') }}</a>
            <a class="mi" href="{{ route('documents') }}">{{ __('My documents') }}</a>
            <a class="mi" href="{{ route('help') }}">{{ __('Help and guides') }}</a>
            <div class="sep"></div>
            <form method="post" action="{{ route('signout') }}">@csrf <button type="submit" class="mi" style="width:100%;background:none;color:var(--ink);height:auto;border:0">{{ __('Sign out') }}</button></form>
          </div>
        </div>
        @else
          <a class="btn sm" href="{{ route('tasks') }}" style="margin-left:8px">{{ __('Return to portal') }} &rarr;</a>
        @endif
      @endauth
    </div>
  </header>
  <div class="body">
    @auth
    @if(!$isPublicRoute)
    <nav class="side" aria-label="Main">
      @php
        $lecOnly = $u && $u->hasAnyRole('lecturer') && ! $u->hasAnyRole('deputy_dean', 'codl_admin');
        $ddOnly = $u && $u->hasAnyRole('deputy_dean') && ! $u->hasAnyRole('codl_admin');
        $ddLabels = ['My tasks' => 'Task queue', 'Offerings' => 'Offerings and readiness', 'Credit transfer evaluation' => 'Credit transfer', 'Traceability and records' => 'Traceability'];
        $ddOrder = ['Task queue', 'Proposals and approval', 'Offerings and readiness', 'Results and completion', 'Credentials', 'Quality Verification', 'Quality review', 'ACR and ABC deposits', 'Credit transfer', 'Stackable pathways', 'Reports', 'Traceability', 'Documents', 'Notifications'];
        $codlOnly = $u && $u->hasAnyRole('codl_admin') && ! $u->hasAnyRole('deputy_dean', 'lecturer');
        $codlGroups = ['Users and roles' => 'Administration', 'Reference data' => 'Administration', 'Settings' => 'Administration', 'Templates' => 'Administration', 'Demo clock' => 'Administration',
          'UCreds exchange' => 'Data exchange', 'UCreds content' => 'Data exchange', 'Adapter status' => 'Data exchange', 'Test mailbox' => 'Data exchange', 'Audit trail' => 'Records', 'Reports' => 'Records', 'Retention' => 'Records'];
        $codlOrder = array_merge(['My tasks'], array_keys($codlGroups), ['Documents', 'Notifications']);
        $extOnly = $u && $u->hasAnyRole('industry_expert') && ! $u->hasAnyRole('student', 'lecturer', 'deputy_dean', 'codl_admin');
        $labels = ['My proposals' => 'Proposals and versions', 'Reviews' => 'Reviews for colleagues', 'Offerings' => 'Cohorts and results', 'Quality Verification' => 'QV evidence', 'Quality review' => 'Quality review (CQI)'];
        $areaCount = fn ($a) => $ddOnly ? app(\App\Modules\Identity\TaskController::class)->visible($u)->whereNull('completed_at')->where('area', $a)->count() : 0;
        $counts = $ddOnly ? ['approvals' => $areaCount('proposals'), 'offerings' => $areaCount('offerings'), 'results' => $areaCount('results'), 'deposits' => $areaCount('deposits'), 'transfers' => $areaCount('transfers'), 'credentials.admin' => $areaCount('credentials')] : ($lecOnly ? [
          'proposals' => \Illuminate\Support\Facades\DB::table('micro_credentials as m')->join('mc_versions as v', 'v.micro_credential_id', '=', 'm.id')->where('m.owner_id', $u->id)->whereIn('v.status', ['draft', 'returned'])->count(),
          'reviews' => \Illuminate\Support\Facades\DB::table('review_assignments')->where('reviewer_id', $u->id)->whereIn('status', ['invited', 'in_progress'])->count(),
        ] : []);
        $shown = collect($nav)->filter(fn ($i) => $i[4] && ! ($extOnly && $i[2] === 'tasks'))->map(function ($i) use ($extOnly, $lecOnly, $labels, $ddOnly, $ddLabels, $codlOnly, $codlGroups) {
          if ($extOnly && $i[2] === 'reviews') { $i[1] = 'My review invitations'; $i[0] = 'Invitations'; }
          if ($codlOnly) { $i[1] = ['Adapters' => 'Adapter status', 'Notification log' => 'Test mailbox'][$i[1]] ?? $i[1]; $i[0] = $codlGroups[$i[1]] ?? $i[0]; }
          if ($ddOnly) {
            $i[1] = $ddLabels[$i[1]] ?? $i[1];
            $i[0] = in_array($i[1], ['Reports', 'Traceability and records', 'Traceability']) ? 'Insight' : (in_array($i[1], ['Documents', 'Notifications']) ? $i[0] : 'Work');
          }
          if ($lecOnly) { $i[1] = $labels[$i[1]] ?? $i[1]; if (in_array($i[1], ['Reports', 'Traceability and records', 'Traceability'])) { $i[0] = 'Insight'; } }
          return $i;
        })->when($codlOnly, fn ($c) => $c->sortBy(fn ($i) => array_search($i[1], $codlOrder) === false ? 99 : array_search($i[1], $codlOrder))->values())->when($ddOnly, fn ($c) => $c->sortBy(fn ($i) => array_search($i[1], $ddOrder) === false ? 99 : array_search($i[1], $ddOrder))->values());
      @endphp
      @foreach($shown->groupBy(0) as $group => $items)
        @if($group !== 'Work' && $group !== 'Messages')<div class="grp">{{ __($group) }}</div>@endif
        @foreach($items as $i)
          <a href="{{ isset($i[6]) ? route($i[2], $i[6]) : route($i[2]) }}" @if(request()->routeIs(...$i[3])) aria-current="page" @endif title="{{ __($i[1]) }}"><svg viewBox="0 0 16 16" aria-hidden="true">{!! $icons[$i[5]] !!}</svg><span class="lbl">{{ __($i[1]) }}</span>@if($i[1] === 'Notifications' && $unread)<span class="c">{{ $unread }}</span>@elseif($i[2] === 'tasks' && $tasksOpen)<span class="c">{{ $tasksOpen }}</span>@elseif(($counts[$i[2]] ?? 0) > 0)<span class="c">{{ $counts[$i[2]] }}</span>@endif</a>
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
