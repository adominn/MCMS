@extends('layouts.print')
@section('title', __('ABC deposit export'))
@section('doc_title', __('ABC deposit export'))
@section('doc_bm', 'Eksport Deposit ABC')
@section('doc_ref', $b->reference)
@section('doc_file', 'abc-export-'.$b->reference.'.pdf')
@section('doc_actions')
  <a class="btn ter sm" href="{{ route('deposits.batch.csv', $b->id) }}">{{ __('Download .csv') }}</a>
@endsection
@section('content')

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <h1 style="font-size:18pt;margin:0 0 4px">{{ __('Academic Bank of Credit Batch Export') }}</h1>
  <div class="small muted">
    {{ __('Batch') }} <b>{{ $b->reference }}</b> &middot; <b>{{ $ptj->code }} &ndash; {{ $ptj->name }}</b> &middot;
    {{ __('Format') }} {{ $b->format_version }} &middot;
    {{ __('Prepared on') }} {{ \Carbon\Carbon::parse($b->created_at)->format('d/m/Y H:i') }}
  </div>
</div>

<div class="note ok" style="margin-bottom:16px;font-size:9.5pt">
  <div>
    <strong>{{ __('A deposit is storage of a credit record only. It is not a credit transfer.') }}</strong>
    {{ __('ABC storage retains these Academic Credit Records for participant recognition and future credit transfer applications.') }}
  </div>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Batch reference') }}</dt>
      <dd><b>{{ $b->reference }}</b></dd>

      <dt style="font-size:9pt">{{ __('Faculty / PTj') }}</dt>
      <dd>{{ $ptj->code }} &ndash; {{ $ptj->name }}</dd>

      <dt style="font-size:9pt">{{ __('Prepared on') }}</dt>
      <dd>{{ \Carbon\Carbon::parse($b->created_at)->format('d M Y, H:i') }}</dd>

      <dt style="font-size:9pt">{{ __('ACR in batch') }}</dt>
      <dd><b>{{ $rows->count() }}</b> {{ __('learners') }}</dd>

      <dt style="font-size:9pt">{{ __('Credits in batch') }}</dt>
      <dd><b>{{ rtrim(rtrim((string) $rows->sum('credits'), '0'), '.') }}</b> {{ __('credits') }}</dd>
    </dl>
  </div>
</div>

<div class="panel" style="margin-bottom:20px">
  <div class="hd">
    <h2>{{ __('Consented Academic Credit Records in batch') }}</h2>
    <span class="r small muted">{{ $rows->count() }} {{ __('record(s)') }}</span>
  </div>
  <div class="table-wrap">
    <table id="batch">
      <thead>
        <tr>
          <th>{{ __('ACR') }}</th>
          <th>{{ __('Credential') }}</th>
          <th class="num" style="width:90px">{{ __('Credits') }}</th>
          <th class="num" style="width:90px">{{ __('MQF level') }}</th>
          <th class="num" style="width:120px">{{ __('Completed') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach($rows as $r)
        <tr>
          <td><code>{{ $r->reference }}</code></td>
          <td><b>{{ $r->name_en }}</b></td>
          <td class="num">{{ rtrim(rtrim((string) $r->credits, '0'), '.') }}</td>
          <td class="num">{{ $r->mqf_level ?? '-' }}</td>
          <td class="num">{{ \Carbon\Carbon::parse($r->completion_date)->format('d/m/Y') }}</td>
        </tr>
      @endforeach
      </tbody>
      <tfoot>
        <tr style="font-weight:600;background:var(--surface)">
          <th colspan="2">{{ __('Total') }}</th>
          <th class="num">{{ rtrim(rtrim((string) $rows->sum('credits'), '0'), '.') }}</th>
          <td colspan="2" class="num">{{ $rows->count() }} {{ __('record(s)') }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<p class="muted small">{{ __('No identity number or email appears on this document.') }}</p>

<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2>{{ __('Format and checks') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <div class="note warn" style="margin-bottom:12px;font-size:9.5pt">
      <b>{{ __('File format not final.') }}</b> {{ __('The ABC has not confirmed its import format. The CSV download uses the columns shown here and will be adjusted when the ABC confirms it.') }}
    </div>
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Consent') }}</dt>
      <dd>{{ __('Every row has a consent date. Only ACR with consent given are deposited; nothing is deposited without the participant\'s consent.') }}</dd>

      <dt style="font-size:9pt">{{ __('Storage, not transfer') }}</dt>
      <dd>{{ __('Deposit stores the credit in ABC for 10 years. It does not transfer credit.') }}</dd>

      <dt style="font-size:9pt">{{ __('Personal data') }}</dt>
      <dd>{{ __('Learner ID only in machine data. Names, IC numbers, emails and marks are not in this file.') }}</dd>

      <dt style="font-size:9pt">{{ __('Next step') }}</dt>
      <dd>{{ __('Record the ABC reference number on the deposit screen when ABC returns it.') }}</dd>
    </dl>
  </div>
</div>

<div style="display:flex;gap:40px;margin-top:auto;padding-top:20px;border-top:1px solid var(--border)">
  <div style="flex:1;text-align:center">
    <div style="height:36px;border-bottom:1px solid var(--text)"></div>
    <div style="font-weight:600;margin-top:4px">Deputy Dean (Academic), {{ $ptj->code }}</div>
    <div class="bm small">Timbalan Dekan (Akademik)</div>
  </div>
  <div style="flex:1;text-align:center">
    <div style="height:36px;border-bottom:1px solid var(--text)"></div>
    <div style="font-weight:600;margin-top:4px">{{ now()->format('d/m/Y') }}</div>
    <div class="bm small">Tarikh</div>
  </div>
</div>

@endsection
