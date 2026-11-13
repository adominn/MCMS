@extends('layouts.app')
@section('title', 'Cohort records')
@section('content')
<div class="ph">
  <div>
    <x-breadcrumb :crumbs="[
      'Traceability ledger' => route('trace'),
      $o->name_en . ' (' . $o->cohort_name . ')' => null,
    ]" />
    <h1>{{ __('Minimum records: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
    <div class="sub">{{ __('The required governance records for a cohort, and whether each is present.') }}</div>
  </div>
  <div class="act">
    <a href="{{ route('trace') }}" class="btn ghost sm">&larr; {{ __('Back to traceability') }}</a>
  </div>
</div>
<table id="cohort-records">
  <thead><tr><th>{{ __('Record') }}</th><th>{{ __('Present') }}</th><th>{{ __('Detail') }}</th></tr></thead>
  <tbody>@foreach($items as $i)<tr><td>{{ $i['label'] }}</td><td>{{ $i['present'] ? __('Yes') : __('No') }}</td><td>{{ $i['detail'] }}</td></tr>@endforeach</tbody>
</table>
@endsection
