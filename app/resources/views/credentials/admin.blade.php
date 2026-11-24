@extends('layouts.app')
@section('title', 'Credentials')
@section('content')
@php
  $canManageCreds = auth()->user()->hasAnyRole('records_custodian', 'records_depositor');
@endphp
<div class="title">
  <div>
    <h1>{{ __('Credentials') }}</h1>
    <div class="sub">
      @if($canManageCreds)
        {{ __('Credentials are issued by the system when completion is confirmed and every check passes. Correct, replace, retry, or revoke an issued credential here.') }}
      @else
        {{ __('Read-only overview of issued credentials for your faculty. Issuance retries, corrections, replacements, and revocations are handled by the Records & Credential Custodian.') }}
      @endif
    </div>
  </div>
</div>

@if($failures->isNotEmpty())
  <div class="panel" style="margin-bottom:24px">
    <div class="hd">
      <h2>{{ __('Not issued') }}</h2>
      <span class="r"><span class="tag err">{{ $failures->count() }}</span></span>
    </div>
    <div class="bd" style="padding:0">
      <table>
        <thead>
          <tr>
            <th style="width:24%">{{ __('Participant') }}</th>
            <th style="width:28%">{{ __('Cohort') }}</th>
            <th style="width:32%">{{ __('Why') }}</th>
            <th style="width:16%;text-align:right"></th>
          </tr>
        </thead>
        <tbody>
        @foreach($failures as $f)
          <tr>
            <td><b>{{ $f->name }}</b></td>
            <td>{{ $f->name_en }} {{ $f->cohort_name }}</td>
            <td>
              <ul style="margin:0;padding-left:18px;font-size:13px">
                @foreach(json_decode($f->reasons, true) as $r)
                  <li>{{ $r }}</li>
                @endforeach
              </ul>
            </td>
            <td style="text-align:right">
              @if($canManageCreds)
                <form method="post" action="{{ route('credentials.retry', $f->enrolment_id) }}">
                  @csrf
                  <button type="submit" class="btn sm">{{ __('Check again') }}</button>
                </form>
              @else
                <span class="s muted">{{ __('Handled by Records & Credential Custodian') }}</span>
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

<div class="panel">
  <div class="hd"><h2>{{ __('Issued credentials') }}</h2></div>
  <div class="bd" style="padding:0">
    <table>
      <thead>
        <tr>
          <th style="width:22%">{{ __('Number') }}</th>
          <th style="width:25%">{{ __('Recipient') }}</th>
          <th style="width:28%">{{ __('Credential') }}</th>
          <th style="width:13%">{{ __('Issued') }}</th>
          <th style="width:12%">{{ __('Status') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        @php($x = json_decode($r->content, true))
        <tr>
          <td>
            <a href="{{ route('credentials.admin.show', $r->id) }}" style="font-weight:600">
              {{ $r->number }}
            </a>
          </td>
          <td><b>{{ $x['holder'] }}</b></td>
          <td>
            <div class="row" style="gap:6px;align-items:center">
              <x-cat :c="$x['category'] ?? ''" />
              <span>{{ $x['name']['en'] ?? 'Credential' }}</span>
            </div>
            @if(!empty($r->lecturer_name))
              <span class="s muted" style="display:block;margin-top:2px">{{ $r->lecturer_name }}</span>
            @endif
          </td>
          <td>{{ \Carbon\Carbon::parse($r->issued_at)->format('d/m/Y') }}</td>
          <td>
            <x-pill :s="__(['valid' => 'Valid', 'corrected' => 'Corrected', 'replaced' => 'Replaced', 'revoked' => 'Revoked'][$r->status] ?? ucfirst($r->status))" />
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted" style="text-align:center;padding:36px">{{ __('No credentials issued yet.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
