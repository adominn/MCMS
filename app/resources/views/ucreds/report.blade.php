@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', 'Import validation report')
@section('doc_title', 'UCreds Import Validation Report')
@section('doc_ref', 'IMP-' . strtoupper(substr(hash('sha256', $f->original_name), 0, 6)))
@section('doc_file', 'validation-' . $f->original_name . '.pdf')
@section('doc_actions')
  <a class="btn ter sm" href="{{ route('ucreds.errors', $f->id) }}">Download .csv</a>
@endsection
@section('content')

@php
  $isPrint = request()->boolean('print') || request()->has('print');
@endphp

@if(!$isPrint)
  <div class="ph">
    <div>
      <div class="crumb">
        <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
        <a href="{{ route('ucreds') }}">{{ __('UCreds exchange') }}</a> /
        <span>{{ __('Import report') }} #{{ $f->id }}</span>
      </div>
      <h1>{{ __('Import report') }}</h1>
      <div class="sub">
        <b>{{ $f->original_name }}</b> &middot;
        {{ ucfirst($f->kind) }} &middot;
        {{ $f->name_en }} {{ $f->cohort_name }} &middot;
        <code>{{ substr($f->sha256, 0, 16) }}...</code>
      </div>
    </div>
    <div class="act" style="display:flex;gap:8px">
      <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="btn ter sm">{{ __('Print report (A4)') }}</a>
      <a href="{{ route('ucreds') }}" class="btn ghost sm">&larr; {{ __('Back to exchange') }}</a>
    </div>
  </div>

  @if($f->file_error)
    <div class="note err" style="margin-bottom:16px">
      <div>
        <b>{{ __('The whole file is rejected') }}:</b>
        {{ $f->file_error }} {{ __('No rows were staged. Fix the template format and upload the file again.') }}
      </div>
    </div>
  @else
    <div class="kpis">
      <div class="kpi">
        <div class="l">{{ __('Total rows read') }}</div>
        <div class="v">{{ $f->row_count }}</div>
        <div class="s">{{ __('In source CSV') }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Ready to stage') }}</div>
        <div class="v ok">{{ $ready }}</div>
        <div class="s">{{ __('Passed validation') }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Rejected rows') }}</div>
        <div class="v {{ $f->rejected_count > 0 ? 'warn' : '' }}">{{ $f->rejected_count }}</div>
        <div class="s">{{ __('Errors flagged') }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Import status') }}</div>
        <div class="v">
          {{ ['checked' => 'Checked', 'rejected' => 'Rejected', 'accepted' => 'Accepted', 'partly_accepted' => 'Partly accepted'][$f->status] ?? $f->status }}
        </div>
        <div class="s">{{ __('Staging state') }}</div>
      </div>
    </div>

    <div class="panel" style="margin-bottom:20px">
      <div class="hd">
        <h2>{{ __('Validation summary') }}</h2>
        <span class="r tag {{ $ready > 0 ? 'ok' : 'warn' }}">{{ $ready > 0 ? __('Validation passed') : __('Action required') }}</span>
      </div>
      <div class="bd">
        <p style="font-size:15px;line-height:1.5">
          <b>{{ $ready }} rows ready</b>, <b>{{ $f->rejected_count }} rejected</b> of {{ $f->row_count }}.
        </p>
        <p class="small muted" style="margin-bottom:16px">
          @if($f->status === 'checked')
            {{ __('Staging hands the :ready good rows to results as Provisional. Rejected rows stay out and must be corrected on the cohort page.', ['ready' => $ready]) }}
          @elseif(in_array($f->status, ['accepted', 'partly_accepted']))
            {{ __('Staged :count rows on :time.', ['count' => $f->staged_count, 'time' => \Carbon\Carbon::parse($f->staged_at)->format('d/m/Y H:i')]) }}
          @endif
        </p>
        @if($f->status === 'checked' && $ready > 0)
          <form method="post" action="{{ route('ucreds.stage', $f->id) }}">
            @csrf
            <button type="submit" class="btn">{{ __('Stage :count rows', ['count' => $ready]) }}</button>
          </form>
        @endif
      </div>
    </div>
  @endif

  <div class="panel">
    <div class="hd">
      <h2>{{ __('Rejected rows') }}</h2>
      <span class="r small muted">{{ $rowErrs->count() }} {{ __('problem(s)') }}</span>
    </div>
    @if($rowErrs->isEmpty())
      <div class="bd">
        <p class="muted" style="margin:0">{{ __('None. Every row in the uploaded file passed format, score range, and participant matching.') }}</p>
      </div>
    @else
      <div class="bd" style="padding-bottom:8px">
        <a href="{{ route('ucreds.errors', $f->id) }}" class="btn sm ter">{{ __('Download the rejected rows (CSV)') }}</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:80px">{{ __('Line') }}</th>
              <th style="width:160px">{{ __('Learner ID') }}</th>
              <th>{{ __('Why rejected') }}</th>
            </tr>
          </thead>
          <tbody>
          @foreach($rowErrs as $e)
            <tr>
              <td><code>{{ $e->line }}</code></td>
              <td><b>{{ $e->learner_id }}</b></td>
              <td style="color:var(--err)">{{ $e->message }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <div class="foot">
        <span class="small muted">{{ __('Rejected rows: the offering lecturer is notified which marks are missing and enters them directly on the cohort marks workspace.') }}</span>
      </div>
    @endif
  </div>

@else
  {{-- Printable A4 Sheet --}}
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">{{ __("Import Validation Report") }}</h1>
    <div class="small muted">
      File: {{ $f->original_name }} &middot; Reference: IMP-{{ strtoupper(substr(hash('sha256', $f->original_name), 0, 6)) }} &middot; {{ now()->format('d M Y, H:i') }}
    </div>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="bd" style="padding:12px 16px">
      <dl class="dl" style="font-size:10pt;row-gap:6px">
        <dt style="font-size:9pt">{{ __('File') }}</dt>
        <dd><span class="mono">{{ $f->original_name }}</span></dd>

        <dt style="font-size:9pt">{{ __('Direction') }}</dt>
        <dd>Import</dd>

        <dt style="font-size:9pt">{{ __('Cohort') }}</dt>
        <dd><b>{{ $f->name_en }} {{ $f->cohort_name }}</b></dd>

        <dt style="font-size:9pt">{{ __('Received') }}</dt>
        <dd>{{ \Carbon\Carbon::parse($f->created_at)->format('d M Y, H:i') }}</dd>

        <dt style="font-size:9pt">{{ __('File hash (SHA-256)') }}</dt>
        <dd><span class="mono" style="font-size:8pt">{{ $f->sha256 }}</span></dd>

        <dt style="font-size:9pt">{{ __('Rows in file') }}</dt>
        <dd>{{ $f->row_count }}</dd>

        <dt style="font-size:9pt">{{ __('Result') }}</dt>
        <dd><span class="tag {{ in_array($f->status, ['accepted', 'partly_accepted']) ? 'ok' : 'info' }}">{{ ['checked' => 'Checked', 'rejected' => 'Rejected', 'accepted' => 'Accepted', 'partly_accepted' => 'Partly accepted'][$f->status] ?? ucfirst($f->status) }}</span></dd>
      </dl>
    </div>
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="hd"><h2 style="font-size:11pt">{{ __("Row counts") }}</h2></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ __("Outcome") }}</th>
            <th class="num">{{ __("Rows") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ __("Staged as Provisional") }}</td>
            <td class="num">{{ $ready }}</td>
          </tr>
          <tr>
            <td>{{ __("Rejected") }}</td>
            <td class="num">{{ $f->rejected_count }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr style="font-weight:600;border-top:2px solid var(--border)">
            <td>{{ __("Total rows") }}</td>
            <td class="num">{{ $f->row_count }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <div class="note ok" style="margin-bottom:16px;font-size:9.5pt">
    <b>{{ __("Provisional status note:") }}</b> {{ $ready }} rows are stored as <b>{{ __("Provisional") }}</b>. No achievement, badge or credential is created from a provisional result, and nobody is told a result is final until the deputy dean verifies it.
  </div>

  <div class="panel" style="margin-bottom:16px">
    <div class="hd"><h2 style="font-size:11pt">{{ __("Rejected rows") }}</h2></div>
    @if($rowErrs->isNotEmpty())
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:60px">{{ __("Row") }}</th>
              <th>{{ __("Record") }}</th>
              <th>{{ __("Reason") }}</th>
              <th>{{ __("Next step") }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rowErrs as $e)
              <tr>
                <td><code>{{ $e->line }}</code></td>
                <td><b>{{ $e->learner_id }}</b></td>
                <td><span style="color:var(--err)">{{ $e->message }}</span></td>
                <td class="small muted">{{ __("Not stored. The lecturer is told which mark is missing.") }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="bd" style="padding:12px 16px;font-size:9.5pt;color:var(--muted)">{{ __("No rows were rejected.") }}</div>
    @endif
  </div>

  <div style="font-size:9pt;color:var(--muted)">
    Prepared for CODL administrators. Marks are not shown on this report.
  </div>

@endif
@endsection
