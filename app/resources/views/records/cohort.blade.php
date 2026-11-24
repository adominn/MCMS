@extends('layouts.app')
@section('title', 'Cohort records')
@section('content')
<div class="ph">
  <div>
    <x-breadcrumb :crumbs="[
      'Traceability ledger' => route('trace'),
      $o->name_en . ' (' . $o->cohort_name . ')' => null,
    ]" />
    <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#0052ff;margin-bottom:2px">
      {{ __('Minimum Cohort Records Checklist') }}
    </div>
    <h1>{{ __('Minimum records: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
    <div class="sub">{{ __('The 10 mandatory governance artefacts required for each cohort, their storage location, owner, and presence.') }}</div>
  </div>
  <div class="act-bar">
    <div class="act-left">
      <a href="{{ route('trace') }}" class="btn ghost sm">&larr; {{ __('Back to traceability') }}</a>
    </div>
    <div class="act-right">
      <button type="button" onclick="window.print()" class="btn ghost sm">{{ __('Print checklist (A4)') }}</button>
    </div>
  </div>
</div>

<div class="panel">
  <div class="table-wrap">
    <table id="cohort-records">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>{{ __('Cohort Record Artefact') }}</th>
          <th>{{ __('Location / Schema') }}</th>
          <th>{{ __('Owner / Custodian') }}</th>
          <th style="width:110px">{{ __('Status') }}</th>
          <th>{{ __('Observed Details') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $i)
          <tr>
            <td style="color:#475569;font-weight:600">{{ $i['seq'] ?? $loop->iteration }}</td>
            <td>
              <div style="font-weight:600;color:#090a0f">{{ app()->getLocale() === 'ms' && !empty($i['label_ms']) ? $i['label_ms'] : __($i['label']) }}</div>
            </td>
            <td>
              <code>{{ $i['location'] ?? 'system' }}</code>
            </td>
            <td>
              <span class="tag info" style="font-size:11px">{{ $i['owner'] ?? 'P1 / P2' }}</span>
            </td>
            <td>
              @if($i['present'])
                <span class="tag ok">{{ __('Present') }}</span>
              @else
                <span class="tag warn">{{ __('Missing') }}</span>
              @endif
            </td>
            <td>{{ $i['detail'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="foot" style="background:#f1f5f9;padding:12px 16px;border-top:1px solid #e2e8f0;font-size:12px;color:#475569">
    <b>{{ __('Important Notice:') }}</b> {{ __('Internal achievement records, digital credential/badge issuance, ACR preparation, and credit deposit into ABC are four distinct governance stages that must never be conflated. Every cohort must retain these 10 minimum records for quality review and compliance audit.') }}
  </div>
</div>
@endsection
