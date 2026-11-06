@extends($print ? 'layouts.print' : 'layouts.app')
@section('title', $r['title'])
@section('doc_title', 'Report: '.$r['title'])
@section('doc_ref', 'RPT-'.strtoupper($r['kind']).'-'.now()->format('Ymd'))
@section('doc_file', 'report-'.$r['kind'].'-'.now()->format('Ymd').'.pdf')
@section('doc_actions')
  <a class="btn ter sm" href="{{ route('reports.csv', $r['kind']) }}">{{ __('Download .csv') }}</a>
@endsection
@section('content')

@if(!$print)
  <div class="ph">
    <div>
      <div class="crumb">
        <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
        <a href="{{ route('reports') }}">{{ __('Reports') }}</a> /
        <span>{{ $r['title'] }}</span>
      </div>
      <h1>{{ $r['title'] }}</h1>
      <div class="sub">Scope: {{ $r['scope_label'] }} &middot; Generated {{ $r['generated'] }}</div>
    </div>
    <div class="act" style="display:flex;gap:8px">
      <a href="{{ route('reports.print', $r['kind']) }}" class="btn ter sm" target="_blank">{{ __('Print') }}</a>
      <a href="{{ route('reports.csv', $r['kind']) }}" class="btn ghost sm">{{ __('CSV') }}</a>
      <a href="{{ route('reports') }}" class="btn ghost sm">&larr; {{ __('Reports') }}</a>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="bd">
      <p id="summary" style="margin:0"><strong>{{ __("Summary:") }}</strong> {{ collect($r['summary'])->map(fn ($v, $k) => "$k $v")->implode(', ') }}</p>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="hd">
      <h2>{{ $r['title'] }}</h2>
      <span class="r small muted">{{ count($r['rows']) }} {{ __('record(s)') }}</span>
    </div>
    <div class="table-wrap">
      <table id="report">
        <thead>
          <tr>
            @foreach($r['columns'] as $c)
              <th>{{ $c }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
        @forelse($r['rows'] as $row)
          <tr>
            @foreach($row as $v)
              <td>{{ $v }}</td>
            @endforeach
          </tr>
        @empty
          <tr>
            <td colspan="{{ max(1, count($r['columns'])) }}" class="muted" style="text-align:center;padding:24px">{{ __("No rows in your scope.") }}</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <p class="muted small">{{ __("Method: figures are read from the records of MCMS at the time shown. A participant appears by reference only.") }}</p>

@else
  {{-- Printable Layout --}}
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">{{ $r['title'] }}</h1>
    <div class="small muted">
      Scope: <b>{{ $r['scope_label'] }}</b> &middot; Generated: {{ $r['generated'] }}
    </div>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="bd">
      <p id="summary" style="margin:0"><strong>{{ __("Summary:") }}</strong> {{ collect($r['summary'])->map(fn ($v, $k) => "$k $v")->implode(', ') }}</p>
    </div>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="table-wrap">
      <table id="report">
        <thead>
          <tr>
            @foreach($r['columns'] as $c)
              <th>{{ $c }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
        @forelse($r['rows'] as $row)
          <tr>
            @foreach($row as $v)
              <td>{{ $v }}</td>
            @endforeach
          </tr>
        @empty
          <tr>
            <td colspan="{{ max(1, count($r['columns'])) }}" class="muted" style="text-align:center;padding:20px">{{ __("No rows in your scope.") }}</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <p class="muted small">{{ __("Method: figures are read from the records of MCMS at the time shown. A participant appears by reference only.") }}</p>
@endif

@endsection
