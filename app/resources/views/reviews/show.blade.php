@extends('layouts.app')
@section('title', __('Review'))
@section('content')
<x-breadcrumb :crumbs="[
  (auth()->user()->hasAnyRole('lecturer') ? 'Reviews for colleagues' : 'My review invitations') => route('reviews'),
  $v->name_en => null,
]" />

<div class="title">
  <div>
    <h1>{{ $a->kind === 'expert' ? __('Comment on a proposal') : __('Review a proposal') }}: {{ $v->name_en }}</h1>
    <div class="sub">
      {{ __('Due :date', ['date' => \Carbon\Carbon::parse($a->due_on)->format('d/m/Y')]) }} &middot; {{ __('Scope: :scope', ['scope' => $a->scope]) }}@if(!empty($v->category)) &middot; <x-cat :c="$v->category" />@endif
    </div>
  </div>
  @if($status !== 'invited')
    <div class="act">
      <a class="btn ter sm" href="{{ route('reviews') }}">{{ __('Back to invitations') }}</a>
    </div>
  @endif
</div>

@if($status === 'invited')
  <style>
    .coi-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(260px, 1fr); gap: 20px; align-items: start; }
    .coi-opt { display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; border: 1px solid var(--color-card-border, #090a0f); border-radius: 6px; background: #ffffff; cursor: pointer; transition: border-color .12s ease, background .12s ease; margin: 0; }
    .coi-opt:hover { background: var(--surface, #f8fafc); border-color: var(--color-primary, #0052ff); }
    .coi-opt.on { background: var(--color-primary-tint, #edf4ff); border-color: var(--color-primary, #0052ff); box-shadow: inset 3px 0 0 var(--color-primary, #0052ff); }
    .coi-opt.on-warn { background: #fff7ed; border-color: #c2410c; box-shadow: inset 3px 0 0 #c2410c; }
    .coi-opt input { margin-top: 3px; flex-shrink: 0; }
    .coi-kv { display: flex; flex-direction: column; gap: 10px; font-size: 13px; }
    .coi-kv-row { display: flex; flex-direction: column; gap: 2px; padding-bottom: 8px; border-bottom: 1px solid var(--hairline); }
    .coi-kv-row:last-child { padding-bottom: 0; border-bottom: 0; }
    .coi-kv-k { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
    .coi-kv-v { color: var(--ink); font-weight: 500; }
    @media (max-width: 820px) { .coi-grid { grid-template-columns: 1fr; } }
  </style>

  <div class="coi-grid">
    <form method="post" action="{{ route('reviews.coi', $a->id) }}" id="coi-form">
      @csrf
      <div class="panel">
        <div class="hd"><h2>{{ __('Step 1: conflict of interest') }}</h2></div>
        <div class="bd">
          <p class="muted" style="margin-top:0;font-size:13px;line-height:1.5">
            {{ __('The proposal stays closed until you declare whether you have a conflict of interest. Declaring a conflict is standard governance practice — it removes you immediately without penalty and returns the task to the Deputy Dean to assign another reviewer.') }}
          </p>

          <div class="col" style="gap:10px;margin:18px 0">
            <label class="coi-opt" data-coi-card="none">
              <input type="radio" name="conflict" value="none" required>
              <div>
                <div style="font-weight:700;color:var(--ink);font-size:13.5px">{{ __('I have no conflict of interest') }}</div>
                <div class="s" style="color:var(--muted);font-size:12.5px;margin-top:2px;line-height:1.45">{{ __('You did not co-develop this micro-credential, have no supervisory or close personal relationship with the proposer, and have no commercial interest in it.') }}</div>
              </div>
            </label>

            <label class="coi-opt" data-coi-card="conflict">
              <input type="radio" name="conflict" value="conflict">
              <div>
                <div style="font-weight:700;color:var(--ink);font-size:13.5px">{{ __('I have a conflict of interest') }}</div>
                <div class="s" style="color:var(--muted);font-size:12.5px;margin-top:2px;line-height:1.45">{{ __('Declines this invitation immediately and notifies the Deputy Dean to assign another reviewer.') }}</div>
              </div>
            </label>
          </div>

          <div id="coi-reason-wrap" hidden style="margin-bottom:18px">
            <label for="coi_reason" style="display:block;font-size:12.5px;font-weight:600;margin-bottom:4px">
              {{ __('Reason for conflict (optional)') }}
            </label>
            <textarea id="coi_reason" name="coi_reason" rows="2" maxlength="500" style="width:100%" placeholder="{{ __('e.g. Co-developed part of the syllabus, supervise the proposer, or have an external industry tie') }}"></textarea>
            <div class="small muted" style="margin-top:4px">{{ __('Recorded in the governance audit trail to help the Deputy Dean select an independent reviewer.') }}</div>
          </div>

          <div class="row" style="gap:10px">
            <button type="submit" class="btn">{{ __('Confirm') }}</button>
            <a class="btn ter" href="{{ route('reviews') }}">{{ __('Cancel') }}</a>
          </div>
        </div>
      </div>
    </form>

    <div class="panel">
      <div class="hd"><h2>{{ __('Invitation overview') }}</h2></div>
      <div class="bd">
        <div class="coi-kv">
          <div class="coi-kv-row">
            <span class="coi-kv-k">{{ __('Proposer') }}</span>
            <span class="coi-kv-v">{{ $v->owner_name ?? '–' }}</span>
          </div>
          <div class="coi-kv-row">
            <span class="coi-kv-k">{{ __('Faculty (PTj)') }}</span>
            <span class="coi-kv-v">{{ $v->ptj_code ?? '–' }}@if(!empty($v->ptj_name)) &middot; {{ $v->ptj_name }}@endif</span>
          </div>
          <div class="coi-kv-row">
            <span class="coi-kv-k">{{ __('Category & version') }}</span>
            <span class="coi-kv-v">
              @if(!empty($v->category))<x-cat :c="$v->category" />@else – @endif
              @if(!empty($v->version_no)) &middot; v{{ $v->version_no }}@endif
            </span>
          </div>
          <div class="coi-kv-row">
            <span class="coi-kv-k">{{ __('Assigned by') }}</span>
            <span class="coi-kv-v">{{ $assignedBy ?? __('Deputy Dean') }}</span>
          </div>
          <div class="coi-kv-row">
            <span class="coi-kv-k">{{ __('Review due date') }}</span>
            <span class="coi-kv-v">{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</span>
          </div>
          <div class="coi-kv-row">
            <span class="coi-kv-k">{{ __('Scope of review') }}</span>
            <span class="coi-kv-v">{{ $a->scope ?: '–' }}</span>
          </div>
          @if(!empty($priorReviews) && $priorReviews->isNotEmpty())
            <div class="coi-kv-row">
              <span class="coi-kv-k">{{ $priorReviews->count() === 1 ? __('Your earlier review') : __('Your earlier reviews') }}</span>
              <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px">
                @foreach($priorReviews->reverse()->values() as $pIdx => $pr)
                  @php
                    $rNo = $priorReviews->count() - $pIdx;
                    $rLbl = __(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$pr->recommendation] ?? ucfirst($pr->recommendation));
                    $rCls = $pr->recommendation === 'support' ? 'ok' : ($pr->recommendation === 'not_support' ? 'overdue' : 'warn');
                  @endphp
                  <div style="padding:8px 10px;background:var(--surface, #f8fafc);border:1px solid var(--hairline);border-radius:4px;font-size:12.5px">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;flex-wrap:wrap">
                      <b>{{ __('Round :n', ['n' => $rNo]) }} &middot; {{ \Carbon\Carbon::parse($pr->submitted_at)->format('d/m/Y') }}</b>
                      <span class="tag {{ $rCls }}">{{ $rLbl }}</span>
                    </div>
                    @if(!empty($pr->summary))
                      <div class="muted small" style="margin-top:4px;line-height:1.4">{{ $pr->summary }}</div>
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <script>
  (function () {
    var form = document.getElementById('coi-form');
    var reasonWrap = document.getElementById('coi-reason-wrap');
    if (!form) return;
    function sync() {
      var val = (form.querySelector('input[name="conflict"]:checked') || {}).value || '';
      form.querySelectorAll('.coi-opt').forEach(function (card) {
        var k = card.getAttribute('data-coi-card');
        card.classList.toggle('on', k === 'none' && val === 'none');
        card.classList.toggle('on-warn', k === 'conflict' && val === 'conflict');
      });
      if (reasonWrap) reasonWrap.hidden = (val !== 'conflict');
    }
    form.addEventListener('change', sync);
  })();
  </script>
@elseif($status === 'declined')
  <div class="note warn" role="alert" style="max-width:680px">
    <b>{{ __('Declined:') }}</b> {{ __('You declared a conflict of interest and were removed from this review.') }}
  </div>
  <div style="margin-top:16px">
    <a class="btn ter" href="{{ route('reviews') }}">{{ __('Back to invitations') }}</a>
  </div>
@else
  @php
    $startOnFormTab = $errors->any() || old('recommendation') || old('summary') || old('rating') || old('text') || in_array($status, ['submitted', 'superseded'], true);
    $secondTabLabel = $a->kind === 'expert'
      ? __('Comments') . ' (' . $mine->count() . ')'
      : (in_array($status, ['submitted', 'superseded'], true) ? __('Your review') : __('Rate & submit review'));
    $priorReviews = $priorReviews ?? collect();
  @endphp

  <div data-tabs data-default-tab="{{ $startOnFormTab ? 'rev-form' : 'rev-summary' }}">
    <div class="tabs" role="tablist" style="margin-bottom:18px">
      <button type="button" class="tab" role="tab" data-tab="rev-summary" aria-selected="{{ $startOnFormTab ? 'false' : 'true' }}">
        {{ __('Proposal summary') }}
      </button>
      <button type="button" class="tab" role="tab" data-tab="rev-form" aria-selected="{{ $startOnFormTab ? 'true' : 'false' }}">
        {{ $secondTabLabel }}
      </button>
      @if($priorReviews->isNotEmpty())
        <button type="button" class="tab" role="tab" data-tab="rev-prior" aria-selected="false">
          {{ $priorReviews->count() === 1 ? __('Previous review') : __('Previous reviews (:count)', ['count' => $priorReviews->count()]) }}
        </button>
      @endif
    </div>

    <div data-pane="rev-summary" @if($startOnFormTab) hidden @endif>
      <div style="margin-bottom:16px">
        @include('approval._summary')
      </div>
      <div class="row" style="justify-content:flex-end">
        <button type="button" class="btn" onclick="var b=document.querySelector('[data-tab=&quot;rev-form&quot;]');if(b)b.click();window.scrollTo({top:0,behavior:'smooth'});">
          {{ $a->kind === 'expert' ? __('Proceed to comments') : ($status === 'submitted' ? __('View your review') : __('Proceed to review')) }} &rarr;
        </button>
      </div>
    </div>

    <div data-pane="rev-form" @if(!$startOnFormTab) hidden @endif>
      @if($a->kind === 'expert')
        <div class="panel" style="margin-bottom:20px">
          <div class="hd"><h2>{{ __('Your comments') }}</h2></div>
          <div class="bd">
            @forelse($mine as $c)
              <div class="panel" style="margin-bottom:12px">
                <div class="bd">
                  <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:6px">
                    <b>{{ $c->text }}</b>
                    <x-pill :s="__(['open' => 'Open', 'answered' => 'Answered', 'resolved' => 'Resolved'][$c->status] ?? $c->status)" />
                  </div>
                  @if($c->response)
                    <div class="note info" style="margin-top:8px">
                      <b>{{ __('Response:') }}</b> {{ $c->response }}
                    </div>
                  @endif
                </div>
              </div>
            @empty
              <div class="muted">{{ __('None yet.') }}</div>
            @endforelse
          </div>
        </div>

        @if(in_array($v->status, ['submitted', 'in_review', 'endorsed']))
          <form method="post" action="{{ route('reviews.comment', $a->id) }}">
            @csrf
            <div class="panel" style="margin-bottom:20px">
              <div class="hd"><h2>{{ __('Add a comment') }}</h2></div>
              <div class="bd">
                <p class="muted" style="margin-top:0;font-size:13px">{{ __('Comment on competencies and relevance only. There is no rating and no recommendation for this role.') }}</p>
                <div class="field" style="margin-bottom:16px">
                  <label for="text">{{ __('Comment') }}</label>
                  <textarea id="text" name="text" rows="3" required minlength="5" placeholder="{{ __('Share your feedback on competencies and relevance') }}">{{ old('text') }}</textarea>
                </div>
                <button type="submit" class="btn">{{ __('Send comments') }}</button>
              </div>
            </div>
          </form>
        @else
          <div class="note info"><p class="muted" style="margin:0">{{ __('Comments are closed: the decision is recorded.') }}</p></div>
        @endif
      @elseif(in_array($status, ['submitted', 'superseded'], true) && $review)
        <div class="panel">
          <div class="hd" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <h2 style="margin:0">{{ __('Your review') }}@if($status === 'superseded') <span class="tag warn" style="margin-left:6px">{{ __('Superseded') }}</span>@endif</h2>
            <span class="tag {{ $review->recommendation === 'support' ? 'ok' : ($review->recommendation === 'not_support' ? 'overdue' : 'warn') }}">
              {{ __(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$review->recommendation] ?? ucfirst($review->recommendation)) }}
            </span>
          </div>
          <div class="bd" style="display:flex;flex-direction:column;gap:16px">
            <dl class="dl" style="margin:0">
              <dt>{{ __('Recommendation') }}</dt>
              <dd><b>{{ __(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$review->recommendation] ?? $review->recommendation) }}</b></dd>
              <dt>{{ __('Summary') }}</dt>
              <dd>{{ $review->summary }}</dd>
            </dl>
            @php $myRatings = json_decode($review->ratings ?? '[]', true) ?: []; @endphp
            @if(!empty($myRatings))
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
                  <tbody>
                    @foreach($myRatings as $rk => $rv)
                      <tr>
                        <td><b>{{ __($items[$rk] ?? $rk) }}</b></td>
                        <td><span class="tag {{ ($rv['rating'] ?? '') === 'satisfactory' ? 'ok' : 'warn' }}">{{ ($rv['rating'] ?? '') === 'satisfactory' ? __('Satisfactory') : __('Needs amendment') }}</span></td>
                        <td style="text-align:center">@if(!empty($rv['blocking']))<span class="tag overdue">{{ __('Blocking') }}</span>@else<span class="muted">&ndash;</span>@endif</td>
                        <td>{{ !empty($rv['comment']) ? $rv['comment'] : '–' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        </div>
      @else
        @php $list = collect($items)->when($v->category !== 'umc', fn ($c) => $c->except('parent_mapping')); @endphp
        <form method="post" action="{{ route('reviews.submit', $a->id) }}" id="review-submit-form">
          @csrf
          <div class="panel" style="margin-bottom:24px">
            <div class="hd" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
              <div>
                <h2 style="margin:0">{{ __('Step 2: rate each item') }}</h2>
                <div class="small muted" style="margin-top:2px">{{ __('"Needs amendment" logs a tracked comment for the proposer. Tick "Blocking" to prevent endorsement until the proposer responds and the Deputy Dean resolves it.') }}</div>
              </div>
            </div>
            <div class="bd" style="padding:0">
              <table data-plain>
                <thead>
                  <tr>
                    <th style="width:38%">{{ __('Item') }}</th>
                    <th style="width:20%">{{ __('Rating') }}</th>
                    <th style="width:32%">{{ __('Comment') }}</th>
                    <th style="width:10%;text-align:center">{{ __('Blocking') }}</th>
                  </tr>
                </thead>
                <tbody>
                @foreach($list as $k => $label)
                  <tr data-rate-row="{{ $k }}">
                    <td><b>{{ __($label) }}</b></td>
                    <td>
                      <select name="rating[{{ $k }}]" class="rev-rating-sel" aria-label="{{ __('Rating for :item', ['item' => $label]) }}">
                        <option value="">-</option>
                        <option value="satisfactory" @selected(old("rating.$k") === 'satisfactory')>{{ __('Satisfactory') }}</option>
                        <option value="needs_amendment" @selected(old("rating.$k") === 'needs_amendment')>{{ __('Needs amendment') }}</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" name="comment[{{ $k }}]" value="{{ old("comment.$k") }}" aria-label="{{ __('Comment for :item', ['item' => $label]) }}" placeholder="{{ __('Required if needs amendment') }}">
                    </td>
                    <td style="text-align:center">
                      <input type="checkbox" name="blocking[{{ $k }}]" value="1" class="rev-blocking-cb" @checked(old("blocking.$k")) aria-label="Mark the comment on {{ $label }} as blocking" title="{{ __('Applies when rated Needs amendment: blocks endorsement until resolved') }}">
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="panel" style="margin-bottom:24px">
            <div class="hd"><h2>{{ __('Step 3: recommendation') }}</h2></div>
            <div class="bd">
              <div class="field" style="margin-bottom:16px;max-width:360px">
                <label for="recommendation">{{ __('Recommendation') }}</label>
                <select id="recommendation" name="recommendation">
                  <option value="">-</option>
                  <option value="support" @selected(old('recommendation') === 'support')>{{ __('Support') }}</option>
                  <option value="amend" @selected(old('recommendation') === 'amend')>{{ __('Amend') }}</option>
                  <option value="not_support" @selected(old('recommendation') === 'not_support')>{{ __('Do not support') }}</option>
                </select>
                <div id="rec-auto-hint" class="small muted" style="margin-top:4px" hidden></div>
              </div>

              <div class="field" style="margin-bottom:20px">
                <label for="summary">{{ __('Summary') }}</label>
                <textarea id="summary" name="summary" rows="3" placeholder="{{ __('Provide an overall summary of your review') }}">{{ old('summary') }}</textarea>
              </div>

              <div class="row" style="gap:12px">
                <button type="submit" class="btn">{{ __('Submit review') }}</button>
                <a class="btn ter" href="{{ route('reviews') }}">{{ __('Cancel') }}</a>
              </div>
            </div>
          </div>
        </form>

        <script>
        (function () {
          var form = document.getElementById('review-submit-form');
          var recSel = document.getElementById('recommendation');
          var recHint = document.getElementById('rec-auto-hint');
          if (!form || !recSel) return;
          var manuallySet = false;

          recSel.addEventListener('change', function () {
            manuallySet = true;
            if (recHint) recHint.hidden = true;
          });

          function syncRatings(fromUser) {
            var sels = Array.prototype.slice.call(form.querySelectorAll('.rev-rating-sel'));
            var amendCount = 0;
            var satCount = 0;
            sels.forEach(function (sel) {
              var tr = sel.closest('tr');
              var cb = tr ? tr.querySelector('.rev-blocking-cb') : null;
              var isAmend = sel.value === 'needs_amendment';
              if (isAmend) amendCount++;
              else if (sel.value === 'satisfactory') satCount++;
              if (cb) {
                cb.disabled = !isAmend;
                if (!isAmend) cb.checked = false;
              }
            });

            if (fromUser && !manuallySet) {
              if (amendCount > 0) {
                recSel.value = 'amend';
                if (recHint) {
                  recHint.textContent = amendCount === 1
                    ? @json(__('Suggested "Amend" because 1 item needs amendment (you can still change this).'))
                    : @json(__('Suggested "Amend" because multiple items need amendment (you can still change this).'));
                  recHint.hidden = false;
                }
              } else if (satCount === sels.length && sels.length > 0) {
                recSel.value = 'support';
                if (recHint) {
                  recHint.textContent = @json(__('Suggested "Support" because all items are satisfactory (you can still change this).'));
                  recHint.hidden = false;
                }
              }
            }
          }

          form.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('rev-rating-sel')) {
              syncRatings(true);
            }
          });
          syncRatings(false);
        })();
        </script>
      @endif
    </div>

    @if($priorReviews->isNotEmpty())
      <div data-pane="rev-prior" hidden>
        @if($priorReviews->count() > 1)
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px" role="group" aria-label="{{ __('Select previous review round') }}">
            <span class="small muted" style="font-weight:600">{{ __('Review round:') }}</span>
            @foreach($priorReviews->reverse()->values() as $pIdx => $pr)
              @php $roundNo = $priorReviews->count() - $pIdx; @endphp
              <button type="button" class="btn sm {{ $pIdx === 0 ? '' : 'ter' }} prior-round-btn" data-prior-target="prior-round-{{ $pr->id }}">
                {{ __('Round :n', ['n' => $roundNo]) }} &middot; {{ \Carbon\Carbon::parse($pr->submitted_at)->format('d/m/Y') }}
              </button>
            @endforeach
          </div>
        @endif

        @foreach($priorReviews->reverse()->values() as $pIdx => $pr)
          @php
            $roundNo = $priorReviews->count() - $pIdx;
            $prRatings = json_decode($pr->ratings ?? '[]', true) ?: [];
            $prRecLabel = __(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$pr->recommendation] ?? ucfirst($pr->recommendation));
            $prRecCls = $pr->recommendation === 'support' ? 'ok' : ($pr->recommendation === 'not_support' ? 'overdue' : 'warn');
          @endphp
          <div id="prior-round-{{ $pr->id }}" class="panel prior-round-pane" @if($pIdx > 0) hidden @endif>
            <div class="hd" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
              <div>
                <h2 style="margin:0">
                  {{ $priorReviews->count() === 1 ? __('Previous review (Superseded)') : __('Round :n (Superseded)', ['n' => $roundNo]) }}
                </h2>
                <div class="small muted" style="margin-top:2px">
                  {{ __('Submitted :date', ['date' => \Carbon\Carbon::parse($pr->submitted_at)->format('d/m/Y')]) }}@if(!empty($pr->scope)) &middot; {{ __('Scope: :scope', ['scope' => $pr->scope]) }}@endif
                </div>
              </div>
              <span class="tag {{ $prRecCls }}">{{ $prRecLabel }}</span>
            </div>
            <div class="bd" style="display:flex;flex-direction:column;gap:16px">
              <div style="padding:12px 14px;background:var(--surface, #f8fafc);border:1px solid var(--hairline);border-radius:4px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:4px">{{ __('Overall summary') }}</div>
                <div style="font-size:13.5px;color:var(--ink);line-height:1.5">{{ $pr->summary ?: '–' }}</div>
              </div>
              @if(!empty($prRatings))
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
                    <tbody>
                      @foreach($prRatings as $rk => $rv)
                        <tr>
                          <td><b>{{ __($items[$rk] ?? $rk) }}</b></td>
                          <td><span class="tag {{ ($rv['rating'] ?? '') === 'satisfactory' ? 'ok' : 'warn' }}">{{ ($rv['rating'] ?? '') === 'satisfactory' ? __('Satisfactory') : __('Needs amendment') }}</span></td>
                          <td style="text-align:center">@if(!empty($rv['blocking']))<span class="tag overdue">{{ __('Blocking') }}</span>@else<span class="muted">&ndash;</span>@endif</td>
                          <td>{{ !empty($rv['comment']) ? $rv['comment'] : '–' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        @endforeach

        @if($priorReviews->count() > 1)
          <script>
          (function () {
            document.querySelectorAll('.prior-round-btn').forEach(function (btn) {
              btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-prior-target');
                document.querySelectorAll('.prior-round-btn').forEach(function (b) {
                  b.classList.toggle('ter', b !== btn);
                });
                document.querySelectorAll('.prior-round-pane').forEach(function (p) {
                  p.hidden = (p.id !== target);
                });
              });
            });
          })();
          </script>
        @endif
      </div>
    @endif
  </div>
@endif
@endsection
