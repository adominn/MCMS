@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', __('Achievement record'))
@section('doc_title', 'Achievement Record')
@section('doc_bm', 'Rekod Pencapaian')
@section('doc_ref', 'MCMS-R-'.(auth()->user()->institution_id ?? auth()->user()->id))
@section('doc_file', 'achievement-record-'.auth()->user()->id.'.pdf')
@section('content')
@php
  $isPrint = request()->boolean('print') || request()->has('print');
  $sel = $records->first(fn ($d) => $d['final']) ?? $records->first();
@endphp

@if(!$isPrint)
  <div class="ph">
    <div>
      <div class="crumb">
        <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
        <a href="{{ route('documents') }}">{{ __('Documents') }}</a> /
        <span>{{ __('Achievement record') }}</span>
      </div>
      <h1>{{ __('Achievement record') }}</h1>
      <div class="sub">{{ __('What you achieved in each micro-credential. It is an internal record, not an Academic Credit Record.') }}</div>
    </div>
  </div>

  <div class="note warn" style="margin-bottom:20px">
    <div>
      <strong>{{ __('Internal record, not an Academic Credit Record.') }}</strong>
      {{ __("This is MCMS's own record of modules and completions. It cannot be deposited in ABC or used for credit transfer.") }}
    </div>
  </div>

  @if(count($records))
    @php
      $totRegistrations = count($records);
      $totModulesAchieved = $records->sum(fn($d) => collect($d['modules'])->filter(fn($m) => $m->achieved)->count());
      $totCompletions = $records->filter(fn($d) => $d['state'] === 'complete')->count();
      $totCreds = $records->sum(fn($d) => count($d['credentials']));
    @endphp
    <div class="kpis" style="margin-bottom:20px">
      <div class="kpi">
        <div class="l">{{ __('Registrations') }}</div>
        <div class="v">{{ $totRegistrations }}</div>
        <div class="s muted">{{ __('Total enrolled') }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Modules achieved') }}</div>
        <div class="v">{{ $totModulesAchieved }}</div>
        <div class="s muted">{{ __('Passed module assessments') }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Completions') }}</div>
        <div class="v">{{ $totCompletions }}</div>
        <div class="s muted">{{ __('Confirmed by Deputy Dean') }}</div>
      </div>
      <div class="kpi">
        <div class="l">{{ __('Credentials awarded') }}</div>
        <div class="v">{{ $totCreds }}</div>
        <div class="s muted">{{ __('Full micro-credentials') }}</div>
      </div>
    </div>

    <div class="panel">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h2 style="margin:0">{{ __('Achievement Records Summary') }}</h2>
          <div class="s muted" style="margin-top:2px">{{ __('Select a record row or click View record to inspect verified assessments, modules, and credentials.') }}</div>
        </div>
        <span class="r">
          <span class="s muted">{{ __(':count records', ['count' => count($records)]) }}</span>
        </span>
      </div>
      <table id="achievement-records-table">
        <thead>
          <tr>
            <th style="min-width:240px">{{ __('Micro-credential / Course') }}</th>
            <th>{{ __('Cohort & Registration') }}</th>
            <th>{{ __('Modules Achieved') }}</th>
            <th class="num">{{ __('Final Result') }}</th>
            <th>{{ __('Completion Status') }}</th>
            <th>{{ __('Credentials') }}</th>
            <th class="num" style="min-width:140px">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
        @foreach($records as $d)
          @php
            $nAch = collect($d['modules'])->filter(fn ($m) => $m->achieved)->count();
            $nTot = count($d['modules']);
            $achievedBadges = collect($d['modules'])->filter(fn ($m) => !empty($m->achieved))->map(fn ($m) => $m->achieved->code);
            $creds = $d['credentials'] ?? collect();
          @endphp
          <tr data-href="{{ route('records.show', $d['e']->id) }}">
            <td>
              <a href="{{ route('records.show', $d['e']->id) }}" style="font-weight:700;color:var(--ink)">
                {{ $d['e']->name_en }}
              </a>
              <div class="s muted" style="margin-top:2px">
                <span>{{ __('Version') }} {{ $d['e']->version_no }}</span>
              </div>
            </td>
            <td>
              <div style="font-weight:600;color:var(--ink)">{{ $d['e']->cohort_name }}</div>
              <div class="s muted"><code>{{ $d['e']->reference }}</code></div>
            </td>
            <td>
              @if($nAch > 0)
                <span class="tag ok" style="font-size:11px">
                  {{ $nAch }} / {{ $nTot }} {{ __('Achieved') }}
                </span>
                @if($achievedBadges->isNotEmpty())
                  <div class="s muted" style="font-size:11px;margin-top:2px">
                    {{ $achievedBadges->implode(', ') }}
                  </div>
                @endif
              @else
                <span class="tag muted" style="font-size:11px">
                  0 / {{ $nTot }} {{ __('Achieved') }}
                </span>
              @endif
            </td>
            <td class="num">
              @if($d['final'])
                <div style="font-weight:700;color:var(--ink)">{{ $d['final']['weighted_percent'] }}%</div>
                <div class="s muted">{{ $d['final']['grade'] ?? '' }}</div>
              @else
                <span class="muted">&ndash;</span>
              @endif
            </td>
            <td>
              @if($d['state'] === 'complete')
                <span class="tag ok" style="font-size:11px">{{ __('Complete') }}</span>
                @if($d['rec']->completion_status)
                  <div class="s muted" style="font-size:10px;margin-top:2px">{{ \Carbon\Carbon::parse($d['rec']->completed_on)->format('d/m/Y') }}</div>
                @endif
              @elseif($d['state'] === 'in_progress')
                <span class="tag info" style="font-size:11px">{{ __('In progress') }}</span>
              @else
                <span class="tag muted" style="font-size:11px">{{ __('Not started') }}</span>
              @endif
            </td>
            <td>
              @if($creds->isNotEmpty())
                @foreach($creds as $cr)
                  <div><code class="vc" style="font-size:11px">{{ $cr->number }}</code></div>
                @endforeach
              @else
                <span class="muted">&ndash;</span>
              @endif
            </td>
            <td class="num">
              <a href="{{ route('records.show', $d['e']->id) }}" class="btn ter sm" title="{{ __('View individual achievement record') }}">
                <span class="btn-lbl">{{ __('View record') }} &rarr;</span>
              </a>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="panel">
      <div class="bd muted" style="text-align:center;padding:32px">
        {{ __('You are not enrolled in a cohort yet.') }}
      </div>
    </div>
  @endif

@else
  {{-- Printable Layout --}}
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">{{ __('Achievement Record') }}</h1>
    <div class="small muted">
      {{ __('Holder') }}: <b>{{ auth()->user()->name }}</b> &middot; {{ __('Learner ID') }}: <b>{{ auth()->user()->institution_id ?? auth()->user()->email }}</b> &middot; {{ __('Generated on') }} {{ now()->format('d/m/Y H:i') }}
    </div>
  </div>

  <div class="note warn" style="margin-bottom:16px;font-size:9.5pt">
    <div>
      <b>{{ __('Internal record, not an Academic Credit Record.') }}</b> {{ __("This is MCMS's own record of modules and completions. It cannot be deposited in ABC or used for credit transfer.") }}
    </div>
  </div>

  @php
    $totRegistrations = count($records);
    $totModulesAchieved = $records->sum(fn($d) => collect($d['modules'])->filter(fn($m) => $m->achieved)->count());
    $totCompletions = $records->filter(fn($d) => $d['state'] === 'complete')->count();
    $totCreds = $records->sum(fn($d) => count($d['credentials']));
  @endphp
  <div class="kpis" style="margin-bottom:20px">
    <div class="kpi">
      <div class="l">{{ __('Registrations') }}</div>
      <div class="v">{{ $totRegistrations }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Modules achieved') }}</div>
      <div class="v">{{ $totModulesAchieved }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Completions') }}</div>
      <div class="v">{{ $totCompletions }}</div>
    </div>
    <div class="kpi">
      <div class="l">{{ __('Credentials issued') }}</div>
      <div class="v">{{ $totCreds }}</div>
    </div>
  </div>

  <div class="panel" style="margin-bottom:20px">
    <div class="hd"><h2>{{ __('Registrations and completions') }}</h2></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ __('Registration') }}</th>
            <th>{{ __('Micro-credential') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Credential') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($records as $d)
            <tr>
              <td><code>{{ $d['e']->reference }}</code></td>
              <td><b>{{ $d['e']->name_en }}</b><div class="small muted">{{ $d['e']->cohort_name }}</div></td>
              <td>
                <span class="tag {{ $d['state'] === 'complete' ? 'ok' : 'info' }}">
                  {{ __(['not_started' => 'Not started', 'in_progress' => 'In progress', 'complete' => 'Complete'][$d['state']]) }}
                </span>
              </td>
              <td>
                @if(count($d['credentials']))
                  @foreach($d['credentials'] as $cr)
                    <code>{{ $cr->number }}</code> ({{ $cr->status }})
                  @endforeach
                @else
                  &ndash;
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted" style="text-align:center">{{ __('No registrations on record.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @foreach($records as $d)
    <div class="panel" style="margin-bottom:20px">
      <div class="hd">
        <h2>{{ $d['e']->name_en }} &ndash; {{ $d['e']->cohort_name }}</h2>
        <span class="r tag">{{ $d['e']->reference }}</span>
      </div>
      <div class="bd">
        <h3 style="font-size:11pt;margin:0 0 8px">{{ __('Modules and badges') }}</h3>
        <table style="margin-bottom:16px">
          <thead>
            <tr>
              <th>{{ __('Module') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Date') }}</th>
              <th>{{ __('Badge') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($d['modules'] as $m)
              <tr>
                <td>{{ $m->seq }}. {{ $m->name }}</td>
                <td>
                  <span class="tag {{ $m->achieved ? 'ok' : 'muted' }}">
                    {{ $m->achieved ? __('Achieved') : __('Not yet achieved') }}
                  </span>
                </td>
                <td>{{ $m->achieved ? \Carbon\Carbon::parse($m->achieved->achieved_at)->format('d/m/Y') : '–' }}</td>
                <td>{{ $m->achieved ? ($m->achieved->code . ($m->achieved->external_id ? ' (UCreds ' . $m->achieved->external_id . ')' : '')) : '–' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

        @if(count($d['results']))
          <h3 style="font-size:11pt;margin:0 0 8px">{{ __('Verified assessment results') }}</h3>
          <table>
            <thead>
              <tr>
                <th>{{ __('Assessment') }}</th>
                <th class="num">{{ __('Mark') }}</th>
                <th>{{ __('Result') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($d['results'] as $r)
                <tr>
                  <td>{{ $r->name_en }}</td>
                  <td class="num">{{ rtrim(rtrim((string) $r->score, '0'), '.') }} / {{ rtrim(rtrim((string) $r->max_mark, '0'), '.') }}</td>
                  <td>
                    <span class="tag {{ (float) $r->score >= (float) $r->pass_mark ? 'ok' : 'err' }}">
                      {{ (float) $r->score >= (float) $r->pass_mark ? __('Met') : __('Not met') }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    </div>
  @endforeach

  <div style="display:flex;gap:40px;margin-top:auto;padding-top:20px;border-top:1px solid var(--border)">
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">Registrar's Office / CODL</div>
      <div class="bm small">Pejabat Pendaftar / CODL</div>
    </div>
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">{{ now()->format('d/m/Y') }}</div>
      <div class="bm small">Tarikh</div>
    </div>
  </div>
@endif

@endsection