@extends('layouts.app')
@section('title', __('My profile'))
@section('content')
@php
  $cleanName = preg_replace('/^(Dr\.|Ir\.|Prof\.|Prof\.\s*Madya\s*Dr\.|Ts\.)\s+/iu', '', $u->name);
  $initials = collect(explode(' ', trim((string) $cleanName)))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
  if (empty($initials)) {
      $initials = mb_substr($u->name, 0, 2);
  }
  $homeRoute = route('tasks');
@endphp
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ $homeRoute }}">{{ __('Home') }}</a>
  <span>/</span>
  <span>{{ __('My profile') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('My profile') }}</h1>
    <div class="sub">{{ $u->name }} &middot; {{ $post }}</div>
  </div>
</div>

<div data-tabs>
  <nav class="tabs" aria-label="Profile sections">
    <button data-tab="details">{{ __('Details') }}</button>
    <button data-tab="notif">{{ __('Notifications') }}</button>
    <button data-tab="security">{{ __('Sign-in and sessions') }}</button>
    <button data-tab="data">{{ __('My personal data') }}</button>
  </nav>

  {{-- Details Tab --}}
  <div data-pane="details">
    <div class="row" style="display:flex;gap:20px;align-items:flex-start">
      <div class="panel grow" style="flex:1">
        <div class="bd col">
          <div class="row" style="display:flex;gap:16px;align-items:center">
            <span class="avatar" style="width:56px;height:56px;border-radius:28px;font-size:18px;display:inline-flex;align-items:center;justify-content:center;background:#0f62fe;color:#fff;font-weight:600">{{ $initials }}</span>
            <div>
              <div style="font-size:20px;line-height:28px">{{ $u->name }}</div>
              <div class="muted">{{ $post }}</div>
            </div>
          </div>
          <dl class="dl" style="margin-top:20px">
            <dt>{{ __('Email') }}</dt>
            <dd>{{ $u->email }}</dd>
            <dt>{{ $staff ? __('Staff number') : __('Learner ID') }}</dt>
            <dd>{{ $u->institution_id ?: ('ID-'.$u->id) }}</dd>
            <dt>{{ __('Roles') }}</dt>
            <dd>{{ $roleLabel }}<span class="sr">{{ __('My roles') }}</span></dd>
            <dt>{{ __('Source') }}</dt>
            <dd>{{ __('UTeM Microsoft account. Change these at UTeM HR or Pejabat Pendaftar.') }}</dd>
          </dl>
        </div>
      </div>

      <div class="panel" style="width:420px;flex-shrink:0">
        <div class="hd"><h2>{{ __('Preferences') }}</h2></div>
        <div class="bd col">
          <form method="post" action="{{ route('profile.preferences') }}" class="col" style="gap:16px">
            @csrf
            <div class="field">
              <label for="pref-locale">{{ __('Language for screens, email and certificates') }}</label>
              <select id="pref-locale" name="locale" class="input" style="width:100%">
                <option value="en" @selected(app()->getLocale() === 'en')>{{ __('English') }}</option>
                <option value="ms" @selected(app()->getLocale() === 'ms')>{{ __('Bahasa Melayu') }}</option>
              </select>
            </div>
            <div class="field">
              <label for="pref-phone">{{ __('Contact phone') }}</label>
              <input type="text" id="pref-phone" name="phone" class="input" value="{{ old('phone', $u->phone) }}" placeholder="012-345 6789">
              <div class="help small muted" style="margin-top:4px;font-size:12px;color:var(--muted)">{{ __('Used only for urgent notices about your records') }}</div>
            </div>
            <div class="field">
              <label for="pref-cname">{{ __('Name on certificates') }}</label>
              <input type="text" id="pref-cname" name="name_on_certificate" class="input" value="{{ old('name_on_certificate', $u->name_on_certificate ?: $u->name) }}">
              <div class="help small muted" style="margin-top:4px;font-size:12px;color:var(--muted)">{{ __('As in your UTeM record, including bin, binti, a/l or a/p. Ask for a correction if it is wrong.') }}</div>
            </div>
            <button type="submit" class="btn" style="align-self:flex-start">{{ __('Save preferences') }}</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Notifications Tab --}}
  <div data-pane="notif">
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Email notifications') }}</h2>
        <span class="r">{{ __('Required notices cannot be switched off') }}</span>
      </div>
      <div class="bd col">
        <form method="post" action="{{ route('notifications.prefs') }}">
          @csrf
          <div class="col" style="gap:0">
            <div class="check" style="display:flex;align-items:center;padding:12px 0;border-bottom:1px solid var(--hairline)">
              <div class="grow" style="flex:1">
                <div>{{ __('Decisions on my records') }}</div>
                <span class="small muted" style="display:block;font-size:12px;color:var(--muted)">{{ __('Always sent') }}</span>
              </div>
              <span class="tag">{{ __('Required') }}</span>
            </div>

            <div class="check" style="display:flex;align-items:center;padding:12px 0;border-bottom:1px solid var(--hairline)">
              <div class="grow" style="flex:1">
                <div>{{ __('Credential issued, corrected or revoked') }}</div>
                <span class="small muted" style="display:block;font-size:12px;color:var(--muted)">{{ __('Always sent') }}</span>
              </div>
              <span class="tag">{{ __('Required') }}</span>
            </div>

            <div class="check" style="display:flex;align-items:center;padding:12px 0;border-bottom:1px solid var(--hairline)">
              <div class="grow" style="flex:1">
                <div>{{ __('Consent requests') }}</div>
                <span class="small muted" style="display:block;font-size:12px;color:var(--muted)">{{ __('Always sent') }}</span>
              </div>
              <span class="tag">{{ __('Required') }}</span>
            </div>

            <div class="check" style="display:flex;align-items:center;padding:12px 0;border-bottom:1px solid var(--hairline)">
              <div class="grow" style="flex:1">
                <div>{{ __('Enrolment changes') }}</div>
                <span class="small muted" style="display:block;font-size:12px;color:var(--muted)">{{ __('Always sent') }}</span>
              </div>
              <span class="tag">{{ __('Required') }}</span>
            </div>

            @if($staff)
              <div class="check" style="display:flex;align-items:center;padding:12px 0;border-bottom:1px solid var(--hairline)">
                <div class="grow" style="flex:1">
                  <div>{{ __('Tasks assigned to me') }}</div>
                  <span class="small muted" style="display:block;font-size:12px;color:var(--muted)">{{ __('In the system and by email') }}</span>
                </div>
                <label class="choice" style="border:0;padding:0;margin:0;display:inline-flex;align-items:center;gap:6px">
                  <input type="checkbox" name="tasks" value="1" @checked($tasksOn)> {{ __('On') }}
                </label>
              </div>

              <div class="check" style="display:flex;align-items:center;padding:12px 0;border-bottom:1px solid var(--hairline)">
                <div class="grow" style="flex:1">
                  <div>{{ __('Reminders for overdue tasks') }}</div>
                  <span class="small muted" style="display:block;font-size:12px;color:var(--muted)">{{ __('After 14 days') }}</span>
                </div>
                <label class="choice" style="border:0;padding:0;margin:0;display:inline-flex;align-items:center;gap:6px">
                  <input type="checkbox" name="reminders" value="1" @checked($remindersOn)> {{ __('On') }}
                </label>
              </div>
            @endif

            @if($u->hasAnyRole('lecturer', 'deputy_dean'))
              <div class="check" style="display:flex;align-items:center;padding:12px 0;border-bottom:1px solid var(--hairline)">
                <div class="grow" style="flex:1">
                  <div>{{ __('Daily digest instead of single emails') }}</div>
                  <span class="small muted" style="display:block;font-size:12px;color:var(--muted)">{{ __('One email at 08:00') }}</span>
                </div>
                <label class="choice" style="border:0;padding:0;margin:0;display:inline-flex;align-items:center;gap:6px">
                  <input type="checkbox" name="digest" value="1" @checked($digestOn)> {{ __('On') }}
                </label>
              </div>
            @endif
          </div>

          <button type="submit" class="btn" style="margin-top:20px;align-self:flex-start">{{ __('Save') }}</button>
        </form>
      </div>
    </div>
  </div>

  {{-- Sign-in & Sessions Tab --}}
  <div data-pane="security">
    <div class="row" style="display:flex;gap:20px;align-items:flex-start">
      <div class="panel" style="flex:1">
        <div class="hd"><h2>{{ __('Sign-in') }}</h2></div>
        <div class="bd col">
          <dl class="dl">
            <dt>{{ __('Method') }}</dt>
            <dd>{{ __('UTeM Microsoft account (single sign-on)') }}</dd>
            <dt>{{ __('Second step') }}</dt>
            <dd>{{ $staff ? __('Required for your role · Microsoft Authenticator') : __('Not required for participants') }}</dd>
            <dt>{{ __('Idle sign-out') }}</dt>
            <dd>{{ __('After :min minutes without activity', ['min' => $idle]) }}</dd>
          </dl>
          <button type="button" class="btn ter" data-modal="timeout" style="margin-top:16px;align-self:flex-start">{{ __('Preview the idle warning') }}</button>
        </div>
      </div>

      <div class="panel grow" style="flex:1">
        <div class="hd"><h2>{{ __('Active sessions') }}</h2></div>
        <table id="sessions">
          <thead>
            <tr>
              <th>{{ __('Device') }}</th>
              <th>{{ __('Last active') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($sessions as $s)
              <tr>
                <td>{{ $s->device }}</td>
                <td>{{ $s->last_active }}</td>
                <td style="text-align:right">
                  @if($s->is_current)
                    <span class="muted">{{ __('This session') }}</span>
                  @else
                    <form method="post" action="{{ route('profile.sessions.terminate') }}" style="display:inline">
                      @csrf
                      <input type="hidden" name="device" value="{{ $s->device }}">
                      <button type="submit" class="btn ghost sm" data-confirm="Sign out of {{ $s->device }}?|That device must sign in again." data-label="Sign out device">{{ __('Sign out') }}</button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- My Personal Data Tab --}}
  <div data-pane="data">
    <div class="panel">
      <div class="hd"><h2>{{ __('Personal data we hold') }}</h2></div>
      <div class="bd col">
        <dl class="dl">
          <dt>{{ __('Full name') }}</dt>
          <dd>{{ $u->name }}</dd>
          <dt>{{ __('Email') }}</dt>
          <dd>{{ $u->email }}</dd>
          <dt>{{ __('Phone') }}</dt>
          <dd>{{ $u->phone ?: '012-345 6789' }}</dd>
          <dt>{{ __('Identity document') }}</dt>
          <dd>{{ __('Held encrypted; shown to you only on request') }}</dd>
          <dt>{{ __('Used for') }}</dt>
          <dd>{{ __('Enrolment, results, credentials and credit records (PDPA notice accepted 02/08/2025)') }}</dd>
        </dl>
        <div class="row" style="display:flex;gap:8px;margin-top:20px;align-items:center">
          <button type="button" class="btn ter" data-modal="datacorrect">{{ __('Request a correction') }}</button>
          @if($u->hasRole('student'))
            <a class="btn ghost" href="{{ route('records.mine') }}">{{ __('See my achievement record') }}</a>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal: datacorrect --}}
<div id="modal-datacorrect" class="scrim" style="display:none" hidden>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-dc-title">
    <div class="mh">
      <div>
        <div class="k">{{ __('My personal data') }}</div>
        <h2 id="modal-dc-title">{{ __('Request a correction to your personal data') }}</h2>
      </div>
      <button type="button" class="iconbtn x" data-close aria-label="{{ __('Close') }}" style="border:0;background:none;cursor:pointer;font-size:20px">&times;</button>
    </div>
    <form method="post" action="{{ route('profile.data_correction') }}" class="mb" style="margin:0">
      @csrf
      <div class="field" style="margin-bottom:16px">
        <label for="dc-what">{{ __('What is wrong') }}</label>
        <select id="dc-what" name="what" class="input" style="width:100%" required>
          <option value="Name spelling">{{ __('Name spelling') }}</option>
          <option value="Email">{{ __('Email') }}</option>
          <option value="Phone">{{ __('Phone') }}</option>
          <option value="Other">{{ __('Other') }}</option>
        </select>
      </div>
      <div class="field" style="margin-bottom:16px">
        <label for="dc-should">{{ __('What it should be') }}</label>
        <input type="text" id="dc-should" name="should" class="input" style="width:100%" required>
      </div>
      <div class="small muted" style="font-size:12px;color:var(--muted);line-height:18px;margin-bottom:16px">
        {{ __('Pejabat Pendaftar checks the request against your documents. Credentials already issued are corrected as a new revision, never overwritten.') }}
      </div>
      <div class="mf" style="display:flex;border-top:1px solid var(--hairline)">
        <button type="button" class="btn sec" data-close style="flex:1">{{ __('Cancel') }}</button>
        <button type="submit" class="btn" style="flex:1">{{ __('Send request') }}</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: timeout --}}
<div id="modal-timeout" class="scrim" style="display:none" hidden>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-to-title">
    <div class="mh">
      <div>
        <div class="k">{{ __('Idle session') }}</div>
        <h2 id="modal-to-title">{{ __('You will be signed out in 2 minutes') }}</h2>
      </div>
      <button type="button" class="iconbtn x" data-close aria-label="{{ __('Close') }}" style="border:0;background:none;cursor:pointer;font-size:20px">&times;</button>
    </div>
    <div class="mb col">
      <p style="margin:0 0 16px">{{ __('You have not used MCMS for :min minutes. Unsaved changes on this screen will be lost when you are signed out.', ['min' => max(1, $idle - 2)]) }}</p>
      <form method="post" action="{{ route('signout') }}" style="margin:0">
        @csrf
        <button type="submit" class="btn ter" style="align-self:flex-start">{{ __('Sign out now') }}</button>
      </form>
    </div>
    <div class="mf" style="display:flex;border-top:1px solid var(--hairline)">
      <button type="button" class="btn" data-close onclick="window.mcmsToast && window.mcmsToast('Session extended', 'Idle sign-out is in {{ $idle }} minutes.', 'info')" style="flex:1">{{ __('Stay signed in') }}</button>
    </div>
  </div>
</div>
@endsection
