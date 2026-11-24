@extends('layouts.app')
@section('title', __('Credit transfer evaluation'))
@section('content')
@php
  $pendingCount = $rows->whereIn('status', ['submitted', 'under_eval'])->count();
  $canEvaluate = auth()->user()->hasAnyRole('codl_admin', 'academic_authority');
  $isDd = ! $canEvaluate && auth()->user()->hasAnyRole('deputy_dean', 'ptj_authority');
@endphp

<div class="title">
  <div>
    @if($isDd)
      <h1>{{ __('Credit Transfer Monitor') }}</h1>
      <div class="sub">{{ __('Faculty Programme Overview · Read-only monitor of credit transfers evaluated by CODL and approved by Senate for your faculty programmes.') }}</div>
    @else
      <h1>{{ __('Credit Transfer Evaluation & Decisions') }}</h1>
      <div class="sub">{{ __('CODL & Senate Academic Authority Workspace · Evaluate learning outcome equivalence, check ACR validity, and record Senate transfer decisions.') }}</div>
    @endif
  </div>
</div>

<div data-tabs data-auto>
  <nav class="tabs" aria-label="Transfer stages" style="margin-bottom:20px">
    <button data-tab="applications">{{ __('Applications') }} <span class="muted">{{ count($rows) }}</span></button>
    <button data-tab="held">{{ __('Credit held (Redemption)') }} <span class="muted">{{ count($inUse) }}</span></button>
    <button data-tab="admissions">{{ __('Admissions recorded') }} <span class="muted">{{ count($admissions) }}</span></button>
  </nav>

  <div data-pane="applications" @if(!count($rows)) data-empty @endif>
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Applications queue') }}</h2>
        <span class="r"><span class="tag info">{{ count($rows) }} {{ __('cases') }}</span></span>
      </div>
      <div class="table-wrap">
        <table id="queue">
          <thead>
            <tr>
              <th>{{ __('Reference') }}</th>
              <th>{{ __('Participant') }}</th>
              <th>{{ __('Programme') }}</th>
              <th>{{ __('Status') }}</th>
              <th style="text-align:right">{{ __('Action') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($rows as $r)
            <tr>
              <td>
                <a href="{{ route('transfers.show', $r->id) }}" style="font-weight:600"><code>{{ $r->reference }}</code></a>
              </td>
              <td>{{ $r->user_name }}<div class="s muted"><code>{{ $r->institution_id }}</code></div></td>
              <td><code>{{ $r->programme }}</code></td>
              <td><x-pill :s="__($status[$r->status] ?? $r->status)" /></td>
              <td style="text-align:right">
                <a class="btn ter sm" href="{{ route('transfers.show', $r->id) }}">{{ __('Open') }}</a>
                @if(in_array($r->status, ['approved', 'partly', 'not_approved'], true))
                  <a class="btn ter sm" href="{{ route('transfers.letter', $r->id) }}?print=1" target="_blank">{{ __('Letter (A4)') }}</a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No application.') }}</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div data-pane="held" @if(!count($inUse)) data-empty @endif>
    @php
      $canRedeem = auth()->user()->hasAnyRole('codl_admin', 'academic_authority', 'abc_depositor', 'records_depositor');
      $heldApps = $inUse->groupBy('application_id')->map(function ($group) {
          $first = $group->first();
          return (object) [
              'application_id' => $first->application_id,
              'reference' => $first->reference,
              'programme' => $first->programme ?? '',
              'name' => $first->name,
              'institution_id' => $first->institution_id ?? '',
              'line_ids' => $group->pluck('id')->all(),
              'course_codes' => $group->pluck('course_code')->unique()->values()->all(),
              'courses' => $group->map(fn ($x) => (object) [
                  'course_code' => $x->course_code,
                  'course_name' => $x->course_name,
                  'mc_name' => $x->mc_name ?? '',
                  'acr_reference' => $x->acr_reference ?? '',
                  'credits' => (float) ($x->credits_approved ?: ($x->credits_requested ?? 0)),
              ])->values(),
              'line_count' => $group->count(),
              'total_credits' => (float) $group->sum(fn ($x) => $x->credits_approved ?: ($x->credits_requested ?? 0)),
          ];
      })->values();
      $heldProgrammes = $heldApps->pluck('programme')->filter()->unique()->sort()->values();
    @endphp
    <div class="panel">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h2 style="margin:0">{{ __('Single redemption ledger (Provisionally redeemed credits)') }}</h2>
        <span class="tag warn">{{ $heldApps->count() }} {{ $heldApps->count() === 1 ? __('application') : __('applications') }} &middot; {{ count($inUse) }} {{ count($inUse) === 1 ? __('course') : __('courses') }}</span>
      </div>

      @if($heldApps->isNotEmpty() && $canRedeem)
        <form method="post" action="{{ route('transfers.redeem.bulk') }}" id="bulk-redeem-form">
          @csrf
          <input type="hidden" name="return_to" value="held">

          {{-- Bulk Action Bar for Cohort / Multi-Student Redemption --}}
          <div style="padding:12px 16px;background:var(--color-canvas-subtle, #f8fafc);border-bottom:1px solid var(--color-line-hairline, #e2e8f0);display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <div style="display:inline-flex;flex-direction:column;gap:2px;flex:1;min-width:170px">
              <label for="bulk-ev" class="small muted">{{ __('Evidence reference (e.g. Senate graduation list)') }} *</label>
              <input id="bulk-ev" name="evidence_ref" type="text" placeholder="{{ __('e.g. SENATE/GRAD/2026-BITS') }}" required style="width:100%;min-height:34px;padding:4px 8px;font-size:12.5px">
            </div>
            <div style="display:inline-flex;flex-direction:column;gap:2px;min-width:210px">
              <label for="bulk-what" class="small muted">{{ __('Outcome') }} *</label>
              <select id="bulk-what" name="what" style="min-height:34px;padding:4px 8px;font-size:12.5px">
                <option value="completed">{{ __('Programme completed (Redeem)') }}</option>
                <option value="not_completed">{{ __('Withdrawn or not completed (Release)') }}</option>
              </select>
            </div>
            <div style="display:inline-flex;flex-direction:column;gap:2px">
              <label for="bulk-abcon" class="small muted">{{ __('ABC updated on') }}</label>
              <input id="bulk-abcon" name="abc_updated_on" type="date" style="width:140px;min-height:34px;padding:4px 8px;font-size:12.5px">
            </div>
            <div style="display:inline-flex;flex-direction:column;gap:2px">
              <label for="bulk-abcref" class="small muted">{{ __('ABC update ref') }}</label>
              <input id="bulk-abcref" name="abc_update_ref" type="text" placeholder="{{ __('Optional ref') }}" style="width:140px;min-height:34px;padding:4px 8px;font-size:12.5px">
            </div>
            <div>
              <button type="submit" class="btn primary sm" id="bulk-redeem-btn" disabled>{{ __('Record for selected (0)') }}</button>
            </div>
          </div>

          {{-- Search, Programme Filter & Sort Bar for Bulk Selection --}}
          <div style="padding:10px 16px;background:#ffffff;border-bottom:1px solid var(--color-card-border, #090a0f);display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;flex:1">
              <input id="held-search" type="search" placeholder="{{ __('Search student, matric, ref, or course...') }}" aria-label="{{ __('Search held applications') }}" style="min-width:240px;flex:1;max-width:360px;min-height:32px;padding:4px 10px;font-size:12.5px">
              <select id="held-prog-filter" aria-label="{{ __('Filter by programme') }}" style="min-height:32px;padding:4px 8px;font-size:12.5px">
                <option value="">{{ __('All programmes') }}</option>
                @foreach($heldProgrammes as $pc)
                  <option value="{{ $pc }}">{{ $pc }}</option>
                @endforeach
              </select>
              <select id="held-sort" aria-label="{{ __('Sort applications') }}" style="min-height:32px;padding:4px 8px;font-size:12.5px">
                <option value="ref_asc">{{ __('Sort: Reference (A–Z)') }}</option>
                <option value="name_asc">{{ __('Sort: Participant (A–Z)') }}</option>
                <option value="credits_desc">{{ __('Sort: Credits (High–Low)') }}</option>
                <option value="courses_desc">{{ __('Sort: Courses (High–Low)') }}</option>
              </select>
            </div>
            <span class="s muted" id="held-visible-count">{{ $heldApps->count() }} {{ $heldApps->count() === 1 ? __('row') : __('rows') }}</span>
          </div>

          <div id="bulk-line-inputs"></div>

          <div class="table-wrap">
            <table id="in-use" data-no-search style="margin:0">
              <thead>
                <tr>
                  <th style="width:38px">
                    <input type="checkbox" id="held-select-all" aria-label="{{ __('Select all visible applications') }}">
                  </th>
                  <th>{{ __('Application') }}</th>
                  <th>{{ __('Participant') }}</th>
                  <th>{{ __('Courses Held') }}</th>
                  <th style="text-align:right">{{ __('Total Credits') }}</th>
                </tr>
              </thead>
              <tbody id="held-tbody">
                @foreach($heldApps as $appRow)
                  @php
                    $searchBlob = strtolower($appRow->reference.' '.$appRow->programme.' '.$appRow->name.' '.$appRow->institution_id.' '.implode(' ', $appRow->course_codes).' '.$appRow->courses->pluck('course_name')->implode(' ').' '.$appRow->courses->pluck('mc_name')->implode(' '));
                  @endphp
                  <tr data-held-row
                      data-prog="{{ $appRow->programme }}"
                      data-ref="{{ strtolower($appRow->reference) }}"
                      data-name="{{ strtolower($appRow->name) }}"
                      data-credits="{{ $appRow->total_credits }}"
                      data-courses="{{ $appRow->line_count }}"
                      data-search="{{ $searchBlob }}">
                    <td style="vertical-align:top;padding-top:12px">
                      <input type="checkbox" class="held-app-chk" data-lines='@json($appRow->line_ids)' aria-label="Select {{ $appRow->reference }}">
                    </td>
                    <td style="vertical-align:top">
                      <a href="{{ route('transfers.show', $appRow->application_id) }}?tab=admission" style="font-weight:600"><code>{{ $appRow->reference }}</code></a>
                      <div class="muted small">{{ $appRow->programme }}</div>
                    </td>
                    <td style="vertical-align:top">
                      <strong>{{ $appRow->name }}</strong>
                      @if($appRow->institution_id)
                        <div class="s muted"><code>{{ $appRow->institution_id }}</code></div>
                      @endif
                    </td>
                    <td style="vertical-align:top">
                      <details>
                        <summary style="cursor:pointer;list-style:none;display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap">
                          <span class="tag info" style="font-size:11px">{{ $appRow->line_count }} {{ $appRow->line_count === 1 ? __('course') : __('courses') }}</span>
                          <span class="s muted" style="font-family:var(--mono)">{{ implode(', ', $appRow->course_codes) }}</span>
                          <span class="s" style="color:var(--color-primary, #0052ff);font-weight:600">{{ __('Details') }}</span>
                        </summary>
                        <div style="margin-top:8px;border:1px solid var(--color-line-hairline, #e2e8f0);border-radius:4px;background:#ffffff;padding:8px 10px">
                          @foreach($appRow->courses as $c)
                            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px;padding:4px 0;{{ !$loop->last ? 'border-bottom:1px solid #f1f5f9;' : '' }}font-size:12px">
                              <div>
                                <strong style="font-family:var(--mono)">{{ $c->course_code }}</strong> {{ $c->course_name }}
                                <div class="s muted" style="font-size:11px">{{ $c->mc_name }} @if($c->acr_reference)&middot; <code>{{ $c->acr_reference }}</code>@endif</div>
                              </div>
                              <span style="font-weight:600;white-space:nowrap">{{ mcms_num($c->credits) }} cr</span>
                            </div>
                          @endforeach
                        </div>
                      </details>
                    </td>
                    <td style="text-align:right;font-weight:700;vertical-align:top">{{ mcms_num($appRow->total_credits) }} cr</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </form>

        <script>
        (function () {
          var form = document.getElementById('bulk-redeem-form');
          var selectAll = document.getElementById('held-select-all');
          var progFilter = document.getElementById('held-prog-filter');
          var searchInp = document.getElementById('held-search');
          var sortSel = document.getElementById('held-sort');
          var tbody = document.getElementById('held-tbody');
          var visCount = document.getElementById('held-visible-count');
          var btn = document.getElementById('bulk-redeem-btn');
          var container = document.getElementById('bulk-line-inputs');
          if (!form || !btn || !container || !tbody) return;

          function allRows() {
            return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-held-row]'));
          }

          function visibleChks() {
            return allRows()
              .filter(function (tr) { return tr.style.display !== 'none'; })
              .map(function (tr) { return tr.querySelector('.held-app-chk'); })
              .filter(Boolean);
          }

          function syncSelection() {
            var vis = visibleChks();
            var checked = vis.filter(function (c) { return c.checked; });
            var allLineIds = [];
            checked.forEach(function (c) {
              try {
                var ids = JSON.parse(c.getAttribute('data-lines') || '[]');
                ids.forEach(function (id) { allLineIds.push(id); });
              } catch (e) {}
            });
            container.innerHTML = '';
            allLineIds.forEach(function (id) {
              var inp = document.createElement('input');
              inp.type = 'hidden';
              inp.name = 'line_ids[]';
              inp.value = String(id);
              container.appendChild(inp);
            });
            btn.disabled = allLineIds.length === 0;
            btn.textContent = '{{ __("Record for selected") }} (' + checked.length + ' · ' + allLineIds.length + ' {{ __("courses") }})';
            if (selectAll) {
              selectAll.checked = vis.length > 0 && checked.length === vis.length;
            }
            if (visCount) {
              visCount.textContent = vis.length + ' ' + (vis.length === 1 ? '{{ __("row") }}' : '{{ __("rows") }}');
            }
          }

          function applyFilterAndSort() {
            var q = (searchInp ? searchInp.value : '').trim().toLowerCase();
            var p = progFilter ? progFilter.value : '';
            var rows = allRows();

            rows.forEach(function (tr) {
              var matchProg = !p || tr.getAttribute('data-prog') === p;
              var matchQ = !q || (tr.getAttribute('data-search') || '').indexOf(q) !== -1;
              var show = matchProg && matchQ;
              tr.style.display = show ? '' : 'none';
              if (!show) {
                var c = tr.querySelector('.held-app-chk');
                if (c) c.checked = false;
              }
            });

            if (sortSel) {
              var mode = sortSel.value;
              rows.sort(function (a, b) {
                if (mode === 'name_asc') return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
                if (mode === 'credits_desc') return parseFloat(b.getAttribute('data-credits') || '0') - parseFloat(a.getAttribute('data-credits') || '0');
                if (mode === 'courses_desc') return parseInt(b.getAttribute('data-courses') || '0', 10) - parseInt(a.getAttribute('data-courses') || '0', 10);
                return (a.getAttribute('data-ref') || '').localeCompare(b.getAttribute('data-ref') || '');
              });
              rows.forEach(function (tr) { tbody.appendChild(tr); });
            }
            syncSelection();
          }

          if (selectAll) {
            selectAll.addEventListener('change', function () {
              visibleChks().forEach(function (c) { c.checked = selectAll.checked; });
              syncSelection();
            });
          }
          form.querySelectorAll('.held-app-chk').forEach(function (c) {
            c.addEventListener('change', syncSelection);
          });
          if (progFilter) progFilter.addEventListener('change', applyFilterAndSort);
          if (searchInp) searchInp.addEventListener('input', applyFilterAndSort);
          if (sortSel) sortSel.addEventListener('change', applyFilterAndSort);
          syncSelection();
        })();
        </script>
      @else
        <div class="table-wrap">
          <table id="in-use" style="margin:0">
            <thead>
              <tr>
                <th>{{ __('Application') }}</th>
                <th>{{ __('Participant') }}</th>
                <th>{{ __('Courses Held') }}</th>
                <th style="text-align:right">{{ __('Total Credits') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($heldApps as $appRow)
                <tr>
                  <td><b><code>{{ $appRow->reference }}</code></b><div class="muted small">{{ $appRow->programme }}</div></td>
                  <td>{{ $appRow->name }}</td>
                  <td>{{ $appRow->line_count }} {{ $appRow->line_count === 1 ? __('course') : __('courses') }} ({{ implode(', ', $appRow->course_codes) }})</td>
                  <td style="text-align:right;font-weight:600">{{ mcms_num($appRow->total_credits) }} cr</td>
                </tr>
              @empty
                <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('No credit is held.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  <div data-pane="admissions" @if(!count($admissions)) data-empty @endif>
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Programme admissions recorded') }}</h2>
        <span class="r"><span class="tag ok">{{ count($admissions) }} {{ __('admissions') }}</span></span>
      </div>
      <div class="bd" style="padding:0">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
          <p class="muted small" style="margin:0">{{ __('Admission to a degree programme is recorded separately by Pejabat Pendaftar (Academic Registrar). Transfer approval transfers credit only and does not imply university admission.') }}</p>
        </div>
        <div class="table-wrap">
          <table id="admissions" style="margin:0">
            <thead>
              <tr>
                <th>{{ __('Participant') }}</th>
                <th>{{ __('Admitted Programme') }}</th>
                <th>{{ __('Intake') }}</th>
                <th>{{ __('Registrar Evidence Reference') }}</th>
                <th style="text-align:right">{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
            @forelse($admissions as $ad)
              <tr>
                <td><b>{{ $ad->name }}</b></td>
                <td><span class="tag info">{{ $ad->code }}</span></td>
                <td>{{ $ad->intake }}</td>
                <td><code>{{ $ad->evidence_ref }}</code></td>
                <td style="text-align:right"><span class="tag ok">{{ __('Admitted') }}</span></td>
              </tr>
            @empty
              <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No admissions recorded yet.') }}</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
