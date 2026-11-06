@php($nAch = collect($d['modules'])->filter(fn ($m) => $m->achieved)->count())
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><span class="tag">{{ __('Internal record, not an Academic Credit Record') }}</span></div>
  <div class="bd col">
  <h2>{{ $d['e']->name_en }} {{ $d['e']->cohort_name }} <span class="muted">{{ __('version') }} {{ $d['e']->version_no }} &middot; {{ $d['e']->reference }}</span></h2>
  <p><b>{{ $d['e']->person }}</b> {{ $d['e']->institution_id }}</p>
  <p>{{ __('Completion:') }} <b>{{ __(['not_started' => 'Not started', 'in_progress' => 'In progress', 'complete' => 'Complete'][$d['state']]) }}</b>
    @if($d['rec']->completion_status){{ __('on') }} {{ \Carbon\Carbon::parse($d['rec']->completed_on)->format('d/m/Y') }}@endif
    <span class="muted">{{ __('Module badges and verified results do not complete the micro-credential: the deputy dean confirms completion separately.') }}</span></p>
  <div class="kpis">
    <div class="kpi"><div class="l">{{ __('Modules achieved') }}</div><div class="v">{{ $nAch }} / {{ count($d['modules']) }}</div></div>
    <div class="kpi"><div class="l">{{ __('Verified results') }}</div><div class="v">{{ count($d['results']) }}</div></div>
    <div class="kpi"><div class="l">{{ __('Final result') }}</div><div class="v">{{ $d['final'] ? $d['final']['weighted_percent'].'%' : '—' }}</div></div>
    <div class="kpi"><div class="l">{{ __('Credentials awarded') }}</div><div class="v">{{ count($d['credentials']) }}</div></div>
  </div>
  @if($d['final'])<p><b>{{ __('Final result:') }}</b> {{ $d['final']['weighted_percent'] }}%</p>@endif
  <div data-tabs>
    <nav class="tabs" aria-label="Record sections"><button data-tab="modules">{{ __('Modules') }}</button><button data-tab="results">{{ __('Verified results') }}</button><button data-tab="credentials">{{ __('Credentials awarded') }}</button></nav>
    <div data-pane="modules"><h3>{{ __('Modules') }}</h3>
      <table>
        <thead><tr><th>{{ __('Module') }}</th><th>{{ __('Status') }}</th><th>{{ __('Badge') }}</th></tr></thead>
        <tbody>@foreach($d['modules'] as $m)<tr><td>{{ $m->seq }}. {{ $m->name }}</td><td>{{ $m->achieved ? __('Achieved').' '.\Carbon\Carbon::parse($m->achieved->achieved_at)->format('d/m/Y') : __('Not yet achieved') }}</td><td>{{ $m->achieved ? $m->achieved->code.($m->achieved->external_id ? ' (UCreds '.$m->achieved->external_id.')' : '') : '-' }}</td></tr>@endforeach</tbody>
      </table></div>
    <div data-pane="results"><h3>{{ __('Verified results') }}</h3>
      <table>
        <thead><tr><th>{{ __('Assessment') }}</th><th class="num">{{ __('Mark') }}</th><th>{{ __('Result') }}</th></tr></thead>
        <tbody>@forelse($d['results'] as $r)<tr><td>{{ $r->name_en }}</td><td class="num">{{ rtrim(rtrim((string) $r->score, '0'), '.') }} / {{ rtrim(rtrim((string) $r->max_mark, '0'), '.') }}</td><td>{{ (float) $r->score >= (float) $r->pass_mark ? __('Met') : __('Not met') }}</td></tr>@empty<tr><td colspan="3" class="muted">{{ __('Nothing verified yet.') }}</td></tr>@endforelse</tbody>
      </table></div>
    <div data-pane="credentials"><h3>{{ __('Credentials awarded') }}</h3>@forelse($d['credentials'] as $cr)<p>{{ $cr->number }} ({{ $cr->status }})</p>@empty<p class="muted">{{ __('None yet.') }}</p>@endforelse</div>
  </div>
  </div>
</div>
