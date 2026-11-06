@extends('layouts.app')
@section('title', $person->name)
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <a href="{{ route('users') }}">{{ __('Users and roles') }}</a> /
      <span>{{ $person->name }}</span>
    </div>
    <h1>{{ $person->name }}</h1>
    <div class="sub">
      <code>{{ $person->institution_id ?? 'S'.str_pad((string)$person->id, 5, '0', STR_PAD_LEFT) }}</code> &middot;
      {{ $person->email }} &middot;
      {{ __('Member since') }} {{ $person->created_at?->format('d M Y') ?? '—' }}
    </div>
  </div>
  <div class="act">
    <span class="tag {{ $person->status === 'active' ? 'ok' : 'warn' }}" style="font-size:13px;padding:4px 10px">
      {{ $person->status === 'active' ? __('Active') : __('Deactivated') }}
    </span>
  </div>
</div>


<div class="row" style="align-items:stretch;margin-bottom:20px;gap:16px">
  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Profile from single sign-on') }}</h2>
    </div>
    <div class="bd">
      <dl>
        <dt>{{ __('Full name') }}</dt><dd><b>{{ $person->name }}</b></dd>
        <dt>{{ __('Email address') }}</dt><dd>{{ $person->email }}</dd>
        <dt>{{ __('Staff / Matric ID') }}</dt><dd><code>{{ $person->institution_id ?? 'S'.str_pad((string)$person->id, 5, '0', STR_PAD_LEFT) }}</code></dd>
        <dt>{{ __('Account status') }}</dt>
        <dd>
          <span class="tag {{ $person->status === 'active' ? 'ok' : 'warn' }}">
            {{ $person->status === 'active' ? __('Active') : __('Deactivated') }}
          </span>
        </dd>
      </dl>
      <div class="small muted" style="margin-top:12px;border-top:1px solid var(--border);padding-top:8px">
        {{ __('Read-only from SSO. Changes to staff or learner identities are made at UTeM HR or Pejabat Pendaftar and synchronize on sign-in.') }}
      </div>
    </div>
  </div>

  <div class="panel" style="width:380px;margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Separation of duties') }}</h2>
    </div>
    <div class="bd">
      <p class="small" style="line-height:1.5">
        {{ __('One person may hold several roles, including an administrator role and an academic role. MCMS checks each record independently:') }}
      </p>
      <ul class="small muted" style="padding-left:18px;line-height:1.6">
        <li>{{ __('Whoever enters results cannot verify that cohort.') }}</li>
        <li>{{ __('Nobody may review or endorse their own proposal.') }}</li>
        <li>{{ __('CODL administrators assign access; academic governance stays with the PTj deputy deans.') }}</li>
      </ul>
    </div>
  </div>
</div>

<div class="panel" style="margin-bottom:20px">
  <div class="hd">
    <h2>{{ __('Role assignments') }}</h2>
    <span class="r small muted">{{ $assignments->count() }} {{ __('assignments') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Role') }}</th>
          <th>{{ __('Scope') }}</th>
          <th>{{ __('Effective from') }}</th>
          <th>{{ __('Valid until') }}</th>
          <th>{{ __('Reason') }}</th>
          <th class="num">{{ __('Action') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($assignments as $a)
        @php($isActive = !$a->valid_to || $a->valid_to->gte(today()))
        <tr>
          <td>
            <b>{{ $a->role->label() }}</b>
          </td>
          <td>
            @if($a->scope_type === 'ptj')
              <span class="tag info">PTj: {{ $ptjs->firstWhere('id', $a->ptj_id)?->code }}</span>
            @elseif($a->scope_type === 'mc')
              <span class="tag info">Micro-credential {{ $a->scope_ref }}</span>
            @elseif($a->scope_type === 'university')
              <span class="tag ok">{{ __('Whole University') }}</span>
            @else
              <span class="tag muted">{{ __('All') }}</span>
            @endif
          </td>
          <td>{{ $a->valid_from?->format('d/m/Y') ?? __('Always') }}</td>
          <td>
            @if($a->valid_to)
              <span class="{{ $a->valid_to->lt(today()) ? 'tag warn' : '' }}">{{ $a->valid_to->format('d/m/Y') }}</span>
            @else
              <span class="muted">{{ __('Open') }}</span>
            @endif
          </td>
          <td class="small">{{ $a->reason }}</td>
          <td class="num">
            @if($isActive)
              <form method="post" action="{{ route('users.revoke', [$person, $a]) }}" data-confirm="Revoke this role?|The person loses the access this role gives. The change is recorded in the audit trail." data-danger style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                @csrf
                <input type="text" name="reason" placeholder="{{ __('Reason') }}" required minlength="5" aria-label="Reason to revoke" style="max-width:140px;height:28px;font-size:12px">
                <button type="submit" class="btn sm danger ghost">{{ __('Revoke') }}</button>
              </form>
            @else
              <span class="tag muted">{{ __('Revoked / Ended') }}</span>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="muted" style="text-align:center;padding:20px">{{ __('No staff roles assigned. Default role is Participant.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="row" style="align-items:stretch;gap:16px">
  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Assign a role') }}</h2>
    </div>
    <div class="bd">
      <form method="post" action="{{ route('users.assign', $person) }}" class="col" style="gap:12px">
        @csrf
        <div class="row" style="gap:16px">
          <div style="flex:1">
            <label for="role" style="font-weight:600">{{ __('Role') }}</label>
            <select id="role" name="role" required style="width:100%">
              @foreach($roles as $r)
                <option value="{{ $r->value }}">{{ $r->label() }}</option>
              @endforeach
            </select>
          </div>
          <div style="flex:1">
            <label for="scope_type" style="font-weight:600">{{ __('Scope') }}</label>
            <select id="scope_type" name="scope_type" required style="width:100%">
              <option value="university">{{ __('Whole University') }}</option>
              <option value="ptj">{{ __('One PTj') }}</option>
              <option value="mc">{{ __('Specific micro-credentials') }}</option>
            </select>
          </div>
        </div>

        <div class="row" style="gap:16px">
          <div style="flex:1">
            <label for="ptj_id" style="font-weight:500">{{ __('PTj (when the scope is one PTj)') }}</label>
            <select id="ptj_id" name="ptj_id" style="width:100%">
              <option value="">-</option>
              @foreach($ptjs as $p)
                <option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}</option>
              @endforeach
            </select>
          </div>
          <div style="flex:1">
            <label for="scope_ref" style="font-weight:500">{{ __('Micro-credential code (when specific)') }}</label>
            <input type="text" id="scope_ref" name="scope_ref" placeholder="e.g. LE or CSO" style="width:100%">
          </div>
        </div>

        <div class="row" style="gap:16px">
          <div style="flex:1">
            <label for="valid_from" style="font-weight:600">{{ __('Effective date') }}</label>
            <input type="date" id="valid_from" name="valid_from" value="{{ today()->toDateString() }}" required style="width:100%">
          </div>
          <div style="flex:2">
            <label for="reason" style="font-weight:600">{{ __('Reason') }}</label>
            <input type="text" id="reason" name="reason" placeholder="{{ __('Reason recorded in audit trail') }}" required minlength="5" style="width:100%">
          </div>
        </div>

        <div style="margin-top:8px">
          <button type="submit" class="btn">{{ __('Assign role') }}</button>
        </div>
      </form>
    </div>
  </div>

  <div class="panel" style="width:380px;margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Account governance') }}</h2>
    </div>
    <div class="bd">
      <form method="post" action="{{ route('users.status', [$person, $person->status === 'active' ? 'inactive' : 'active']) }}" class="col" style="gap:12px">
        @csrf
        <p class="small muted" style="margin:0">
          {{ __('Deactivating stops sign-in and active roles. Historical records, verified results, and credentials are preserved.') }}
        </p>
        <div>
          <label for="areason" style="font-weight:600">{{ __('Reason') }}</label>
          <input type="text" id="areason" name="reason" required minlength="5" placeholder="{{ __('Reason for status change') }}" style="width:100%">
        </div>
        <div>
          <button type="submit" class="btn {{ $person->status === 'active' ? 'danger' : 'ter' }}">
            {{ $person->status === 'active' ? __('Deactivate account') : __('Reactivate account') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
