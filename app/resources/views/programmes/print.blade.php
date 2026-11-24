<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <title>{{ $programme->code }} &ndash; {{ __('Degree Plan') }}</title>
  <style>
    body{font-family:"Plus Jakarta Sans",Arial,sans-serif;color:#090a0f;background:#fff;margin:24px;font-size:12px;line-height:1.4}
    h1{font-size:18px;margin:0 0 4px}
    h2{font-size:13px;margin:16px 0 6px;border-bottom:1px solid #090a0f;padding-bottom:3px}
    .meta{color:#475569;margin-bottom:14px}
    table{width:100%;border-collapse:collapse;margin-bottom:12px}
    th,td{border:1px solid #090a0f;padding:5px 7px;text-align:left;vertical-align:top}
    th{background:#f8fafc;font-size:11px;text-transform:uppercase}
    .num{text-align:right;font-variant-numeric:tabular-nums}
    .mono{font-family:"IBM Plex Mono",Consolas,monospace;font-size:11px}
    .note{border:1px solid #090a0f;padding:8px 10px;margin-top:16px;font-size:11px}
    .no-print{margin-bottom:14px}
    @media print{.no-print{display:none}}
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()" style="padding:6px 14px;background:#0052ff;color:#fff;border:none;border-radius:4px;cursor:pointer">{{ __('Print / Save as PDF') }}</button>
  </div>

  <h1>{{ $programme->code }} &ndash; {{ $programme->name }}</h1>
  <div class="meta">
    {{ $programme->faculty_code ?? '' }} {{ $programme->faculty_name ?? '' }} &middot;
    MQF {{ $programme->mqf_level }} &middot;
    {{ __('Required Credits:') }} {{ $plan['version']->total_credits ?? $programme->total_credits }} cr
    @if($plan['version']) &middot; {{ __('Session') }} {{ $plan['version']->academic_session }} @endif
    &middot; {{ __('Generated:') }} {{ now()->format('Y-m-d') }}
  </div>

  @if($plan['progress'])
    <div style="border:1px solid #090a0f;padding:8px 10px;margin-bottom:14px">
      <strong>{{ __('Learner Progress Summary:') }}</strong>
      {{ __('Completed:') }} {{ mcms_num($plan['progress']['completed']) }} cr &middot;
      {{ __('Transferred:') }} {{ mcms_num($plan['progress']['transferred']) }} cr &middot;
      {{ __('In Progress:') }} {{ mcms_num($plan['progress']['in_progress']) }} cr &middot;
      {{ __('Remaining:') }} {{ mcms_num($plan['progress']['remaining']) }} cr
    </div>
  @endif

  @foreach($plan['years'] as $year)
    @foreach($year->semesters as $sem)
      <h2>{{ __($year->label) }} &ndash; {{ __($sem->label) }} ({{ mcms_num($sem->credits) }} cr &middot; {{ __('Running Total:') }} {{ mcms_num($sem->running_total) }} / {{ mcms_num($plan['version']->total_credits ?? $programme->total_credits) }} cr)</h2>
      <table>
        <thead>
          <tr>
            <th style="width:95px">{{ __('Code') }}</th>
            <th>{{ __('Course Name') }}</th>
            <th style="width:45px">{{ __('Type') }}</th>
            <th class="num" style="width:55px">{{ __('Credits') }}</th>
            <th style="width:130px">{{ __('Prerequisites') }}</th>
            @if($plan['progress'])<th style="width:90px">{{ __('Status') }}</th>@endif
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
                <td>{{ $c->is_blocked ? __('Blocked') : ucfirst(str_replace('_', ' ', (string) $c->learner_state)) }}</td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    @endforeach
  @endforeach

  <div class="note">
    {{ __('This degree plan is for academic planning only. Formal credit transfer and programme admission are subject to evaluation and approval by UTeM. At least :min% of graduating credits must be earned directly at UTeM.', ['min' => mcms_num($minHomePercent)]) }}
  </div>
</body>
</html>
