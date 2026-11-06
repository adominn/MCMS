@extends('layouts.app')
@section('title', 'Cohort records')
@section('content')
<h1>Minimum records: {{ $o->name_en }} {{ $o->cohort_name }}</h1>
<p><a href="{{ route('trace') }}">Traceability</a></p>
<p class="muted">{{ __('The required governance records for a cohort, and whether each is present.') }}</p>
<table id="cohort-records">
  <thead><tr><th>{{ __('Record') }}</th><th>{{ __('Present') }}</th><th>{{ __('Detail') }}</th></tr></thead>
  <tbody>@foreach($items as $i)<tr><td>{{ $i['label'] }}</td><td>{{ $i['present'] ? __('Yes') : __('No') }}</td><td>{{ $i['detail'] }}</td></tr>@endforeach</tbody>
</table>
@endsection
