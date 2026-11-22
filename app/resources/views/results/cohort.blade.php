@extends('layouts.app')
@section('title', __('Cohort results'))
@section('content')
<x-breadcrumb :crumbs="[
  'Cohorts and results' => route('offerings'),
  $o->name_en . ' ' . $o->cohort_name => route('offerings.show', $o->id),
  'Results' => null,
]" />

<div class="title">
  <div>
    <h1>{{ __('Results: :name :cohort', ['name' => $o->name_en, 'cohort' => $o->cohort_name]) }}</h1>
    <div class="sub">
      @if(!empty($o->lecturer_name)){{ $o->lecturer_name }} &middot; @endif
      <a href="{{ route('offerings.show', $o->id) }}">{{ __('The cohort') }}</a> &middot; <a href="{{ route('offerings.participants', $o->id) }}">{{ __('Participants') }}</a>
      @if($locked)&middot; <span class="tag ok">{{ __('Submitted for verification') }}</span> {{ \Carbon\Carbon::parse($locked)->format('d/m/Y H:i') }}@else&middot; <span class="tag warn">{{ __('Draft') }}</span>@endif
    </div>
  </div>
  <div class="act">
    <a class="btn ter sm" href="{{ route('completion.signoff.show', $o->id) }}">{{ __('Coordinator sign-off') }} &rarr;</a>
    <a class="btn ter sm" href="{{ route('offerings.participants', $o->id) }}">{{ __('Participants') }}</a>
    <a class="btn ter sm" href="{{ route('offerings.show', $o->id) }}">&larr; {{ __('Back to cohort') }}</a>
  </div>
</div>

@php
  $totalLearners = $enrol->count();
  $heldCount = count($openCases);
  $missingCount = 0;
  $gradedLearners = 0;
  foreach ($enrol as $e) {
    $hasAny = false;
    $isHeld = in_array($e->id, $openCases);
    foreach ($comps as $c) {
      $val = $results[$e->id][$c->id]->score ?? null;
      if ($val === null && ! $isHeld) {
        $missingCount++;
      }
      if ($val !== null) {
        $hasAny = true;
      }
    }
    if ($hasAny) {
      $gradedLearners++;
    }
  }
@endphp

<div class="kpis">
  <div class="kpi">
    <div class="l">{{ __('Total learners') }}</div>
    <div class="v" id="kpi-total">{{ $totalLearners }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Graded / Provisional') }}</div>
    <div class="v" id="kpi-graded">{{ $gradedLearners }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Marks missing') }}</div>
    <div class="v" id="kpi-missing" @if($missingCount > 0) style="color:var(--danger,#da1e28);font-weight:600" @endif>{{ $missingCount }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Held for integrity') }}</div>
    <div class="v" id="kpi-held" @if($heldCount > 0) style="color:var(--warn-dark,#b28600);font-weight:600" @endif>{{ $heldCount }}</div>
  </div>
</div>

<div id="status-banner">
@if($locked)
  <div class="note ok" style="margin-bottom:16px">
    <div>
      <b>{{ __('Submitted to the Deputy Dean') }}</b> ({{ \Carbon\Carbon::parse($locked)->format('d/m/Y H:i') }}). {{ __('Marks are locked pending verification.') }}
    </div>
  </div>
@elseif($missingCount > 0)
  <div class="note warn" style="margin-bottom:16px">
    <div>
      <b><span id="banner-missing-count">{{ $missingCount }}</span> {{ __('mark(s) missing.') }}</b> {{ __('Complete all component marks before submitting for verification.') }}
    </div>
  </div>
@else
  <div class="note ok" style="margin-bottom:16px">
    <div>
      <b>{{ __('All marks are complete.') }}</b> {{ __('You can submit the cohort results to the Deputy Dean for verification.') }}
    </div>
  </div>
@endif
</div>

<div class="note" style="margin-bottom:20px">
  <div>
    {{ __('The assessment plan comes from the approved micro-credential version and cannot be altered here. Attendance or progress never gives a pass: only verified marks do (FR-121). As the course lecturer who entered these marks, verification belongs to the Deputy Dean (D-15 Separation of Duties).') }}
  </div>
</div>

<form method="post" action="{{ route('results.save', $o->id) }}" class="card" style="margin-bottom:24px">@csrf
  @include('results._table', ['flag' => false, 'changeable' => $locked === null ? false : true])
  @if($editable)
    <p style="margin-top:16px;display:flex;gap:8px">
      <button type="submit" name="action" value="save" class="btn sm">{{ __('Save marks') }}</button>
      <button type="submit" name="action" value="submit" class="btn ter sm" @if($missingCount > 0) title="{{ __('All marks must be filled before submitting') }}" @endif>{{ __('Submit for verification') }}</button>
    </p>
  @else
    <p style="margin-top:16px">
      <button type="submit" name="action" value="withdraw" class="btn ter sm">{{ __('Withdraw the submission') }}</button>
    </p>
  @endif
</form>

@if($editable)
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('.table-wrap');
    if (!table) return;

    function updateRow(eid) {
      const inputs = document.querySelectorAll('input.mark-input[data-eid="' + eid + '"]');
      if (!inputs.length) return;

      let hasMissing = false;
      let weightedSum = 0;
      let allPassed = true;

      inputs.forEach(input => {
        const v = input.value.trim();
        const max = parseFloat(input.dataset.max) || 100;
        const pass = parseFloat(input.dataset.pass) || 50;
        const weight = parseFloat(input.dataset.weight) || 0;

        if (v === '' || isNaN(v)) {
          hasMissing = true;
          input.style.border = '1px dashed var(--warn-dark, #b28600)';
          input.style.background = 'var(--warn-bg, #fcf4d6)';
        } else {
          const num = parseFloat(v);
          if (num < 0 || num > max) {
            hasMissing = true;
            input.style.border = '1px solid var(--danger, #da1e28)';
            input.style.background = '#fff1f1';
          } else {
            input.style.border = '';
            input.style.background = '';
            weightedSum += (num / max) * weight;
            if (num < pass) {
              allPassed = false;
            }
          }
        }
      });

      if (weightedSum < 50) {
        allPassed = false;
      }

      const finEl = document.getElementById('fin-' + eid);
      const rsEl = document.getElementById('rs-' + eid);

      if (finEl) {
        finEl.innerHTML = hasMissing ? '<b>-</b>' : '<b>' + Math.round(weightedSum) + '</b>';
      }
      if (rsEl && !rsEl.innerText.includes('Held')) {
        if (hasMissing) {
          rsEl.innerHTML = '<span class="tag warn">{{ __("Missing") }}</span>';
        } else if (allPassed) {
          rsEl.innerHTML = '<span class="tag ok">{{ __("Pass") }}</span>';
        } else {
          rsEl.innerHTML = '<span class="tag err">{{ __("Fail") }}</span>';
        }
      }
    }

    function updateGlobalKpis() {
      const allInputs = document.querySelectorAll('input.mark-input');
      if (!allInputs.length) return;

      let missing = 0;
      const gradedEids = new Set();

      allInputs.forEach(input => {
        const eid = input.dataset.eid;
        const v = input.value.trim();
        if (v === '' || isNaN(v)) {
          missing++;
        } else {
          gradedEids.add(eid);
        }
      });

      const kpiMiss = document.getElementById('kpi-missing');
      if (kpiMiss) {
        kpiMiss.textContent = missing;
        kpiMiss.style.color = missing > 0 ? 'var(--danger, #da1e28)' : '';
        kpiMiss.style.fontWeight = missing > 0 ? '600' : '';
      }

      const kpiGraded = document.getElementById('kpi-graded');
      if (kpiGraded) {
        kpiGraded.textContent = gradedEids.size;
      }

      const banner = document.getElementById('status-banner');
      if (banner) {
        if (missing > 0) {
          banner.innerHTML = '<div class="note warn" style="margin-bottom:16px"><div><b><span id="banner-missing-count">' + missing + '</span> {{ __("mark(s) missing.") }}</b> {{ __("Complete all component marks before submitting for verification.") }}</div></div>';
        } else {
          banner.innerHTML = '<div class="note ok" style="margin-bottom:16px"><div><b>{{ __("All marks are complete.") }}</b> {{ __("You can submit the cohort results to the Deputy Dean for verification.") }}</div></div>';
        }
      }

      const submitBtn = document.querySelector('button[value="submit"]');
      if (submitBtn) {
        if (missing > 0) {
          submitBtn.setAttribute('title', missing + ' {{ __("mark(s) missing. Complete all marks before submitting.") }}');
        } else {
          submitBtn.removeAttribute('title');
        }
      }
    }

    table.addEventListener('input', function (e) {
      if (e.target.matches('input.mark-input')) {
        const eid = e.target.dataset.eid;
        updateRow(eid);
        updateGlobalKpis();
      }
    });
  });
  </script>
@endif

@if($editable)
  <h2>{{ __('Integrity cases') }}</h2>
  <p class="muted">{{ __("A flagged participant's results are held and cannot be verified until the deputy dean resolves the case.") }}</p>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Participant') }}</th>
          <th>{{ __('Flag reason & action') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($enrol as $e)
          <tr>
            <td style="width:240px">
              <b>{{ $e->name }}</b><br>
              <span class="muted s">{{ $e->institution_id }}</span>
              @if(in_array($e->id, $openCases))
                <br><span class="tag overdue">{{ __('Integrity case open') }}</span>
              @endif
            </td>
            <td>
              @if(!in_array($e->id, $openCases))
                <form method="post" action="{{ route('results.flag', $o->id) }}" class="row" style="gap:8px;align-items:center">@csrf
                  <input type="hidden" name="enrolment_id" value="{{ $e->id }}">
                  <input type="text" name="reason" placeholder="{{ __('Reason to hold results for integrity investigation') }}" required minlength="5" aria-label="Reason to flag {{ $e->name }}" style="max-width:400px;height:32px">
                  <button type="submit" class="btn danger sm" style="height:32px">{{ __('Flag integrity case') }}</button>
                </form>
              @else
                <span class="muted">{{ __('Case is currently open under Deputy Dean review.') }}</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection
