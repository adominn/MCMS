@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', __('Achievement record: :name', ['name' => $d['e']->name_en]))
@section('doc_title', 'Achievement Record')
@section('doc_bm', 'Rekod Pencapaian')
@section('doc_ref', 'MCMS-R-'.($d['e']->institution_id ?? $d['e']->user_id))
@section('doc_file', 'achievement-record-'.$d['e']->reference.'.pdf')
@section('content')

@if(request()->boolean('print') || request()->has('print'))
  {{-- Printable Individual Record Layout --}}
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">{{ __('Achievement Record') }}: {{ $d['e']->name_en }}</h1>
    <div class="small muted">
      {{ __('Holder') }}: <b>{{ $d['e']->person }}</b> &middot; {{ __('Learner ID') }}: <b>{{ $d['e']->institution_id ?? '-' }}</b> &middot; {{ __('Cohort') }}: <b>{{ $d['e']->cohort_name }}</b> &middot; {{ __('Generated on') }} {{ now()->format('d/m/Y H:i') }}
    </div>
  </div>

  @include('records._record', ['d' => $d])

  <div style="display:flex;gap:40px;margin-top:auto;padding-top:20px;border-top:1px solid var(--border)">
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">{{ __("Registrar's Office / CODL") }}</div>
    </div>
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">{{ now()->format('d/m/Y') }}</div>
      <div class="bm small">{{ __('Date') }}</div>
    </div>
  </div>

@else
  {{-- Carbon Screen Layout --}}
  @php
    $u = auth()->user();
    $isStudent = $u && $u->hasAnyRole('student');
    $isLecturer = $u && $u->hasAnyRole('lecturer');
    $isDeputyDean = $u && $u->hasAnyRole('deputy_dean');

    if ($isStudent) {
        $crumbs = [
            __('My learning') => route('home'),
            __('Achievement records') => route('records.mine'),
            $d['e']->name_en => null,
        ];
        $backUrl = route('records.mine');
        $backLabel = __('All records');
        $backBottomLabel = __('Back to all achievement records');
    } elseif ($isDeputyDean) {
        $crumbs = [
            __('Results and completion') => route('results'),
            ($d['e']->name_en ?? __('Cohort')) . ' ' . ($d['e']->cohort_name ?? '') => route('completion.show', $d['e']->offering_id),
            __('Achievement record: :name', ['name' => $d['e']->person]) => null,
        ];
        $backUrl = route('completion.show', $d['e']->offering_id);
        $backLabel = __('Back to completion');
        $backBottomLabel = __('Back to completion checks');
    } elseif ($isLecturer) {
        $crumbs = [
            __('Cohorts and results') => route('offerings'),
            ($d['e']->name_en ?? __('Cohort')) . ' ' . ($d['e']->cohort_name ?? '') => route('offerings.show', $d['e']->offering_id),
            __('Participants') => route('offerings.participants', $d['e']->offering_id),
            __('Record: :name', ['name' => $d['e']->person]) => null,
        ];
        $backUrl = route('offerings.participants', $d['e']->offering_id);
        $backLabel = __('Back to participants');
        $backBottomLabel = __('Back to cohort participants');
    } else {
        $crumbs = [
            __('Documents') => route('documents'),
            __('Achievement record: :name', ['name' => $d['e']->person ?? $d['e']->name_en]) => null,
        ];
        $backUrl = route('documents');
        $backLabel = __('Back to documents');
        $backBottomLabel = __('Back to documents');
    }
  @endphp

  <x-breadcrumb :crumbs="$crumbs" :back="$backUrl" />

  <div class="title" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div>
      <h1><span class="cat">{{ strtoupper($d['e']->category ?? 'MC') }}</span> {{ __('Achievement Record') }}: {{ $d['e']->name_en }}</h1>
      <div class="sub">
        @if(!empty($d['e']->ptj_name)){{ $d['e']->ptj_name }} &middot; @endif
        @if(!empty($d['e']->lecturer_name)){{ $d['e']->lecturer_name }} &middot; @endif
        {{ $d['e']->cohort_name }} &middot; 
        {{ __('Reference') }} <code>{{ $d['e']->reference }}</code>
      </div>
    </div>
    <div class="act" style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn primary sm" href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" title="{{ __('Open official printable A4 sheet') }}">
        <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-right:6px" aria-hidden="true">
          <path d="M4 6V2h8v4M4 12H2.5A1.5 1.5 0 0 1 1 10.5v-3A1.5 1.5 0 0 1 2.5 6h11A1.5 1.5 0 0 1 15 7.5v3a1.5 1.5 0 0 1-1.5 1.5H12"/>
          <rect x="4" y="9" width="8" height="5"/>
        </svg>
        {{ __('Print record (A4)') }}
      </a>
      <a href="{{ $backUrl }}" class="btn ghost sm">
        &larr; {{ $backLabel }}
      </a>
    </div>
  </div>

  @include('records._record', ['d' => $d])

  <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--rule);display:flex;justify-content:space-between;align-items:center">
    <a href="{{ $backUrl }}" class="btn ghost sm">
      <span class="btn-lbl">&larr; {{ $backBottomLabel }}</span>
    </a>
    <span class="s muted">{{ __('Record identifier: :ref', ['ref' => $d['e']->reference]) }}</span>
  </div>
@endif

@endsection