@extends('layouts.print')
@section('title', 'UCreds enrolment export')
@section('doc_title', 'UCreds Enrolment Export')
@section('doc_ref', 'EXP-' . $ref)
@section('doc_file', 'enrol-' . $ref . '.csv')
@section('doc_actions')
  <a class="btn ter sm" href="{{ route('ucreds.export', $o->id) }}">Download .csv</a>
@endsection
@section('content')

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <h1 style="font-size:18pt;margin:0 0 4px">{{ __("UCreds Enrolment Export Manifest") }}</h1>
  <div class="small muted">
    Cohort: {{ $o->cohort_name }} &middot; Reference: EXP-{{ $ref }} &middot; {{ now()->format('d M Y, H:i') }}
  </div>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Cohort') }}</dt>
      <dd><b>{{ $o->cohort_name }}</b></dd>

      <dt style="font-size:9pt">{{ __('Micro-credential') }}</dt>
      <dd><b>{{ $o->name_en }}</b></dd>

      <dt style="font-size:9pt">{{ __('Template') }}</dt>
      <dd><span class="mono">{{ $template }}</span></dd>

      <dt style="font-size:9pt">{{ __('Format') }}</dt>
      <dd>CSV, UTF-8, CRLF, header required</dd>

      <dt style="font-size:9pt">{{ __('Generated') }}</dt>
      <dd>{{ now()->format('d M Y, H:i T') }}</dd>

      <dt style="font-size:9pt">{{ __('Enrolled rows') }}</dt>
      <dd><b>{{ count($rows) }}</b> {{ __('participants') }}</dd>

      <dt style="font-size:9pt">{{ __('Export file') }}</dt>
      <dd><code class="mono">enrol-{{ $ref }}.csv</code></dd>
    </dl>
  </div>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __("Columns in export file") }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __("Column") }}</th>
          <th>{{ __("Description") }}</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><code class="mono">learner_id</code></td>
          <td>Matric number or official learner ID in MCMS</td>
        </tr>
        <tr>
          <td><code class="mono">full_name</code></td>
          <td>Official name from the learner identity</td>
        </tr>
        <tr>
          <td><code class="mono">enrolment_id</code></td>
          <td>Registration reference (E-YYYY-NNNN)</td>
        </tr>
        <tr>
          <td><code class="mono">offering</code></td>
          <td>Offering code for UCreds ({{ $ref }})</td>
        </tr>
        <tr>
          <td><code class="mono">cohort</code></td>
          <td>Cohort identifier ({{ $o->cohort_name }})</td>
        </tr>
        <tr>
          <td><code class="mono">start_date</code></td>
          <td>Start date of the offering (YYYY-MM-DD: {{ substr((string)$o->start_date, 0, 10) }})</td>
        </tr>
        <tr>
          <td><code class="mono">end_date</code></td>
          <td>End date of the offering (YYYY-MM-DD: {{ substr((string)$o->end_date, 0, 10) }})</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="note ok" style="margin-bottom:16px;font-size:9.5pt">
  <b>{{ __("Enrolment validation policy:") }}</b> This export is prepared for CODL administrators to upload into the UCreds learning platform. Only enrolled learners who have paid or had payment waived are included. Withdrawn or pending learners are omitted.
</div>

<div style="font-size:9pt;color:var(--muted)">
  Prepared for CODL administrators. Upload directly to UCreds batch enrolment facility.
</div>

@endsection
