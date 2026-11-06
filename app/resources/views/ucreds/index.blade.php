@extends('layouts.app')
@section('title', 'UCreds exchange')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <span>{{ __('UCreds exchange') }}</span>
    </div>
    <h1>{{ __('UCreds exchange') }}</h1>
    <div class="sub">{{ __('The learning platform connection is manual in Release 1: MCMS exports the enrolment list and imports result and badge files. Template version v3.') }}</div>
  </div>
  <div class="act">
    <a href="{{ route('ucreds.courses') }}" class="btn ghost sm">{{ __('UCreds content catalogue') }} &rarr;</a>
  </div>
</div>

<div class="kpis">
  <div class="kpi">
    <div class="l">{{ __('Active cohorts') }}</div>
    <div class="v">{{ $offerings->count() }}</div>
    <div class="s">{{ __('Ready for export/import') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Imports') }}</div>
    <div class="v">{{ $imports->count() }}</div>
    <div class="s">{{ __('Results & badges files') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Exports') }}</div>
    <div class="v">{{ $exports->count() }}</div>
    <div class="s">{{ __('Enrolment files') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Template format') }}</div>
    <div class="v">v3</div>
    <div class="s">{{ __('Current standard') }}</div>
  </div>
</div>

<div class="row" style="align-items:stretch;margin-bottom:24px;gap:20px">
  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Export enrolments') }}</h2>
      <span class="tag muted">CSV download</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ __('Cohort') }}</th>
            <th>{{ __('Status') }}</th>
            <th class="num">{{ __('Download') }}</th>
          </tr>
        </thead>
        <tbody>
        @forelse($offerings as $o)
          <tr>
            <td><b>{{ $o->name_en }} {{ $o->cohort_name }}</b></td>
            <td><span class="tag info">{{ config('offerings.status')[$o->status] ?? $o->status }}</span></td>
            <td class="num">
              <a href="{{ route('ucreds.export.manifest', $o->id) }}" target="_blank" class="btn sm ghost">{{ __('Manifest') }}</a>
              <a href="{{ route('ucreds.export', $o->id) }}" class="btn sm ter">Download enrol-O-{{ str_pad((string) $o->id, 4, '0', STR_PAD_LEFT) }}.csv</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="muted" style="text-align:center;padding:20px">{{ __('No cohorts ready for export.') }}</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Import a results or badge file') }}</h2>
      <span class="tag info">{{ __('Validation check') }}</span>
    </div>
    <div class="bd">
      <form method="post" action="{{ route('ucreds.check') }}" enctype="multipart/form-data" class="col" style="gap:12px">
        @csrf
        <div>
          <label for="offering_id" style="font-weight:600">{{ __('Cohort') }}</label>
          <select id="offering_id" name="offering_id" required style="width:100%">
            @foreach($offerings as $o)
              <option value="{{ $o->id }}">{{ $o->name_en }} {{ $o->cohort_name }} ({{ config('offerings.status')[$o->status] ?? $o->status }})</option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="kind" style="font-weight:600">{{ __('File') }}</label>
          <select id="kind" name="kind" required style="width:100%">
            <option value="results">{{ __('Results (template results v3)') }}</option>
            <option value="badges">{{ __('Badges (template badges v3)') }}</option>
          </select>
        </div>

        <div>
          <label for="file" style="font-weight:600">{{ __('CSV file') }}</label>
          <input type="file" id="file" name="file" accept=".csv,text/csv" required style="width:100%">
        </div>

        <div style="margin-top:8px">
          <button type="submit" class="btn">{{ __('Check file') }}</button>
        </div>
      </form>
    </div>
    <div class="foot">
      <span class="small muted">{{ __('Checking changes nothing. Staged results are Provisional: no achievement, badge or credential exists until the deputy dean verifies.') }}</span>
    </div>
  </div>
</div>

<div class="panel" style="margin-bottom:20px">
  <div class="hd">
    <h2>{{ __('Import history') }}</h2>
    <span class="r small muted">{{ $imports->count() }} {{ __('files') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('No.') }}</th>
          <th>{{ __('When') }}</th>
          <th>{{ __('Cohort') }}</th>
          <th>{{ __('File & SHA-256') }}</th>
          <th>{{ __('Rows') }}</th>
          <th>{{ __('Staged') }}</th>
          <th>{{ __('Rejected') }}</th>
          <th>{{ __('Status') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($imports as $i)
        <tr>
          <td><a href="{{ route('ucreds.report', $i->id) }}" class="btn sm ghost">#{{ $i->id }}</a></td>
          <td>{{ \Carbon\Carbon::parse($i->created_at)->format('d/m/Y H:i') }}</td>
          <td><b>{{ $i->name_en }} {{ $i->cohort_name }}</b></td>
          <td>
            {{ $i->original_name }} <span class="tag muted">{{ $i->kind }}</span>
            <div style="margin-top:2px"><code>{{ substr($i->sha256, 0, 12) }}...</code></div>
          </td>
          <td>{{ $i->row_count }}</td>
          <td><span class="tag ok">{{ $i->staged_count }}</span></td>
          <td>
            @if($i->rejected_count > 0)
              <span class="tag warn">{{ $i->rejected_count }}</span>
            @else
              0
            @endif
          </td>
          <td>
            <span class="tag {{ in_array($i->status, ['accepted', 'partly_accepted']) ? 'ok' : ($i->status === 'rejected' ? 'warn' : 'info') }}">
              {{ ['checked' => 'Checked', 'rejected' => 'Rejected', 'accepted' => 'Accepted', 'partly_accepted' => 'Partly accepted'][$i->status] ?? $i->status }}
            </span>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="muted" style="text-align:center;padding:24px">{{ __('No imports yet.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Export history') }}</h2>
    <span class="r small muted">{{ $exports->count() }} {{ __('files') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('When') }}</th>
          <th>{{ __('Cohort') }}</th>
          <th>{{ __('Rows') }}</th>
          <th>{{ __('SHA-256 checksum') }}</th>
          <th>{{ __('Exported by') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($exports as $e)
        <tr>
          <td>{{ \Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i') }}</td>
          <td><b>{{ $e->name_en }} {{ $e->cohort_name }}</b></td>
          <td>{{ $e->row_count }}</td>
          <td><code>{{ substr($e->sha256, 0, 12) }}...</code></td>
          <td>{{ $e->by }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No exports yet.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
