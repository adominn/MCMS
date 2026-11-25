@extends('layouts.app')
@section('title', $v->name_en)
@section('content')
@php
  $inReview = $v->status === 'in_review';
  $isDdApprover = auth()->user()->hasAnyRole('deputy_dean', 'ptj_authority');
  $isCodl = auth()->user()->hasAnyRole('codl_admin');
  $configuredSenateRole = (string) app(\App\Modules\Core\Settings::class)->get('approval.route.senate');
  $canRecordSenate = in_array($configuredSenateRole, ['academic_authority', 'codl_admin', ''], true)
    ? auth()->user()->hasAnyRole('academic_authority', 'codl_admin')
    : auth()->user()->hasAnyRole($configuredSenateRole);

  $endorsedDate = !empty($v->endorsed_at) ? \Carbon\Carbon::parse($v->endorsed_at)->format('d M Y') : null;
  if (!$endorsedDate && in_array($v->status, ['endorsed', 'codl_verified', 'approved'])) {
    $eDec = \Illuminate\Support\Facades\DB::table('approval_decisions')->where('mc_version_id', $v->id)->where('stage', 'endorsement')->where('decision', 'endorsed')->orderByDesc('id')->first();
    $endorsedDate = $eDec ? \Carbon\Carbon::parse($eDec->meeting_date ?: $eDec->created_at)->format('d M Y') : null;
  }

  $cDec = \Illuminate\Support\Facades\DB::table('approval_decisions')->where('mc_version_id', $v->id)->where('stage', 'codl_verification')->where('decision', 'verified')->orderByDesc('id')->first();
  $codlVerifiedDate = $cDec ? \Carbon\Carbon::parse($cDec->meeting_date ?: $cDec->created_at)->format('d M Y') : null;

  $approvedDate = !empty($v->approved_at) ? \Carbon\Carbon::parse($v->approved_at)->format('d M Y') : null;
  if (!$approvedDate && $v->status === 'approved') {
    $sDec = \Illuminate\Support\Facades\DB::table('approval_decisions')->where('mc_version_id', $v->id)->where('stage', 'senate')->whereIn('decision', ['approved', 'approved_conditions'])->orderByDesc('id')->first();
    $approvedDate = $sDec ? \Carbon\Carbon::parse($sDec->meeting_date ?: $sDec->created_at)->format('d M Y') : (!empty($v->effective_date) ? \Carbon\Carbon::parse($v->effective_date)->format('d M Y') : null);
  }

  $proposalSteps = [
    [
      'label' => __('1. Lecturer Submission'),
      'meta' => !empty($v->submitted_at) ? \Carbon\Carbon::parse($v->submitted_at)->format('d M Y') : (!empty($v->created_at) ? \Carbon\Carbon::parse($v->created_at)->format('d M Y') : '—'),
      'status' => in_array($v->status, ['submitted', 'in_review', 'endorsed', 'codl_verified', 'approved']) ? 'completed' : 'active',
      'num' => '01',
    ],
    [
      'label' => __('2. Deputy Dean Endorsement'),
      'meta' => in_array($v->status, ['endorsed', 'codl_verified', 'approved']) ? ($endorsedDate ?: __('Endorsed')) : (in_array($v->status, ['submitted', 'in_review']) ? __('In Progress') : __('Pending')),
      'status' => in_array($v->status, ['endorsed', 'codl_verified', 'approved']) ? 'completed' : (in_array($v->status, ['submitted', 'in_review']) ? 'active' : 'pending'),
      'num' => '02',
    ],
    [
      'label' => __('3. CODL Verification'),
      'meta' => in_array($v->status, ['codl_verified', 'approved']) ? ($codlVerifiedDate ?: __('Verified')) : ($v->status === 'endorsed' ? __('In Progress') : __('Pending')),
      'status' => in_array($v->status, ['codl_verified', 'approved']) ? 'completed' : ($v->status === 'endorsed' ? 'active' : 'pending'),
      'num' => '03',
    ],
    [
      'label' => __('4. Senate Approval'),
      'meta' => $v->status === 'approved' ? ($approvedDate ?: __('Approved')) : ($v->status === 'codl_verified' ? __('In Progress') : __('Pending')),
      'status' => $v->status === 'approved' ? 'completed' : ($v->status === 'codl_verified' ? 'active' : 'pending'),
      'num' => '04',
    ],
  ];

  $blocks = count($checks['blocking']);
  $warns = count($checks['warnings']);
  $assignCount = $assign->count();
  $commentsCount = $comments->count();
  $decisionsCount = $decisions->count();
  $canAssign = $isDdApprover && in_array($v->status, ['submitted', 'in_review'], true);
@endphp

<x-breadcrumb :crumbs="[
  'Proposals and approval' => route('approvals'),
  $v->name_en => null,
]" />

<div class="title" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:16px">
  <div style="min-width:0;flex:1">
    <h1 style="margin:0 0 6px">{{ $v->name_en }}</h1>
    <div class="sub" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <x-cat :c="$v->category" />
      <span>&middot;</span>
      <span>{{ __('Category :status', ['status' => __(['proposed' => 'proposed', 'verified' => 'verified', 'confirmed' => 'confirmed'][$v->category_status] ?? $v->category_status)]) }}</span>
      <span>&middot;</span>
      <x-pill :s="__($labels[$v->status] ?? ucfirst(str_replace('_', ' ', $v->status)))" />
      <span>&middot;</span>
      <span>{{ __('Version :num', ['num' => $v->version_no]) }}@if($v->supersedes_id) ({{ __(['revision' => 'Revision', 'reoffer' => 'Re-offer'][$v->proposal_type] ?? ucfirst($v->proposal_type)) }} {{ __('of version :num', ['num' => $v->version_no - 1]) }})@endif</span>
    </div>
  </div>
  <div class="act" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex-shrink:0">
    <a class="btn ter sm" href="{{ route('approvals') }}">&larr; {{ __('All proposals') }}</a>
    <a class="btn ter sm" href="{{ route('proposals.print', $v->id) }}" target="_blank">{{ __('Print proposal') }}</a>
    <a class="btn ter sm" href="{{ route('approvals.pack', $v->id) }}">{{ __('Decision pack') }}</a>
    @if($canRecordSenate && in_array($v->status, ['endorsed', 'codl_verified'], true))
      <a class="btn sm" href="{{ route('approvals.senate', $v->id) }}">{{ __('Record Senate decision') }}</a>
    @endif
  </div>
</div>

{{-- 4-Stage Governance Stepper + Live Readiness Strip --}}
<div class="panel" style="margin-bottom:18px;padding:16px 20px">
  <x-stepper :steps="$proposalSteps" />
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px;padding-top:12px;border-top:1px solid var(--hairline);font-size:12.5px">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
      <span>
        <span class="tag {{ $blocks ? 'err' : 'ok' }}">{{ $blocks ? __(':count blocking', ['count' => $blocks]) : __('Checks clear') }}</span>
        @if($warns > 0)<span class="tag warn" style="margin-left:4px">{{ $warns }} {{ $warns === 1 ? __('warning') : __('warnings') }}</span>@endif
      </span>
      <span class="muted">
        {{ __('Academic reviews:') }} <strong style="color:var(--ink)">{{ $submittedReviews }} / {{ $assign->where('kind', 'reviewer')->count() }}</strong> {{ __('submitted') }}
      </span>
      @if($blocking > 0)
        <span class="tag err">{{ $blocking }} {{ __('unresolved blocking comment(s)') }}</span>
      @endif
    </div>
    @if(in_array($v->status, ['submitted', 'in_review']))
      <span class="muted">
        @if($blocks)
          {{ __('Blocking errors must be resolved before endorsement.') }}
        @elseif($isDdApprover && $assignCount === 0)
          {{ __('Next step: assign at least one colleague reviewer.') }}
        @elseif($isDdApprover && $submittedReviews === 0)
          {{ __('Waiting for assigned academic review(s) before endorsement.') }}
        @elseif($isDdApprover)
          {{ __('Academic review received. Ready to record your endorsement.') }}
        @else
          {{ __('Waiting for peer reviews and Deputy Dean endorsement.') }}
        @endif
      </span>
    @endif
  </div>
</div>

{{-- Tabbed Approval Workspace (D-67 Bounded Page + D-70 Creation/Dependency Order) --}}
<div data-tabs data-auto id="approval-workspace-tabs">
  <nav class="tabs" role="tablist" aria-label="{{ __('Approval sections') }}" style="margin-bottom:20px">
    <button type="button" role="tab" data-tab="overview" class="on">
      {{ __('Overview & checks') }}
      @if($blocks > 0)<span class="c">{{ $blocks }}</span>@endif
    </button>
    <button type="button" role="tab" data-tab="reviewers">
      {{ __('Reviewers & reviews') }}
      <span class="c">{{ $assignCount }}</span>
    </button>
    <button type="button" role="tab" data-tab="comments">
      {{ __('Comments') }}
      @if($commentsCount > 0)<span class="c">{{ $commentsCount }}</span>@endif
    </button>
    <button type="button" role="tab" data-tab="decision">
      {{ __('Decision & history') }}
      @if($decisionsCount > 0)<span class="c">{{ $decisionsCount }}</span>@endif
    </button>
  </nav>

  {{-- ==================== TAB 1: OVERVIEW & SYSTEM CHECKS ==================== --}}
  <div data-pane="overview">
    <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:20px;align-items:start">
      <div>
        @include('approval._summary')
      </div>

      <aside class="panel" id="checks" style="position:sticky;top:20px">
        <div class="hd">
          <h2>{{ __('System checks') }}</h2>
          <span class="r">
            <span class="tag {{ $blocks ? 'err' : 'ok' }}">{{ $blocks ? __(':blocks blocking', ['blocks' => $blocks]) : __('Clear') }}</span>
          </span>
        </div>
        <div class="bd" style="display:flex;flex-direction:column;gap:10px">
          <div class="small muted">{{ __(':blocks blocking, :warns warnings', ['blocks' => $blocks, 'warns' => $warns]) }}</div>
          @forelse($checks['blocking'] as $c)
            <div style="font-size:13px;line-height:1.4">
              <span class="tag overdue">{{ __('Error') }}</span> {{ $c['msg'] }}
            </div>
          @empty
            <div style="font-size:13px">
              <span class="tag ok">{{ __('Clear') }}</span> {{ __('No blocking errors.') }}
            </div>
          @endforelse
          @foreach($checks['warnings'] as $c)
            <div style="font-size:13px;line-height:1.4">
              <span class="tag warn">{{ __('Warning') }}</span> {{ $c['msg'] }}
            </div>
          @endforeach
          @if($v->submit_response)
            <div style="border-top:1px solid var(--hairline);padding-top:10px;font-size:13px">
              <b>{{ __("Proposer's response to the warnings:") }}</b>
              <div class="muted" style="margin-top:4px">{{ $v->submit_response }}</div>
            </div>
          @endif
        </div>
      </aside>
    </div>
  </div>

  {{-- ==================== TAB 2: REVIEWERS & REVIEWS ==================== --}}
  <div data-pane="reviewers" hidden>
    <div class="panel" style="margin-bottom:20px">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
          <h2 style="margin:0">{{ __('Reviewers') }}</h2>
        </div>
        @if($canAssign)
          <button type="button" class="btn sm" id="btn-open-assign-reviewers">+ {{ __('Assign reviewers') }}</button>
        @endif
      </div>
      <div class="bd" style="padding:0">
        <table id="reviewers" data-plain>
          <thead>
            <tr>
              <th>{{ __('Name') }}</th>
              <th>{{ __('Role') }}</th>
              <th>{{ __('Due') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Recommendation') }}</th>
              <th style="text-align:right">{{ __('Action') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($assign as $a)
            @php
              $hasDetail = (in_array($a->status, ['submitted', 'superseded'], true) && $a->kind === 'reviewer' && !empty($a->recommendation));
              $recLabel = $a->recommendation ? __(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$a->recommendation] ?? ucfirst($a->recommendation)) : null;
              $reviewPayload = null;
              if ($hasDetail) {
                $parsedRatings = [];
                foreach (json_decode($a->ratings ?? '[]', true) ?: [] as $rk => $rv) {
                  $parsedRatings[] = [
                    'item' => __($items[$rk] ?? $rk),
                    'rating' => $rv['rating'] ?? 'satisfactory',
                    'rating_label' => ($rv['rating'] ?? '') === 'satisfactory' ? __('Satisfactory') : __('Needs amendment'),
                    'comment' => $rv['comment'] ?? '',
                    'blocking' => !empty($rv['blocking']),
                  ];
                }
                $reviewPayload = [
                  'reviewer' => $a->reviewer,
                  'role' => __('Colleague reviewer'),
                  'due' => \Carbon\Carbon::parse($a->due_on)->format('d/m/Y'),
                  'recommendation' => $a->recommendation,
                  'recommendation_label' => $recLabel,
                  'summary' => $a->summary ?? '',
                  'ratings' => $parsedRatings,
                ];
              }
            @endphp
            <tr @if($hasDetail) class="rev-detail-row" data-review-json="{{ json_encode($reviewPayload, JSON_UNESCAPED_UNICODE) }}" style="cursor:pointer" @endif>
              <td><b>{{ $a->reviewer }}</b></td>
              <td>{{ $a->kind === 'expert' ? __('Industry expert (comments only)') : __('Reviewer') }}</td>
              <td>{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</td>
              <td>{{ __(['invited' => 'Invited', 'in_progress' => 'In progress', 'submitted' => 'Submitted', 'declined' => 'Declined (conflict of interest)'][$a->status] ?? ucfirst($a->status)) }}</td>
              <td>
                @if($recLabel)
                  <x-pill :s="$recLabel" />
                @else
                  <span class="muted">&mdash;</span>
                @endif
              </td>
              <td style="text-align:right">
                @if($hasDetail)
                  <button type="button" class="btn ter sm btn-view-review">{{ __('View review') }}</button>
                @else
                  <span class="muted small">&mdash;</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="muted" style="text-align:center;padding:24px">
                {{ __('No reviewers assigned yet.') }}
                @if($canAssign)
                  <div style="margin-top:10px">
                    <button type="button" class="btn ter sm" onclick="document.getElementById('btn-open-assign-reviewers')?.click()">+ {{ __('Assign reviewers') }}</button>
                  </div>
                @endif
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Structured Review Detail Modal --}}
    <div id="modal-review-detail" hidden role="dialog" aria-modal="true" aria-labelledby="modal-review-title" style="position:fixed;inset:0;z-index:1100;background:rgba(9,10,15,.48);display:flex;align-items:center;justify-content:center;padding:20px">
      <div style="width:100%;max-width:780px;max-height:88vh;display:flex;flex-direction:column;background:#ffffff;border:1px solid var(--color-card-border, #090a0f);border-radius:8px;box-shadow:0 20px 50px rgba(9,10,15,.22);overflow:hidden">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--hairline);background:var(--surface, #f8fafc)">
          <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
              <h2 id="modal-review-title" style="margin:0;font-size:16px;font-weight:700;color:var(--ink)">{{ __('Review details') }}</h2>
              <span id="modal-review-rec" class="tag"></span>
            </div>
            <div id="modal-review-meta" class="small muted" style="margin-top:4px"></div>
          </div>
          <button type="button" class="btn ter sm" id="btn-close-review-detail" aria-label="{{ __('Close') }}">&times;</button>
        </div>
        <div style="padding:18px 20px;overflow-y:auto;display:flex;flex-direction:column;gap:16px">
          <div style="padding:12px 14px;background:var(--surface, #f8fafc);border:1px solid var(--hairline);border-radius:4px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:4px">{{ __('Overall summary') }}</div>
            <div id="modal-review-summary" style="font-size:13.5px;color:var(--ink);line-height:1.5"></div>
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:6px">{{ __('Item-by-item evaluation') }}</div>
            <div style="border:1px solid var(--hairline);border-radius:4px;overflow-x:auto">
              <table data-plain style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                  <tr>
                    <th style="width:34%">{{ __('Item') }}</th>
                    <th style="width:18%">{{ __('Rating') }}</th>
                    <th style="width:12%;text-align:center">{{ __('Blocking') }}</th>
                    <th style="width:36%">{{ __('Comment') }}</th>
                  </tr>
                </thead>
                <tbody id="modal-review-rows"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;padding:12px 20px;border-top:1px solid var(--hairline);background:var(--surface, #f8fafc)">
          <button type="button" class="btn ter sm" id="btn-close-review-detail-foot">{{ __('Close') }}</button>
        </div>
      </div>
    </div>

    <script>
    (function () {
      var modal = document.getElementById('modal-review-detail');
      if (!modal || modal.dataset.bound) return;
      modal.dataset.bound = '1';
      var titleEl = document.getElementById('modal-review-title');
      var recEl = document.getElementById('modal-review-rec');
      var metaEl = document.getElementById('modal-review-meta');
      var sumEl = document.getElementById('modal-review-summary');
      var tbodyEl = document.getElementById('modal-review-rows');

      function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; });
      }
      function openReview(data) {
        if (!data) return;
        titleEl.textContent = @json(__('Review by')) + ' ' + data.reviewer;
        recEl.textContent = data.recommendation_label || '';
        recEl.className = 'tag ' + (data.recommendation === 'support' ? 'ok' : (data.recommendation === 'not_support' ? 'overdue' : 'warn'));
        metaEl.textContent = data.role + ' · ' + @json(__('Due')) + ' ' + data.due;
        sumEl.textContent = data.summary || '–';
        tbodyEl.innerHTML = (data.ratings || []).map(function (r) {
          var rCls = r.rating === 'satisfactory' ? 'ok' : 'warn';
          var blk = r.blocking ? '<span class="tag overdue">' + escHtml(@json(__('Blocking'))) + '</span>' : '<span class="muted">&ndash;</span>';
          var cmt = r.comment ? escHtml(r.comment) : '<span class="muted">&ndash;</span>';
          return '<tr><td><b>' + escHtml(r.item) + '</b></td><td><span class="tag ' + rCls + '">' + escHtml(r.rating_label) + '</span></td><td style="text-align:center">' + blk + '</td><td>' + cmt + '</td></tr>';
        }).join('');
        modal.hidden = false;
      }
      function closeReview() { modal.hidden = true; }

      document.querySelectorAll('.rev-detail-row').forEach(function (tr) {
        tr.addEventListener('click', function () {
          try { openReview(JSON.parse(tr.getAttribute('data-review-json'))); } catch (e) {}
        });
      });
      var c1 = document.getElementById('btn-close-review-detail');
      var c2 = document.getElementById('btn-close-review-detail-foot');
      if (c1) c1.addEventListener('click', closeReview);
      if (c2) c2.addEventListener('click', closeReview);
      modal.addEventListener('click', function (e) { if (e.target === modal) closeReview(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeReview(); });
    })();
    </script>
  </div>

  {{-- ==================== TAB 3: COMMENTS ==================== --}}
  <div data-pane="comments" hidden>
    @php
      $ddCmtGroups = $comments->groupBy(fn ($c) => ($c->review_assignment_id ?? 'other') . '|' . $c->author_id)->values();
      $ddOpenTotal = $comments->where('status', 'open')->count();
      $ddAnsTotal = $comments->where('status', 'answered')->count();
      $ddResTotal = $comments->where('status', 'resolved')->count();
      $defaultResolveReason = __('The proposer has answered and addressed the requested amendments.');
    @endphp

    <div class="panel">
      <div class="hd" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
          <h2 style="margin:0">{{ __('Comments') }}</h2>
          @if($comments->isNotEmpty())
            <div class="small muted" style="margin-top:2px">
              {{ $comments->count() }} {{ $comments->count() === 1 ? __('comment') : __('comments') }}
              @if($ddOpenTotal > 0) &middot; {{ $ddOpenTotal }} {{ __('open') }}@endif
              @if($ddAnsTotal > 0) &middot; {{ $ddAnsTotal }} {{ __('answered') }}@endif
              @if($ddResTotal > 0) &middot; {{ $ddResTotal }} {{ __('resolved') }}@endif
            </div>
          @endif
        </div>
      </div>

      <div class="bd" style="padding:0">
        @if($ddCmtGroups->isNotEmpty())
          <table id="comments" data-plain style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
              <tr>
                <th>{{ __('From') }}</th>
                <th>{{ __('Role') }}</th>
                <th>{{ __('Round') }}</th>
                <th>{{ __('Summary') }}</th>
                <th>{{ __('Status') }}</th>
                <th style="text-align:right">{{ __('Action') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ddCmtGroups as $gItems)
                @php
                  $first = $gItems->first();
                  $gOpen = $gItems->where('status', 'open')->count();
                  $gAns = $gItems->where('status', 'answered')->count();
                  $gBlocking = $gItems->where('status', '!=', 'resolved')->where('blocking', true)->count();
                  $isSuperseded = ($first->assignment_status ?? null) === 'superseded';
                  $isExpert = ($first->assignment_kind ?? null) === 'expert';
                  $mId = 'modal-dd-cmt-' . ($first->review_assignment_id ?? 'other') . '-' . $first->author_id;
                @endphp
                <tr class="dd-cmt-summary-row" data-open-dd-modal="{{ $mId }}" style="cursor:pointer">
                  <td><b>{{ $first->author }}</b></td>
                  <td>{{ $isExpert ? __('Industry expert') : __('Reviewer') }}</td>
                  <td>
                    @if($isSuperseded)
                      <span class="tag warn">{{ __('Previous round') }}</span>
                    @else
                      <span class="tag info">{{ __('Current') }}</span>
                    @endif
                  </td>
                  <td>
                    {{ $gItems->count() }} {{ $gItems->count() === 1 ? __('comment') : __('comments') }}
                    @if($gBlocking > 0)
                      <span class="tag overdue" style="margin-left:6px">{{ $gBlocking }} {{ __('blocking') }}</span>
                    @endif
                  </td>
                  <td>
                    @if($gAns > 0)
                      <span class="tag info">{{ $gAns }} {{ __('ready to resolve') }}</span>
                    @elseif($gOpen > 0)
                      <span class="tag warn">{{ $gOpen }} {{ __('waiting response') }}</span>
                    @else
                      <span class="tag ok">{{ __('All resolved') }}</span>
                    @endif
                  </td>
                  <td style="text-align:right">
                    <button type="button" class="btn {{ ($isDdApprover && $gAns > 0) ? '' : 'ter' }} sm" data-open-dd-modal="{{ $mId }}">
                      {{ ($isDdApprover && $gAns > 0) ? __('Review & resolve') : __('View comments') }}
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="muted" style="text-align:center;padding:24px">
            <div>{{ __('No comments.') }}</div>
            <div class="small" style="margin-top:4px">{{ __('Tracked comments appear here when a reviewer marks an item as "Needs amendment" or an industry expert submits feedback.') }}</div>
          </div>
        @endif
      </div>
    </div>

    {{-- Per-reviewer / round Deputy Dean Comment & Resolution Modals --}}
    @foreach($ddCmtGroups as $gItems)
      @php
        $first = $gItems->first();
        $gOpen = $gItems->where('status', 'open')->count();
        $gAns = $gItems->where('status', 'answered')->count();
        $gRes = $gItems->where('status', 'resolved')->count();
        $gBlocking = $gItems->where('status', '!=', 'resolved')->where('blocking', true)->count();
        $isSuperseded = ($first->assignment_status ?? null) === 'superseded';
        $isExpert = ($first->assignment_kind ?? null) === 'expert';
        $mId = 'modal-dd-cmt-' . ($first->review_assignment_id ?? 'other') . '-' . $first->author_id;
        $ansIds = $gItems->where('status', 'answered')->pluck('id')->all();
      @endphp
      <div id="{{ $mId }}" class="dd-cmt-modal" hidden role="dialog" aria-modal="true" aria-labelledby="{{ $mId }}-title" style="position:fixed;inset:0;z-index:1100;background:rgba(9,10,15,.48);display:flex;align-items:center;justify-content:center;padding:20px">
        <div style="width:100%;max-width:920px;max-height:88vh;display:flex;flex-direction:column;background:#ffffff;border:1px solid var(--color-card-border, #090a0f);border-radius:8px;box-shadow:0 20px 50px rgba(9,10,15,.22);overflow:hidden">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--hairline);background:var(--surface, #f8fafc)">
            <div>
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <h2 id="{{ $mId }}-title" style="margin:0;font-size:16px;font-weight:700;color:var(--ink)">{{ __('Comments from :name', ['name' => $first->author]) }}</h2>
                <span class="tag">{{ $isExpert ? __('Industry expert') : __('Reviewer') }}</span>
                @if($isSuperseded)<span class="tag warn">{{ __('Previous round (Superseded)') }}</span>@endif
              </div>
              <div class="small muted" style="margin-top:4px">
                {{ $gItems->count() }} {{ $gItems->count() === 1 ? __('comment') : __('comments') }}
                @if($gAns > 0) &middot; {{ $gAns }} {{ __('answered') }}@endif
                @if($gBlocking > 0) &middot; {{ $gBlocking }} {{ __('blocking') }}@endif
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
              <div style="display:inline-flex;border:1px solid var(--color-card-border, #090a0f);border-radius:4px;overflow:hidden;font-size:12px">
                <button type="button" class="dd-cmt-fbtn on" data-dd-filter="all" style="padding:4px 9px;border:0;border-right:1px solid var(--hairline);background:var(--color-primary, #0052ff);color:#fff;font-weight:600;cursor:pointer">{{ __('All') }} ({{ $gItems->count() }})</button>
                <button type="button" class="dd-cmt-fbtn" data-dd-filter="open" style="padding:4px 9px;border:0;border-right:1px solid var(--hairline);background:#fff;color:var(--ink);font-weight:500;cursor:pointer">{{ __('Open') }} ({{ $gOpen }})</button>
                <button type="button" class="dd-cmt-fbtn" data-dd-filter="answered" style="padding:4px 9px;border:0;border-right:1px solid var(--hairline);background:#fff;color:var(--ink);font-weight:500;cursor:pointer">{{ __('Answered') }} ({{ $gAns }})</button>
                <button type="button" class="dd-cmt-fbtn" data-dd-filter="resolved" style="padding:4px 9px;border:0;background:#fff;color:var(--ink);font-weight:500;cursor:pointer">{{ __('Resolved') }} ({{ $gRes }})</button>
              </div>
              <button type="button" class="btn ter sm dd-cmt-close" aria-label="{{ __('Close') }}">&times;</button>
            </div>
          </div>

          <div style="padding:0;overflow-y:auto;flex:1">
            <table data-plain style="width:100%;border-collapse:collapse;font-size:13px">
              <thead>
                <tr>
                  <th style="width:34%">{{ __('Item & comment') }}</th>
                  <th style="width:14%">{{ __('Status') }}</th>
                  <th style="width:52%">{{ __('Response & resolution') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($gItems as $c)
                  @php
                    $parts = explode(': ', (string) $c->text, 2);
                    $itemTitle = count($parts) === 2 ? $parts[0] : null;
                    $itemBody = count($parts) === 2 ? $parts[1] : $c->text;
                    $stCls = $c->status === 'resolved' ? 'ok' : ($c->status === 'answered' ? 'info' : 'warn');
                    $stLbl = __(['open' => 'Open', 'answered' => 'Answered', 'resolved' => 'Resolved'][$c->status] ?? ucfirst($c->status));
                  @endphp
                  <tr class="dd-cmt-row" data-dd-status="{{ $c->status }}">
                    <td style="vertical-align:top">
                      @if($itemTitle)
                        <div style="font-weight:700;color:var(--ink);margin-bottom:2px">{{ $itemTitle }}</div>
                        <div>{{ $itemBody }}</div>
                      @else
                        <div>{{ $itemBody }}</div>
                      @endif
                    </td>
                    <td style="vertical-align:top">
                      <div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px">
                        <span class="tag {{ $stCls }}">{{ $stLbl }}</span>
                        @if($c->blocking)<span class="tag overdue">{{ __('Blocking') }}</span>@endif
                      </div>
                    </td>
                    <td style="vertical-align:top">
                      @if($c->response)
                        <div style="margin-bottom:6px"><b>{{ __('Response:') }}</b> {{ $c->response }}</div>
                      @endif
                      @if($c->resolve_reason)
                        <div class="muted small">{{ __('Resolved: :reason', ['reason' => $c->resolve_reason]) }}</div>
                      @endif
                      @if($isDdApprover && $c->status === 'answered')
                        <form method="post" action="{{ route('approvals.resolve', [$v->id, $c->id]) }}" style="display:flex;gap:6px;align-items:center;margin:0" data-inplace>
                          @csrf
                          <input type="text" name="reason" class="dd-resolve-input" placeholder="{{ __('Resolution reason...') }}" required minlength="5" aria-label="Reason to resolve comment {{ $c->id }}" style="flex:1;min-width:150px;height:30px;font-size:12.5px">
                          <button type="submit" class="btn ter sm" style="white-space:nowrap">{{ __('Mark resolved') }}</button>
                        </form>
                      @elseif($c->status === 'open' && $c->blocking)
                        <span class="muted small">{{ __("Waiting for the lecturer's response") }}</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:12px 20px;border-top:1px solid var(--hairline);background:var(--surface, #f8fafc)">
            @if($isDdApprover && $gAns > 0)
              <form method="post" action="{{ route('approvals.resolve_all', $v->id) }}" style="display:flex;align-items:center;gap:8px;flex:1;min-width:260px;flex-wrap:wrap;margin:0" data-inplace>
                @csrf
                @foreach($ansIds as $aid)
                  <input type="hidden" name="comment_ids[]" value="{{ $aid }}">
                @endforeach
                <input type="text" name="reason" class="dd-bulk-reason" value="{{ $defaultResolveReason }}" required minlength="5" maxlength="500" aria-label="{{ __('Shared resolution reason for all answered comments') }}" style="flex:1;min-width:220px;height:32px;font-size:12.5px">
                <button type="button" class="btn ter sm dd-fill-same-btn" data-modal-id="{{ $mId }}" title="{{ __('Copy this reason into every answered row above') }}">{{ __('Fill all rows') }}</button>
                <button type="submit" class="btn sm">{{ __('Mark all answered resolved (:count)', ['count' => $gAns]) }}</button>
              </form>
            @else
              <div></div>
            @endif
            <button type="button" class="btn ter sm dd-cmt-close">{{ __('Close') }}</button>
          </div>
        </div>
      </div>
    @endforeach

    @if($ddCmtGroups->isNotEmpty())
      <script>
      (function () {
        var STORE_KEY = 'mcms_open_dd_cmt_{{ $v->id }}';
        function openDdModal(id) {
          var m = document.getElementById(id);
          if (!m) return;
          document.querySelectorAll('.dd-cmt-modal').forEach(function (el) { el.hidden = true; });
          m.hidden = false;
          try { sessionStorage.setItem(STORE_KEY, id); } catch (e) {}
        }
        function closeDdAll() {
          document.querySelectorAll('.dd-cmt-modal').forEach(function (el) { el.hidden = true; });
          try { sessionStorage.removeItem(STORE_KEY); } catch (e) {}
        }

        document.querySelectorAll('[data-open-dd-modal]').forEach(function (el) {
          el.addEventListener('click', function (e) {
            e.stopPropagation();
            openDdModal(el.getAttribute('data-open-dd-modal'));
          });
        });

        document.querySelectorAll('.dd-cmt-modal').forEach(function (modal) {
          modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target.closest('.dd-cmt-close')) closeDdAll();
          });
          modal.querySelectorAll('.dd-cmt-fbtn').forEach(function (btn) {
            btn.addEventListener('click', function () {
              var f = btn.getAttribute('data-dd-filter');
              modal.querySelectorAll('.dd-cmt-fbtn').forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('on', on);
                b.style.background = on ? 'var(--color-primary, #0052ff)' : '#ffffff';
                b.style.color = on ? '#ffffff' : 'var(--ink)';
                b.style.fontWeight = on ? '600' : '500';
              });
              modal.querySelectorAll('.dd-cmt-row').forEach(function (row) {
                row.hidden = !(f === 'all' || row.getAttribute('data-dd-status') === f);
              });
            });
          });
          var fillBtn = modal.querySelector('.dd-fill-same-btn');
          if (fillBtn) {
            fillBtn.addEventListener('click', function () {
              var shared = modal.querySelector('.dd-bulk-reason');
              var val = shared ? shared.value.trim() : '';
              if (!val) return;
              modal.querySelectorAll('.dd-resolve-input').forEach(function (inp) {
                inp.value = val;
              });
            });
          }
        });

        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') closeDdAll();
        });

        try {
          var prev = sessionStorage.getItem(STORE_KEY);
          if (prev && document.getElementById(prev)) openDdModal(prev);
        } catch (e) {}
      })();
      </script>
    @endif
  </div>

  {{-- ==================== TAB 4: DECISION & HISTORY ==================== --}}
  <div data-pane="decision" hidden>
    @if($isDdApprover && in_array($v->status, ['submitted', 'in_review']))
      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Decision') }}</h2></div>
        <div class="bd">
          @if($inReview)
            <form method="post" action="{{ route('approvals.endorse', $v->id) }}" style="margin-bottom:24px;border-bottom:1px solid var(--hairline);padding-bottom:20px" data-inplace>
              @csrf
              <h3 style="margin-top:0">{{ __('Endorse and verify the category') }}</h3>
              <p class="muted s" style="margin-top:0">
                {{ __('Proposed category: :cat. Endorsing records the category you verify, separately from the proposed one.', ['cat' => strtoupper($v->category)]) }}
              </p>
              <div style="margin:14px 0">
                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                  <input type="checkbox" name="category_verified" value="1">
                  <span>{{ __('I have checked the category and confirm :cat.', ['cat' => strtoupper($v->category)]) }}</span>
                </label>
              </div>
              <div class="field" style="margin-bottom:14px;max-width:540px">
                <label for="comments">{{ __('Comments (optional)') }}</label>
                <input type="text" id="comments" name="comments" placeholder="{{ __('Endorsement comments for Senate') }}">
              </div>
              @php
                $advisoryConcerns = $assign->where('status', 'submitted')->where('kind', 'reviewer')->whereIn('recommendation', ['amend', 'not_support'])->count();
              @endphp
              @if($advisoryConcerns > 0)
                <div class="note warn" style="margin-bottom:12px">
                  <p style="margin:0">{{ __('Advisory notice: :count reviewer(s) recommended "Amend" or "Do not support". Review their detailed feedback or use "Request amendment" below before endorsing.', ['count' => $advisoryConcerns]) }}</p>
                </div>
              @endif
              @if($blocking > 0)
                <div class="note warn" style="margin-bottom:12px"><p style="margin:0">{{ __('Endorse will be refused: :count blocking comment(s) are not resolved.', ['count' => $blocking]) }}</p></div>
              @endif
              @if($submittedReviews === 0)
                <div class="note warn" style="margin-bottom:12px"><p style="margin:0">{{ __('Endorse will be refused: no academic review has been submitted yet.') }}</p></div>
              @endif
              <button type="submit" class="btn">{{ __('Endorse') }}</button>
            </form>
          @else
            <div class="note info" style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
              <span>{{ __('Assign at least one colleague reviewer and receive an academic review before recording endorsement.') }}</span>
              <button type="button" class="btn ter sm" onclick="document.querySelector('#approval-workspace-tabs [data-tab=reviewers]')?.click()">{{ __('Go to Reviewers & reviews') }}</button>
            </div>
          @endif

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px">
            <form method="post" action="{{ route('approvals.sendback', [$v->id, 'return']) }}" style="padding:14px;border:1px solid var(--hairline);border-radius:4px;background:var(--surface, #f8fafc)" data-inplace>
              @csrf
              <h3 style="margin:0 0 10px;font-size:14.5px">{{ __('Request amendment') }}</h3>
              <div class="field" style="margin-bottom:12px">
                <label for="r1">{{ __('Reason') }}</label>
                <input type="text" id="r1" name="reason" required minlength="5" placeholder="{{ __('Specify amendments required') }}" style="width:100%;max-width:100%">
              </div>
              <button type="submit" class="btn ter sm">{{ __('Return for amendment') }}</button>
            </form>

            <form method="post" action="{{ route('approvals.sendback', [$v->id, 'reject']) }}" style="padding:14px;border:1px solid var(--hairline);border-radius:4px;background:var(--surface, #f8fafc)" data-inplace>
              @csrf
              <h3 style="margin:0 0 10px;font-size:14.5px">{{ __('Record not supported') }}</h3>
              <div class="field" style="margin-bottom:12px">
                <label for="r2">{{ __('Reason') }}</label>
                <input type="text" id="r2" name="reason" required minlength="5" placeholder="{{ __('Reason for not supporting') }}" style="width:100%;max-width:100%">
              </div>
              <button type="submit" class="btn danger sm">{{ __('Not supported') }}</button>
            </form>
          </div>
        </div>
      </div>
    @endif

    @if($v->status === 'endorsed')
      <div class="panel" style="margin-bottom:20px" id="codl-verification">
        <div class="hd"><h2>{{ __('CODL Verification') }}</h2></div>
        <div class="bd">
          @if($isCodl)
            <form method="post" action="{{ route('approvals.verify', $v->id) }}" data-inplace>
              @csrf
              <p class="muted s" style="margin-top:0">
                {{ __('Faculty endorsement is recorded. Verify category compliance, readiness structure, and documentation completeness before Senate approval.') }}
              </p>
              <div class="field" style="margin-bottom:16px;max-width:540px">
                <label for="verify-comments">{{ __('Verification remarks (optional)') }}</label>
                <input type="text" id="verify-comments" name="comments" placeholder="{{ __('CODL verification remarks for Senate') }}">
              </div>
              <button type="submit" class="btn">{{ __('Confirm CODL Verification') }}</button>
            </form>
          @else
            <p class="muted" style="margin:0"><span class="tag warn">{{ __('Pending CODL verification before final Senate decision') }}</span></p>
          @endif
        </div>
      </div>
    @endif

    @if($v->status === 'codl_verified')
      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Senate Approval') }}</h2></div>
        <div class="bd">
          @if($canRecordSenate)
            <p class="muted s" style="margin-top:0">{{ __('Faculty endorsement and CODL verification are complete. Record the formal Senate decision.') }}</p>
            <a class="btn sm" href="{{ route('approvals.senate', $v->id) }}">{{ __('Record Senate decision') }}</a>
          @else
            <p class="muted" style="margin:0"><span class="tag info">{{ __('Verified by CODL; waiting for Senate approval decision') }}</span></p>
          @endif
        </div>
      </div>
    @endif

    <div class="panel">
      <div class="hd"><h2>{{ __('Decision history') }}</h2></div>
      <div class="bd" style="padding:0">
        <table>
          <thead>
            <tr>
              <th>{{ __('Stage') }}</th>
              <th>{{ __('Decision') }}</th>
              <th>{{ __('By') }}</th>
              <th>{{ __('Date') }}</th>
              <th>{{ __('Meeting') }}</th>
              <th>{{ __('Comments') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($decisions as $d)
            <tr>
              <td><b>{{ match($d->stage) { 'senate' => __('Senate'), 'codl_verification' => __('CODL Verification'), default => __('Endorsement') } }}</b></td>
              <td>
                <x-pill :s="__(str_replace('_', ' ', ucfirst($d->decision)))" />
                @if($d->verified_category) <span class="muted s">({{ __('category :cat verified', ['cat' => strtoupper($d->verified_category)]) }})</span> @endif
              </td>
              <td>{{ $d->by }}</td>
              <td>{{ \Carbon\Carbon::parse($d->created_at)->format('d/m/Y') }}</td>
              <td>{{ $d->meeting_ref ?: '-' }}</td>
              <td>
                {{ $d->comments }}
                @if($d->conditions) <div class="s muted">{{ __('Conditions: :cond', ['cond' => $d->conditions]) }}</div> @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="muted" style="text-align:center;padding:20px">{{ __('No decision yet.') }}</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Scalable Reviewer Assignment Modal (D-69) --}}
@if($canAssign)
  <div class="scrim" id="modal-assign-reviewers" hidden style="display:none;position:fixed;inset:0;background:rgba(9,10,15,0.52);z-index:80;align-items:center;justify-content:center;padding:16px">
    <div class="modal" role="dialog" aria-modal="true" aria-label="{{ __('Assign reviewers') }}" style="width:100%;max-width:620px;background:#ffffff;border:1px solid #090a0f;border-radius:6px;overflow:hidden;box-shadow:0 20px 50px rgba(9,10,15,0.25)">
      <div class="mh" style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--hairline);background:var(--surface, #f8fafc)">
        <div>
          <h2 style="margin:0;font-size:16px">{{ __('Assign reviewers') }}</h2>
          <div class="small muted" style="margin-top:2px">
            {{ __('Default:') }} {{ $proposalPtjCode ?: __('Own faculty') }}@if(!empty($requiredMqf)) &middot; {{ __('MQF :lvl+ eligible', ['lvl' => $requiredMqf]) }}@endif
          </div>
        </div>
        <button type="button" id="btn-close-assign-reviewers" aria-label="{{ __('Close') }}" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--hairline);background:#ffffff;border-radius:4px;font-size:18px;cursor:pointer;color:var(--ink)">&times;</button>
      </div>

      <form method="post" action="{{ route('approvals.assign', $v->id) }}" id="form-assign-reviewers" data-inplace>
        @csrf
        <div class="mb" style="padding:16px 18px;max-height:72vh;overflow-y:auto;display:flex;flex-direction:column;gap:12px">
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input type="search" id="rev-picker-search" placeholder="{{ __('Search reviewers or experts by name...') }}" autocomplete="off" style="flex:1;min-width:200px">
            <div style="display:inline-flex;border:1px solid var(--hairline);border-radius:4px;overflow:hidden" role="group" aria-label="{{ __('Reviewer type filter') }}">
              <button type="button" class="rev-filter-btn on" data-kind="all" style="padding:5px 10px;font-size:12px;font-weight:600;border:0;background:#0052ff;color:#ffffff;cursor:pointer">{{ __('All') }}</button>
              <button type="button" class="rev-filter-btn" data-kind="colleague" style="padding:5px 10px;font-size:12px;font-weight:600;border:0;border-left:1px solid var(--hairline);background:#ffffff;color:var(--ink);cursor:pointer">{{ __('Colleagues') }}</button>
              <button type="button" class="rev-filter-btn" data-kind="expert" style="padding:5px 10px;font-size:12px;font-weight:600;border:0;border-left:1px solid var(--hairline);background:#ffffff;color:var(--ink);cursor:pointer">{{ __('Industry experts') }}</button>
            </div>
          </div>

          <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;font-size:12.5px">
            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0">
              <input type="checkbox" id="rev-all-ptj" style="margin:0">
              <span>{{ __('Include other faculties') }}</span>
            </label>
            @if(!empty($requiredMqf))
              <span class="muted small">{{ __('Colleagues filtered to MQF :lvl+ eligible', ['lvl' => $requiredMqf]) }}</span>
            @endif
          </div>

          <div id="rev-selected-chips" style="display:flex;flex-wrap:wrap;gap:6px;min-height:0"></div>

          <div id="rev-picker-list" style="border:1px solid var(--hairline);border-radius:4px;max-height:220px;overflow-y:auto;background:#ffffff" data-proposal-ptj="{{ $proposalPtjId ?? '' }}" data-required-mqf="{{ $requiredMqf ?? '' }}">
            @foreach($lecturers as $u)
              @php
                $uMqf = $u->max_mqf_level !== null ? (int) $u->max_mqf_level : null;
                $mqfOk = empty($requiredMqf) || $uMqf === null || $uMqf >= (int) $requiredMqf;
              @endphp
              <label class="rev-picker-row" data-kind="colleague" data-name="{{ strtolower($u->name . ' ' . ($u->ptj_code ?? '')) }}" data-ptj-id="{{ $u->ptj_id ?? '' }}" data-mqf-ok="{{ $mqfOk ? '1' : '0' }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 12px;border-bottom:1px solid var(--hairline);cursor:{{ $mqfOk ? 'pointer' : 'not-allowed' }};opacity:{{ $mqfOk ? '1' : '0.55' }};margin:0">
                <span style="display:inline-flex;align-items:center;gap:8px;min-width:0">
                  <input type="checkbox" name="reviewers[]" value="{{ $u->id }}" data-label="{{ $u->name }}" class="rev-checkbox" @disabled(!$mqfOk) style="margin:0">
                  <strong style="font-size:13px">{{ $u->name }}</strong>
                </span>
                <span style="display:inline-flex;align-items:center;gap:6px;flex-shrink:0">
                  @if(!empty($u->ptj_code))<span class="tag" style="font-size:10.5px">{{ $u->ptj_code }}</span>@endif
                  @if($uMqf !== null)<span class="tag {{ $mqfOk ? 'ok' : 'err' }}" style="font-size:10.5px">MQF {{ $uMqf }}</span>@endif
                  <span class="tag info" style="font-size:10.5px">{{ __('Colleague') }}</span>
                </span>
              </label>
            @endforeach
            @foreach($experts as $u)
              <label class="rev-picker-row" data-kind="expert" data-name="{{ strtolower($u->name) }}" data-ptj-id="" data-mqf-ok="1" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 12px;border-bottom:1px solid var(--hairline);cursor:pointer;margin:0">
                <span style="display:inline-flex;align-items:center;gap:8px">
                  <input type="checkbox" name="reviewers[]" value="{{ $u->id }}" data-label="{{ $u->name }}" class="rev-checkbox" style="margin:0">
                  <strong style="font-size:13px">{{ $u->name }}</strong>
                </span>
                <span class="tag" style="font-size:10.5px">{{ __('Industry expert (comments only)') }}</span>
              </label>
            @endforeach
            <div id="rev-picker-empty" class="muted small" style="display:none;padding:16px;text-align:center">{{ __('No matching eligible reviewers found. Try enabling "Include other faculties".') }}</div>
            @if($lecturers->isEmpty() && $experts->isEmpty())
              <div class="muted small" style="padding:16px;text-align:center">{{ __('No reviewers or industry experts available.') }}</div>
            @endif
          </div>

          <div style="display:grid;grid-template-columns:180px minmax(0,1fr);gap:12px">
            <div class="field" style="margin:0">
              <label for="due_on">{{ __('Due date') }}</label>
              <input type="date" id="due_on" name="due_on" min="{{ today()->toDateString() }}" value="{{ today()->addDays(7)->toDateString() }}" required style="width:100%">
            </div>
            <div class="field" style="margin:0">
              <label for="scope">{{ __('Scope of review') }}</label>
              <input type="text" id="scope" name="scope" required minlength="5" placeholder="{{ __('e.g. Full proposal, syllabus alignment, assessment rubrics') }}" style="width:100%;max-width:100%">
            </div>
          </div>
        </div>

        <div class="mf" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--hairline);background:var(--surface, #f8fafc)">
          <span class="small muted" id="rev-selected-count">0 {{ __('selected') }}</span>
          <div style="display:inline-flex;gap:8px">
            <button type="button" class="btn ter sm" id="btn-cancel-assign-reviewers">{{ __('Cancel') }}</button>
            <button type="submit" class="btn sm">{{ __('Assign') }}</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
  (function initAssignReviewersModal() {
    var openBtn = document.getElementById('btn-open-assign-reviewers');
    var modal = document.getElementById('modal-assign-reviewers');
    if (!openBtn || !modal) return;
    var closeBtn = document.getElementById('btn-close-assign-reviewers');
    var cancelBtn = document.getElementById('btn-cancel-assign-reviewers');
    var search = document.getElementById('rev-picker-search');
    var chips = document.getElementById('rev-selected-chips');
    var countEl = document.getElementById('rev-selected-count');
    var filterBtns = modal.querySelectorAll('.rev-filter-btn');
    var rows = modal.querySelectorAll('.rev-picker-row');
    var listEl = document.getElementById('rev-picker-list');
    var allPtjCb = document.getElementById('rev-all-ptj');
    var emptyEl = document.getElementById('rev-picker-empty');
    var proposalPtj = listEl ? (listEl.getAttribute('data-proposal-ptj') || '') : '';
    var activeKind = 'all';

    function openModal() {
      modal.hidden = false;
      modal.style.display = 'flex';
      applyFilter();
      if (search) setTimeout(function () { search.focus(); }, 30);
    }
    function closeModal() {
      modal.hidden = true;
      modal.style.display = 'none';
    }
    function applyFilter() {
      var q = (search ? search.value : '').trim().toLowerCase();
      var includeOtherPtj = !!(allPtjCb && allPtjCb.checked);
      var visible = 0;
      rows.forEach(function (r) {
        var k = r.getAttribute('data-kind');
        var name = r.getAttribute('data-name') || '';
        var rowPtj = r.getAttribute('data-ptj-id') || '';
        var mqfOk = r.getAttribute('data-mqf-ok') !== '0';
        var matchKind = activeKind === 'all' || k === activeKind;
        var matchQ = !q || name.indexOf(q) > -1;
        var matchPtj = (k === 'expert') || includeOtherPtj || !proposalPtj || !rowPtj || (rowPtj === proposalPtj);
        var show = matchKind && matchQ && matchPtj && mqfOk;
        r.style.display = show ? 'flex' : 'none';
        if (show) visible++;
      });
      if (emptyEl) emptyEl.style.display = (rows.length > 0 && visible === 0) ? 'block' : 'none';
    }
    if (allPtjCb) allPtjCb.addEventListener('change', applyFilter);
    function syncSelected() {
      var checked = modal.querySelectorAll('.rev-checkbox:checked');
      if (countEl) countEl.textContent = checked.length + ' {{ __("selected") }}';
      if (!chips) return;
      chips.innerHTML = '';
      checked.forEach(function (cb) {
        var chip = document.createElement('span');
        chip.className = 'tag info';
        chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:3px 8px;font-size:12px';
        chip.textContent = cb.getAttribute('data-label') || '';
        var x = document.createElement('button');
        x.type = 'button';
        x.textContent = '×';
        x.style.cssText = 'border:0;background:none;color:inherit;cursor:pointer;font-weight:700;padding:0;line-height:1';
        x.addEventListener('click', function () { cb.checked = false; syncSelected(); });
        chip.appendChild(x);
        chips.appendChild(chip);
      });
    }

    openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeModal(); });
    if (search) search.addEventListener('input', applyFilter);
    filterBtns.forEach(function (b) {
      b.addEventListener('click', function () {
        activeKind = b.getAttribute('data-kind') || 'all';
        filterBtns.forEach(function (o) {
          var on = o === b;
          o.style.background = on ? '#0052ff' : '#ffffff';
          o.style.color = on ? '#ffffff' : 'var(--ink)';
        });
        applyFilter();
      });
    });
    modal.addEventListener('change', function (e) {
      if (e.target && e.target.classList.contains('rev-checkbox')) syncSelected();
    });
  })();
  </script>
@endif
@endsection
