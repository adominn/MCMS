<div class="card">
  <h2>{{ __('Proposal summary') }}</h2>
  <p><b>{{ $v->name_en }}</b> / {{ $v->name_ms }}<br>
    {{ strtoupper($v->category) }} &middot; {{ $v->ptj_code }} &middot; {{ $v->owner_name }} &middot; {{ __($cfg['delivery_modes'][$v->delivery_mode] ?? '-') }}, {{ $v->delivery_language }}</p>
  <p><b>{{ __('Need:') }}</b> {{ $v->need }}</p>
  @if($course)
    @php
      $modelKey = $v->credit_model;
      $modelMeta = config("proposals.unbundling_models.$modelKey");
    @endphp
    <div style="background:#f8f9fa;border:1px solid #e0e0e0;border-left:3px solid #1e2a6e;padding:10px 14px;border-radius:2px;margin:10px 0">
      <div style="font-size:12.5px;margin-bottom:4px">
        <b>{{ __('Parent course:') }}</b> {{ $course->code }} {{ $course->name }} &middot; {{ $course->credit }} {{ __('credits') }} &middot; {{ $course->slt_hours }} h SLT &middot; {{ $course->programme }}
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;margin-top:4px">
        <b>{{ __('Unbundling Model:') }}</b>
        @if($modelMeta)
          <span class="tag" style="background:#ffffff;border:1px solid {{ $modelMeta['badge_color'] }};color:{{ $modelMeta['badge_color'] }};font-weight:700">
            ({{ $modelMeta['letter'] }}) {{ app()->getLocale() === 'ms' ? $modelMeta['name_ms'] : $modelMeta['name_en'] }}
          </span>
          <span class="muted small">{{ app()->getLocale() === 'ms' ? $modelMeta['desc_ms'] : $modelMeta['desc_en'] }}</span>
        @else
          <span class="muted">{{ $v->credit_model ? __($cfg['credit_models'][$v->credit_model] ?? $v->credit_model) : '-' }}</span>
        @endif
      </div>
    </div>
  @endif
  <p><b>{{ __('Modules:') }}</b> @forelse($modules as $m){{ $m->seq }}. {{ $m->name_en }} ({{ $m->slt_hours }} h)@if(!$loop->last); @endif @empty {{ __('none') }} @endforelse &mdash; {{ __('total') }} {{ $modules->sum('slt_hours') }} h</p>
  <p><b>{{ __('Learning outcomes:') }}</b></p>
  <ul>@forelse($los as $lo)<li>{{ $lo->code }}: {{ $lo->text_en }}</li>@empty<li class="muted">{{ __('none') }}</li>@endforelse</ul>
  <p><b>{{ __('Assessment:') }}</b> @forelse($comps as $c){{ $c->name_en }} ({{ rtrim(rtrim(number_format((float) $c->weight_percent, 1), '0'), '.') }}%)@if(!$loop->last); @endif @empty {{ __('none') }} @endforelse</p>
  <p><b>{{ __('Proposed credit / MQF level:') }}</b> {{ $v->proposed_credit !== null ? rtrim(rtrim((string) $v->proposed_credit, '0'), '.') : '-' }} / {{ $v->proposed_mqf_level ?? '-' }} <span class="muted">({{ __('proposals only until approval or Quality Verification confirms them') }})</span></p>
  <p><b>{{ __('Readiness:') }}</b> @foreach($cfg['readiness'] as $k => $l){{ __($l) }}: {{ __(['yes' => 'Yes', 'not_yet' => 'Not yet', 'na' => 'Not applicable'][$ready[$k]->status ?? 'not_yet']) }}@if(!$loop->last); @endif @endforeach</p>
</div>
