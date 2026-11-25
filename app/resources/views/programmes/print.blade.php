@extends('layouts.print')
@section('title', $programme->code . ' - ' . __('Degree Plan'))
@section('doc_title', __('Degree Plan'))
@section('doc_bm', 'Pelan Ijazah')
@section('doc_ref', $programme->code)
@section('doc_file', 'degree-plan-' . strtolower($programme->code) . '.pdf')
@section('content')
  <h1 style="font-size:14pt;margin:0 0 4px">{{ $programme->code }} &ndash; {{ $programme->name }}</h1>
  <div class="muted small" style="margin-bottom:14px">
    {{ $programme->faculty_code ?? '' }} {{ $programme->faculty_name ?? '' }} &middot;
    MQF {{ $programme->mqf_level }} &middot;
    {{ __('Required Credits:') }} {{ $plan['version']->total_credits ?? $programme->total_credits }} cr
    @if($plan['version']) &middot; {{ __('Session') }} {{ $plan['version']->academic_session }} @endif
    &middot; {{ __('Generated:') }} {{ now()->format('d/m/Y') }}
  </div>

  @if($plan['progress'])
    <div style="border:1px solid #090a0f;padding:8px 10px;margin-bottom:14px;font-size:9pt">
      <strong>{{ __('Learner Progress Summary:') }}</strong>
      {{ __('Completed:') }} {{ mcms_num($plan['progress']['completed']) }} cr &middot;
      {{ __('Transferred:') }} {{ mcms_num($plan['progress']['transferred']) }} cr &middot;
      {{ __('In Progress:') }} {{ mcms_num($plan['progress']['in_progress']) }} cr &middot;
      {{ __('Remaining:') }} {{ mcms_num($plan['progress']['remaining']) }} cr
    </div>
  @endif

  @foreach($plan['years'] as $year)
    @foreach($year->semesters as $sem)
      <h2 style="font-size:10.5pt;margin:14px 0 6px;border-bottom:1px solid #090a0f;padding-bottom:3px">{{ __($year->label) }} &ndash; {{ __($sem->label) }} ({{ mcms_num($sem->credits) }} cr &middot; {{ __('Running Total:') }} {{ mcms_num($sem->running_total) }} / {{ mcms_num($plan['version']->total_credits ?? $programme->total_credits) }} cr)</h2>
      <table class="print-audit-table">
        <thead>
          <tr>
            <th style="width:14%">{{ __('Code') }}</th>
            <th>{{ __('Course Name') }}</th>
            <th style="width:8%">{{ __('Type') }}</th>
            <th class="num" style="width:9%">{{ __('Credits') }}</th>
            <th style="width:18%">{{ __('Prerequisites') }}</th>
            @if($plan['progress'])<th style="width:14%">{{ __('Status') }}</th>@endif
          </tr>
        </thead>
        <tbody>
          @foreach($sem->courses as $c)
            <tr>
              <td class="mono">{{ $c->code }}</td>
              <td>{{ $c->name }}</td>
              <td>{{ $c->component_code }}</td>
              <td class="num">{{ mcms_num($c->credits) }}</td>
              <td class="mono">{{ $c->prerequisites->pluck('req_code')->filter()->implode(', ') ?: '—' }}</td>
              @if($plan['progress'])
                <td>{{ $c->is_blocked ? __('Blocked') : __(ucfirst(str_replace('_', ' ', (string) $c->learner_state))) }}</td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    @endforeach
  @endforeach

  <div class="print-note-box">
    {{ __('This degree plan is for academic planning only. Formal credit transfer and programme admission are subject to evaluation and approval by UTeM. At least :min% of graduating credits must be earned directly at UTeM.', ['min' => mcms_num($minHomePercent)]) }}
  </div>
@endsection
