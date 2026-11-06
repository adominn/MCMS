@extends($print ? 'layouts.print' : 'layouts.app')
@section('title', __('Audit trail'))
@section('doc_title', __('Audit trail export'))
@section('doc_ref', 'AUD-'.now()->format('Ymd'))
@section('doc_file', 'audit-'.now()->format('Y-m').'.pdf')
@section('doc_actions')
  <a class="btn ter sm" href="{{ route('records.audit.csv', ['q' => $q]) }}">{{ __('Download .csv') }}</a>
@endsection
@section('content')

@if(!$print)
  <div class="ph">
    <div>
      <div class="crumb">
        <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
        <a href="{{ route('trace') }}">{{ __('Traceability') }}</a> /
        <span>{{ __('Audit trail') }}</span>
      </div>
      <h1>{{ __('Audit trail') }}</h1>
      <div class="sub">{{ __('Immutable SHA-256 hash-chained log of administrative and academic events in your role scope.') }}</div>
    </div>
    <div class="act" style="display:flex;gap:8px">
      <a href="{{ route('records.audit.print', ['q' => $q]) }}" class="btn ter sm" target="_blank">{{ __('Print or export') }}</a>
      <a href="{{ route('trace') }}" class="btn ghost sm">&larr; {{ __('Traceability') }}</a>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="bd">
      <form method="get" action="{{ route('records.audit') }}" role="search" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="field" style="flex:1;min-width:280px">
          <label for="q">{{ __('Search by action or record') }}</label>
          <input id="q" name="q" type="search" value="{{ $q }}" placeholder="e.g. transfer, proposal, enrolment...">
        </div>
        <button type="submit" class="btn sm">{{ __('Search') }}</button>
        @if($q)
          <a href="{{ route('records.audit') }}" class="btn ghost sm">{{ __('Clear') }}</a>
        @endif
      </form>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="hd">
      <h2>{{ __('Audit ledger entries') }}</h2>
      <span class="r small muted">{{ count($events) }} {{ __('events') }}</span>
    </div>
    <div class="table-wrap">
      <table id="audit">
        <thead>
          <tr>
            <th style="width:70px">{{ __("Seq") }}</th>
            <th style="width:140px">{{ __("Time") }}</th>
            <th>{{ __("Action") }}</th>
            <th>{{ __("Record") }}</th>
            <th>{{ __("By") }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($events as $a)
          <tr>
            <td><code>#{{ $a->seq }}</code></td>
            <td class="small">{{ \Carbon\Carbon::parse($a->occurred_at)->format('d/m/Y H:i') }}</td>
            <td>
              <b>{{ $a->action }}</b>
              @if($a->reason)
                <div class="small muted" style="margin-top:2px">{{ $a->reason }}</div>
              @endif
            </td>
            <td><code>{{ $a->subject_type }} {{ $a->subject_id }}</code></td>
            <td>{{ $a->actor ?? 'System' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __("No entry in your scope.") }}</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <p class="muted small">{{ __("Entries for records inside your scope. Entries are never changed or removed.") }}</p>

@else
  {{-- Printable Layout --}}
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">{{ __("Audit trail export") }}</h1>
    <div class="small muted">
      Entries for records inside your scope. Cryptographically hash-chained &middot; Generated on {{ now()->format('d/m/Y H:i') }}
    </div>
  </div>

  <div class="note ok" style="margin-bottom:16px">
    <p id="integrity" style="margin:0"><strong>{{ __("Integrity:") }}</strong> {{ $intact ? 'The hash chain was checked at '.$generated.' and is intact.' : 'The hash chain check found a break.' }}</p>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="table-wrap">
      <table id="audit">
        <thead>
          <tr>
            <th style="width:60px">{{ __("Seq") }}</th>
            <th style="width:130px">{{ __("Time") }}</th>
            <th>{{ __("Action") }}</th>
            <th>{{ __("Record") }}</th>
            <th>{{ __("By") }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($events as $a)
          <tr>
            <td><code>#{{ $a->seq }}</code></td>
            <td class="small">{{ \Carbon\Carbon::parse($a->occurred_at)->format('d/m/Y H:i') }}</td>
            <td>
              <b>{{ $a->action }}</b>
              @if($a->reason)
                <div class="small muted">{{ $a->reason }}</div>
              @endif
            </td>
            <td><code>{{ $a->subject_type }} {{ $a->subject_id }}</code></td>
            <td>{{ $a->actor ?? 'System' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="muted" style="text-align:center;padding:20px">{{ __("No entry in your scope.") }}</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <p class="muted small">{{ __("Entries for records inside your scope. Entries are never changed or removed.") }}</p>
@endif

@endsection
