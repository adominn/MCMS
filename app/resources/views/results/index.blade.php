@extends('layouts.app')
@section('title', __('Results and completion'))
@section('content')
<div class="title"><div><h1>{{ __('Results and completion') }}</h1><div class="sub">{{ __('Imported results stay provisional until you verify them. Completion is confirmed separately.') }}</div></div></div>
<div class="panel"><table>
  <thead><tr><th>{{ __('Cohort') }}</th><th>{{ __('Status') }}</th><th>{{ __('Submitted') }}</th><th>{{ __('Provisional') }}</th><th>{{ __('Held') }}</th><th>{{ __('Verified') }}</th><th></th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    @php($s = ($stats[$r->id] ?? collect())->pluck('n', 'status'))
    <tr><td><a href="{{ route('results.verify', $r->id) }}" style="font-weight:600">{{ $r->name_en }} {{ $r->cohort_name }}</a></td><td><x-pill :s="__(config('offerings.status')[$r->status] ?? $r->status)" /></td><td>{{ $r->results_submitted_at ? \Carbon\Carbon::parse($r->results_submitted_at)->format('d/m/Y') : __('Not yet') }}</td><td>{{ $s['provisional'] ?? 0 }}</td><td>{{ $s['held'] ?? 0 }}</td><td>{{ $s['verified'] ?? 0 }}</td><td class="num"><a class="btn ter sm" href="{{ route('completion.show', $r->id) }}">{{ __('Completion') }} &rarr;</a></td></tr>
  @empty
    <tr><td colspan="7" class="muted" style="text-align:center;padding:24px">{{ __('No running or ended cohorts.') }}</td></tr>
  @endforelse
  </tbody>
</table></div>
@endsection
