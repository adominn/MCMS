@extends('layouts.app')
@section('title', 'ACR and ABC deposits')
@section('content')
@php
  $canWrite = auth()->user()->hasAnyRole('abc_depositor', 'records_depositor');
@endphp
<div class="title">
  <div>
    <h1>{{ __('ACR Confirmation & Academic Bank of Credit (ABC) Deposits') }}</h1>
    <div class="sub">
      @if($canWrite)
        {{ __('Registry / ABC Depositor Workspace · Review and confirm generated Academic Credit Records (ACRs), export consented batches, and record ABC deposits.') }}
      @else
        {{ __('Read-Only ACR & ABC Oversight · Browse student Academic Credit Records, learner consent progress, and ABC deposit status.') }}
      @endif
    </div>
  </div>
</div>

@if(!$canWrite)
  <div class="note info" style="margin-bottom:16px">
    <p style="margin:0">
      <strong>{{ __('Read-Only Oversight View:') }}</strong>
      {{ __('ACR confirmation, batch export, and ABC deposit recording are performed by the Authorized Records & ABC Depositor Officer.') }}
    </p>
  </div>
@endif

<div data-tabs data-auto>
<nav class="tabs" aria-label="Deposit stages">
  <button data-tab="to-confirm">{{ __('To confirm') }} <span class="muted">{{ $confirm->count() }}</span></button>
  <button data-tab="queue">{{ __('Deposit queue') }} <span class="muted">{{ $queue->count() }}</span></button>
  <button data-tab="waiting">{{ __('Waiting for consent') }} <span class="muted">{{ $waiting->count() }}</span></button>
  <button data-tab="deposited">{{ __('Deposited') }} <span class="muted">{{ $done->count() }}</span></button>
</nav>

{{-- TAB 1: To confirm (with Bulk Confirm for P6/P7) --}}
<div data-pane="to-confirm" @if(!$confirm->count()) data-empty @endif>
<div class="panel">
  <div class="hd">
    <h2>{{ __('Academic Credit Records to confirm') }}</h2>
    <span class="r"><span class="tag info">{{ $confirm->count() }} {{ $confirm->count() === 1 ? __('record') : __('records') }}</span></span>
  </div>
  @if($canWrite && $confirm->count())
    <form method="post" action="{{ route('deposits.confirm.bulk') }}" id="bulk-confirm-form">
      @csrf
      <div style="padding:12px 16px;background:var(--color-canvas-subtle, #f8fafc);border-bottom:1px solid var(--color-card-border, #090a0f);display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
        <div style="flex:1;min-width:220px;display:inline-flex;flex-direction:column;gap:2px">
          <label for="bulk-confirm-notes" class="small muted">{{ __('Optional confirmation note (applied to selected)') }}</label>
          <input id="bulk-confirm-notes" name="notes" type="text" placeholder="{{ __('e.g. Cohort ACR verification complete') }}" style="width:100%;min-height:34px;padding:4px 8px;font-size:12.5px">
        </div>
        <div>
          <button type="submit" class="btn primary sm" id="bulk-confirm-btn" disabled>{{ __('Confirm selected (0)') }}</button>
        </div>
      </div>
  @endif
  <table id="to-confirm">
    <thead>
      <tr>
        @if($canWrite && $confirm->count())
          <th style="width:38px"><input type="checkbox" id="confirm-select-all" aria-label="{{ __('Select all to confirm') }}"></th>
        @endif
        <th>{{ __('Record') }}</th>
        <th>{{ __('Participant') }}</th>
        <th style="text-align:right">{{ __('Credits') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @forelse($confirm as $r)
      <tr>
        @if($canWrite && $confirm->count())
          <td><input type="checkbox" class="confirm-chk" name="acrs[]" value="{{ $r->id }}" aria-label="Confirm {{ $r->reference }}"></td>
        @endif
        <td>
          <a href="{{ route('deposits.show', $r->id) }}" style="font-weight:600">{{ $r->name_en }}</a>
          <br><span class="muted small"><code>{{ $r->reference }}</code> &middot; {{ strtoupper($r->category) }}</span>
        </td>
        <td>{{ $r->user_name }}<div class="s muted"><code>{{ $r->institution_id }}</code></div></td>
        <td style="text-align:right;font-weight:600">{{ mcms_num($r->credits) }}</td>
        <td style="text-align:right">
          <a class="btn ter sm" href="{{ route('deposits.show', $r->id) }}">{{ $canWrite ? __('Review') : __('Inspect') }}</a>
        </td>
      </tr>
    @empty
      <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('Nothing to confirm.') }}</td></tr>
    @endforelse
    </tbody>
  </table>
  @if($canWrite && $confirm->count())
    </form>
    <script>
    (function () {
      var all = document.getElementById('confirm-select-all');
      var btn = document.getElementById('bulk-confirm-btn');
      var chks = Array.prototype.slice.call(document.querySelectorAll('.confirm-chk'));
      if (!btn) return;
      function sync() {
        var n = chks.filter(function (c) { return c.checked; }).length;
        btn.disabled = n === 0;
        btn.textContent = '{{ __("Confirm selected") }} (' + n + ')';
        if (all) all.checked = chks.length > 0 && n === chks.length;
      }
      if (all) all.addEventListener('change', function () { chks.forEach(function (c) { c.checked = all.checked; }); sync(); });
      chks.forEach(function (c) { c.addEventListener('change', sync); });
      sync();
    })();
    </script>
  @endif
</div>
</div>

{{-- TAB 2: Deposit queue (CSV Export + Bulk Record ABC Deposit) --}}
<div data-pane="queue" @if(!$queue->count()) data-empty @endif>
<div class="note" style="margin-bottom:14px">{{ __('Consented and confirmed records only. Only confirmed Academic Credit Records with explicit learner consent appear in the deposit queue.') }}</div>
<form method="post" action="{{ route('deposits.export') }}" id="queue-action-form">
@csrf
<div class="panel">
  <div class="hd">
    <h2>{{ __('Queue for Academic Bank of Credit deposit') }}</h2>
    <span class="r"><span class="tag ok">{{ $queue->count() }} {{ __('ready') }}</span></span>
  </div>
  @if($canWrite && $queue->count())
    <div style="padding:12px 16px;background:var(--color-canvas-subtle, #f8fafc);border-bottom:1px solid var(--color-card-border, #090a0f);display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
      <div style="display:inline-flex;flex-direction:column;gap:2px;flex:1;min-width:170px">
        <label for="bulk-abc-prefix" class="small muted">{{ __('ABC reference / batch prefix') }}</label>
        <input id="bulk-abc-prefix" name="abc_prefix" type="text" placeholder="{{ __('e.g. ABC-2026-FTMK') }}" style="width:100%;min-height:34px;padding:4px 8px;font-size:12.5px">
      </div>
      <div style="display:inline-flex;flex-direction:column;gap:2px">
        <label for="bulk-dep-date" class="small muted">{{ __('Deposit date') }}</label>
        <input id="bulk-dep-date" name="deposit_date" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" style="width:142px;min-height:34px;padding:4px 8px;font-size:12.5px">
      </div>
      <div style="display:inline-flex;flex-direction:column;gap:2px">
        <label for="bulk-dep-status" class="small muted">{{ __('Deposit status') }}</label>
        <select id="bulk-dep-status" name="deposit_status" style="min-height:34px;padding:4px 8px;font-size:12.5px">
          <option value="successful">{{ __('Successful (Available 10 yrs)') }}</option>
          <option value="in_process">{{ __('In process') }}</option>
          <option value="correction">{{ __('Needs correction') }}</option>
        </select>
      </div>
      <div style="display:inline-flex;gap:8px;flex-wrap:wrap">
        <button type="submit" formaction="{{ route('deposits.record.bulk') }}" class="btn primary sm" id="bulk-record-btn" disabled>{{ __('Record deposit (0)') }}</button>
        <button type="submit" formaction="{{ route('deposits.export') }}" class="btn ter sm" id="bulk-export-btn" disabled>{{ __('Export CSV (0)') }}</button>
      </div>
    </div>
  @endif
  <table id="queue">
    <thead>
      <tr>
        @if($canWrite)
          <th style="width:40px"><input type="checkbox" id="check-all" aria-label="{{ __('Select all') }}"></th>
        @endif
        <th>{{ __('Record') }}</th>
        <th>{{ __('Participant') }}</th>
        <th style="text-align:right">{{ __('Credits') }}</th>
        <th>{{ __('Status') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @forelse($queue as $r)
      <tr>
        @if($canWrite)
          <td><input type="checkbox" class="queue-chk" name="acrs[]" value="{{ $r->id }}" aria-label="Select {{ $r->reference }}"></td>
        @endif
        <td>
          <a href="{{ route('deposits.show', $r->id) }}" style="font-weight:600">{{ $r->name_en }}</a>
          <br><span class="muted small"><code>{{ $r->reference }}</code></span>
        </td>
        <td>{{ $r->user_name }}<div class="s muted"><code>{{ $r->institution_id }}</code></div></td>
        <td style="text-align:right;font-weight:600">{{ mcms_num($r->credits) }}</td>
        <td><x-pill :s="__($acrStatus[$r->status] ?? $r->status)" /></td>
        <td style="text-align:right">
          <a class="btn ter sm" href="{{ route('deposits.show', $r->id) }}">{{ $canWrite ? __('Record deposit') : __('Inspect') }}</a>
        </td>
      </tr>
    @empty
      <tr><td colspan="{{ $canWrite ? 6 : 5 }}" class="muted" style="text-align:center;padding:24px">{{ __('No consented record is waiting for deposit.') }}</td></tr>
    @endforelse
    </tbody>
  </table>
  @if($canWrite && $queue->count())
    <div class="ft" style="padding:12px 16px;background:var(--bg);border-top:1px solid var(--border);display:flex;justify-content:flex-end">
      <button type="submit" formaction="{{ route('deposits.export') }}" class="btn ter sm">{{ __('Export selected (CSV)') }}</button>
    </div>
  @endif
</div>
</form>
@if($canWrite && $queue->count())
<script>
(function () {
  var all = document.getElementById('check-all');
  var recBtn = document.getElementById('bulk-record-btn');
  var expBtn = document.getElementById('bulk-export-btn');
  var chks = Array.prototype.slice.call(document.querySelectorAll('.queue-chk'));
  function sync() {
    var n = chks.filter(function (c) { return c.checked; }).length;
    if (recBtn) { recBtn.disabled = n === 0; recBtn.textContent = '{{ __("Record deposit") }} (' + n + ')'; }
    if (expBtn) { expBtn.disabled = n === 0; expBtn.textContent = '{{ __("Export CSV") }} (' + n + ')'; }
    if (all) all.checked = chks.length > 0 && n === chks.length;
  }
  if (all) all.addEventListener('change', function () { chks.forEach(function (c) { c.checked = all.checked; }); sync(); });
  chks.forEach(function (c) { c.addEventListener('change', sync); });
  sync();
})();
</script>
@endif
</div>

<div data-pane="waiting" @if(!$waiting->count()) data-empty @endif>
<div class="panel">
  <div class="hd">
    <h2>{{ __('Confirmed records awaiting learner consent') }}</h2>
    <span class="r"><span class="tag info">{{ $waiting->count() }} {{ __('waiting') }}</span></span>
  </div>
  <table id="waiting">
    <thead>
      <tr>
        <th>{{ __('Record') }}</th>
        <th>{{ __('Participant') }}</th>
        <th>{{ __('Status') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @forelse($waiting as $r)
      <tr>
        <td>
          <a href="{{ route('deposits.show', $r->id) }}" style="font-weight:600">{{ $r->name_en }}</a>
          <br><span class="muted small"><code>{{ $r->reference }}</code></span>
        </td>
        <td>{{ $r->user_name }}</td>
        <td><x-pill :s="__($acrStatus[$r->status] ?? $r->status)" /></td>
        <td style="text-align:right"><a class="btn ter sm" href="{{ route('deposits.show', $r->id) }}">{{ __('Inspect') }}</a></td>
      </tr>
    @empty
      <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('None.') }}</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
</div>

<div data-pane="deposited" @if(!$done->count()) data-empty @endif>
<div class="panel">
  <div class="hd">
    <h2>{{ __('Deposited in Academic Bank of Credit') }}</h2>
    <span class="r"><span class="tag ok">{{ $done->count() }} {{ __('deposited') }}</span></span>
  </div>
  <table id="deposited">
    <thead>
      <tr>
        <th>{{ __('Record') }}</th>
        <th>{{ __('Participant') }}</th>
        <th>{{ __('ABC reference') }}</th>
        <th>{{ __('Deposit date') }}</th>
        <th>{{ __('Expiry') }}</th>
        <th>{{ __('Credit') }}</th>
        <th style="text-align:right">{{ __('Action') }}</th>
      </tr>
    </thead>
    <tbody>
    @forelse($done as $r)
      <tr>
        <td>
          <a href="{{ route('deposits.show', $r->id) }}" style="font-weight:600">{{ $r->name_en }}</a>
          <br><span class="muted small"><code>{{ $r->reference }}</code></span>
        </td>
        <td>{{ $r->user_name }}</td>
        <td><code>{{ $r->abc_reference }}</code></td>
        <td>{{ $r->deposit_date ? \Carbon\Carbon::parse($r->deposit_date)->format('d/m/Y') : 'Not yet recorded' }}</td>
        <td>{{ $r->credit_expiry ? \Carbon\Carbon::parse($r->credit_expiry)->format('d/m/Y') : 'Not yet recorded' }}</td>
        <td><x-pill :s="$creditStatus[$r->credit_status] ?? ''" /></td>
        <td style="text-align:right"><a class="btn ter sm" href="{{ route('deposits.show', $r->id) }}">{{ __('Open ACR') }}</a></td>
      </tr>
    @empty
      <tr><td colspan="7" class="muted" style="text-align:center;padding:24px">{{ __('None.') }}</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
</div>
</div>
@endsection
