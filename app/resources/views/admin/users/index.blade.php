@extends('layouts.app')
@section('title', 'Users and roles')
@section('content')
<div class="ph">
  <div>
    <div class="crumb"><a href="{{ route('tasks') }}">{{ __('Home') }}</a> / <span>{{ __('Users and roles') }}</span></div>
    <h1>{{ __('Users and roles') }}</h1>
    <div class="sub">{{ $users->total() }} {{ __('accounts from UTeM single sign-on. Accounts are never deleted; deactivating keeps every record.') }}</div>
  </div>
</div>

<div class="kpis">
  <div class="kpi">
    <div class="l">{{ __('Active accounts') }}</div>
    <div class="v">{{ $users->where('status', 'active')->count() }}</div>
    <div class="s">{{ __('Able to sign in') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Deactivated') }}</div>
    <div class="v">{{ $users->where('status', '!=', 'active')->count() }}</div>
    <div class="s">{{ __('Preserved in audit') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Total accounts') }}</div>
    <div class="v">{{ $users->total() }}</div>
    <div class="s">{{ __('Synchronized from SSO') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Governance') }}</div>
    <div class="v">D-12</div>
    <div class="s">{{ __('Separation of duties') }}</div>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Accounts and roles') }}</h2>
    <span class="r small muted">{{ $users->total() }} {{ __('registered') }}</span>
  </div>
  <div class="bd">
    <form method="get" action="{{ route('users') }}" role="search" class="toolbar" style="gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
      <label for="q" style="font-weight:500;margin:0">{{ __('Search by name or email') }}</label>
      <input type="search" id="q" name="q" value="{{ $q }}" placeholder="{{ __('Search by name, email or matric...') }}" style="max-width:320px;height:36px">
      <button type="submit" class="btn ter sm">{{ __('Search') }}</button>
      @if($q !== '')
        <a href="{{ route('users') }}" class="btn ghost sm">{{ __('Clear') }}</a>
      @endif
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Staff / matric no.') }}</th>
            <th>{{ __('Roles') }}</th>
            <th>{{ __('Email') }}</th>
            <th>{{ __('Status') }}</th>
            <th class="num">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($users as $u)
          <tr>
            <td>
              <a href="{{ route('users.show', $u) }}"><b>{{ $u->name }}</b></a>
            </td>
            <td><code>{{ $u->institution_id ?? 'S'.str_pad((string)$u->id, 5, '0', STR_PAD_LEFT) }}</code></td>
            <td>
              @php($roles = $u->activeRoles())
              @if($roles->isNotEmpty())
                @foreach($roles as $r)
                  <span class="tag info">{{ $r->label() }}</span>
                @endforeach
              @else
                <span class="tag muted">{{ __('Participant') }}</span>
              @endif
            </td>
            <td>{{ $u->email }}</td>
            <td>
              <span class="tag {{ $u->status === 'active' ? 'ok' : 'warn' }}">
                {{ $u->status === 'active' ? __('Active') : __('Deactivated') }}
              </span>
            </td>
            <td class="num">
              <a href="{{ route('users.show', $u) }}" class="btn sm ter">{{ __('Manage') }}</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="muted" style="text-align:center;padding:24px">{{ __('No account matches these filters.') }}</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    @if($users->hasPages())
      {{ $users->links('pagination.simple') }}
    @endif
  </div>
</div>

<div class="note" style="margin-top:16px">
  <div>
    <b>{{ __('Separation of responsibilities (D-12)') }}:</b>
    {{ __('CODL assigns who can act. It does not make academic decisions: the deputy dean approves, verifies and confirms; roles listed here only decide who may see and do those things.') }}
  </div>
</div>
@endsection
