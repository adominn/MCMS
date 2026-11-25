@php
  $openCount = $comments->where('status', 'open')->count();
  $blockingCount = $comments->where('status', '!=', 'resolved')->where('blocking', true)->count();
  $answeredCount = $comments->where('status', 'answered')->count();
  $resolvedCount = $comments->where('status', 'resolved')->count();

  // Group comments by review assignment (1 clean summary row per reviewer & round)
  $groups = $comments->groupBy(fn ($c) => ($c->review_assignment_id ?? 'other') . '|' . $c->author_id)->values();
@endphp

<div class="panel">
  <div class="hd" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
      <h2 style="margin:0">{{ __('Review comments') }}</h2>
      @if($comments->isNotEmpty())
        <div class="small muted" style="margin-top:2px">
          {{ $comments->count() }} {{ $comments->count() === 1 ? __('comment') : __('comments') }}
          @if($openCount > 0) &middot; {{ $openCount }} {{ __('open') }}@endif
          @if($blockingCount > 0) &middot; {{ $blockingCount }} {{ __('blocking') }}@endif
          @if($answeredCount > 0) &middot; {{ $answeredCount }} {{ __('answered') }}@endif
          @if($resolvedCount > 0) &middot; {{ $resolvedCount }} {{ __('resolved') }}@endif
        </div>
      @endif
    </div>
  </div>

  <div class="bd" style="padding:0">
    @if($groups->isNotEmpty())
      <table data-plain style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr>
            <th>{{ __('Reviewer') }}</th>
            <th>{{ __('Role') }}</th>
            <th>{{ __('Round') }}</th>
            <th>{{ __('Summary') }}</th>
            <th>{{ __('Status') }}</th>
            <th style="text-align:right">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($groups as $gIdx => $gItems)
            @php
              $first = $gItems->first();
              $gOpen = $gItems->where('status', 'open')->count();
              $gBlocking = $gItems->where('status', '!=', 'resolved')->where('blocking', true)->count();
              $gAnswered = $gItems->where('status', 'answered')->count();
              $gResolved = $gItems->where('status', 'resolved')->count();
              $isSuperseded = ($first->assignment_status ?? null) === 'superseded';
              $isExpert = ($first->assignment_kind ?? null) === 'expert';
              $modalId = 'modal-cmt-group-' . ($first->review_assignment_id ?? 'other') . '-' . $first->author_id;
            @endphp
            <tr class="cmt-summary-row" data-open-modal="{{ $modalId }}" style="cursor:pointer">
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
                @if($gOpen > 0)
                  <span class="tag warn">{{ $gOpen }} {{ __('open') }}</span>
                @elseif($gAnswered > 0)
                  <span class="tag info">{{ $gAnswered }} {{ __('answered') }}</span>
                @else
                  <span class="tag ok">{{ __('All resolved') }}</span>
                @endif
              </td>
              <td style="text-align:right">
                <button type="button" class="btn {{ $gOpen > 0 ? '' : 'ter' }} sm" data-open-modal="{{ $modalId }}">
                  {{ $gOpen > 0 ? __('View & respond') : __('View comments') }}
                </button>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p class="muted" style="margin:0;padding:20px">{{ __('No comments yet.') }}</p>
    @endif
  </div>
</div>

{{-- Per-reviewer / round Comment Modals --}}
@foreach($groups as $gIdx => $gItems)
  @php
    $first = $gItems->first();
    $gOpen = $gItems->where('status', 'open')->count();
    $gBlocking = $gItems->where('status', '!=', 'resolved')->where('blocking', true)->count();
    $gAnswered = $gItems->where('status', 'answered')->count();
    $gResolved = $gItems->where('status', 'resolved')->count();
    $isSuperseded = ($first->assignment_status ?? null) === 'superseded';
    $isExpert = ($first->assignment_kind ?? null) === 'expert';
    $modalId = 'modal-cmt-group-' . ($first->review_assignment_id ?? 'other') . '-' . $first->author_id;
  @endphp
  <div id="{{ $modalId }}" class="cmt-modal" hidden role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" style="position:fixed;inset:0;z-index:1100;background:rgba(9,10,15,.48);display:flex;align-items:center;justify-content:center;padding:20px">
    <div style="width:100%;max-width:860px;max-height:88vh;display:flex;flex-direction:column;background:#ffffff;border:1px solid var(--color-card-border, #090a0f);border-radius:8px;box-shadow:0 20px 50px rgba(9,10,15,.22);overflow:hidden">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--hairline);background:var(--surface, #f8fafc)">
        <div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <h2 id="{{ $modalId }}-title" style="margin:0;font-size:16px;font-weight:700;color:var(--ink)">{{ __('Comments from :name', ['name' => $first->author]) }}</h2>
            <span class="tag">{{ $isExpert ? __('Industry expert') : __('Reviewer') }}</span>
            @if($isSuperseded)<span class="tag warn">{{ __('Previous round (Superseded)') }}</span>@endif
          </div>
          <div class="small muted" style="margin-top:4px">
            {{ $gItems->count() }} {{ $gItems->count() === 1 ? __('comment') : __('comments') }}
            @if($gOpen > 0) &middot; {{ $gOpen }} {{ __('open') }}@endif
            @if($gBlocking > 0) &middot; {{ $gBlocking }} {{ __('blocking') }}@endif
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <div class="cmt-modal-pills" style="display:inline-flex;border:1px solid var(--color-card-border, #090a0f);border-radius:4px;overflow:hidden;font-size:12px">
            <button type="button" class="cmt-fbtn on" data-cmt-filter="all" style="padding:4px 9px;border:0;border-right:1px solid var(--hairline);background:var(--color-primary, #0052ff);color:#fff;font-weight:600;cursor:pointer">{{ __('All') }} ({{ $gItems->count() }})</button>
            <button type="button" class="cmt-fbtn" data-cmt-filter="open" style="padding:4px 9px;border:0;border-right:1px solid var(--hairline);background:#fff;color:var(--ink);font-weight:500;cursor:pointer">{{ __('Open') }} ({{ $gOpen }})</button>
            <button type="button" class="cmt-fbtn" data-cmt-filter="answered" style="padding:4px 9px;border:0;border-right:1px solid var(--hairline);background:#fff;color:var(--ink);font-weight:500;cursor:pointer">{{ __('Answered') }} ({{ $gAnswered }})</button>
            <button type="button" class="cmt-fbtn" data-cmt-filter="resolved" style="padding:4px 9px;border:0;background:#fff;color:var(--ink);font-weight:500;cursor:pointer">{{ __('Resolved') }} ({{ $gResolved }})</button>
          </div>
          <button type="button" class="btn ter sm cmt-modal-close" aria-label="{{ __('Close') }}">&times;</button>
        </div>
      </div>

      <div style="padding:0;overflow-y:auto;flex:1">
        <table data-plain style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr>
              <th style="width:36%">{{ __('Item & comment') }}</th>
              <th style="width:15%">{{ __('Status') }}</th>
              <th style="width:49%">{{ __('Your response') }}</th>
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
              <tr class="cmt-row" data-cmt-status="{{ $c->status }}">
                <td style="vertical-align:top">
                  @if($itemTitle)
                    <div style="font-weight:700;color:var(--ink);margin-bottom:2px">{{ $itemTitle }}</div>
                    <div style="color:var(--ink)">{{ $itemBody }}</div>
                  @else
                    <div>{{ $itemBody }}</div>
                  @endif
                </td>
                <td style="vertical-align:top">
                  <div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px">
                    <span class="tag {{ $stCls }}">{{ $stLbl }}</span>
                    @if($c->blocking)
                      <span class="tag overdue">{{ __('Blocking') }}</span>
                    @endif
                  </div>
                </td>
                <td style="vertical-align:top">
                  @if($c->response)
                    <div style="margin-bottom:4px"><b>{{ __('Your response:') }}</b> {{ $c->response }}</div>
                  @endif
                  @if($c->resolve_reason)
                    <div class="small muted">{{ __('Resolved by the deputy dean: :reason', ['reason' => $c->resolve_reason]) }}</div>
                  @endif
                  @if($c->status === 'open')
                    <form method="post" action="{{ route('comments.respond', [$v->id, $c->id]) }}" class="cmt-reply-form" data-modal-id="{{ $modalId }}" data-cmt-id="{{ $c->id }}" style="display:flex;gap:8px;align-items:center;margin:0" data-inplace>
                      @csrf
                      <label for="resp-{{ $c->id }}" class="sr">{{ __('Your response') }}</label>
                      <input type="text" id="resp-{{ $c->id }}" name="response" class="cmt-resp-input" data-cmt-id="{{ $c->id }}" required minlength="3" placeholder="{{ __('Type your response...') }}" style="flex:1;min-width:160px;height:32px;font-size:12.5px">
                      <button type="submit" class="btn ter sm" style="white-space:nowrap">{{ __('Send response') }}</button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 20px;border-top:1px solid var(--hairline);background:var(--surface, #f8fafc)">
        <div class="small muted">
          @if($gOpen > 0)
            {{ __('Fill in one or more responses above, then click Submit all responses.') }}
          @endif
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          @if($gOpen > 0)
            <form method="post" action="{{ route('comments.respond_all', $v->id) }}" class="cmt-batch-form" data-modal-id="{{ $modalId }}" style="margin:0" data-inplace>
              @csrf
              <div class="cmt-batch-fields" hidden></div>
              <button type="submit" class="btn sm">{{ __('Submit all responses') }}</button>
            </form>
          @endif
          <button type="button" class="btn ter sm cmt-modal-close">{{ __('Close') }}</button>
        </div>
      </div>
    </div>
  </div>
@endforeach

@if($groups->isNotEmpty())
  <script>
  (function () {
    var STORE_KEY = 'mcms_open_cmt_modal_{{ $v->id }}';
    var DRAFT_KEY = 'mcms_cmt_drafts_{{ $v->id }}';

    function loadDrafts() {
      try { return JSON.parse(sessionStorage.getItem(DRAFT_KEY) || '{}') || {}; } catch (e) { return {}; }
    }
    function saveDrafts(d) {
      try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(d)); } catch (e) {}
    }

    // Restore any unsaved typed responses across single-row submits
    var drafts = loadDrafts();
    document.querySelectorAll('.cmt-resp-input').forEach(function (inp) {
      var cid = inp.getAttribute('data-cmt-id');
      if (cid && drafts[cid] && !inp.value) inp.value = drafts[cid];
      inp.addEventListener('input', function () {
        var cur = loadDrafts();
        if (inp.value) cur[cid] = inp.value; else delete cur[cid];
        saveDrafts(cur);
      });
    });

    // Clear draft for a row when submitted individually
    document.querySelectorAll('.cmt-reply-form').forEach(function (f) {
      f.addEventListener('submit', function () {
        var cid = f.getAttribute('data-cmt-id');
        var cur = loadDrafts();
        delete cur[cid];
        saveDrafts(cur);
      });
    });

    // Collect all filled responses inside this modal on Submit all responses
    document.querySelectorAll('.cmt-batch-form').forEach(function (bf) {
      bf.addEventListener('submit', function (e) {
        var modal = document.getElementById(bf.getAttribute('data-modal-id'));
        var holder = bf.querySelector('.cmt-batch-fields');
        if (!modal || !holder) return;
        holder.innerHTML = '';
        var count = 0;
        var cur = loadDrafts();
        modal.querySelectorAll('.cmt-resp-input').forEach(function (inp) {
          var val = inp.value.trim();
          var cid = inp.getAttribute('data-cmt-id');
          if (val !== '' && cid) {
            var h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'responses[' + cid + ']';
            h.value = val;
            holder.appendChild(h);
            delete cur[cid];
            count++;
          }
        });
        if (count === 0) {
          e.preventDefault();
          e.stopImmediatePropagation();
          if (window.mcmsToast) window.mcmsToast(@json(__('No responses entered')), @json(__('Type at least one response before submitting.')), 'warn');
          return;
        }
        saveDrafts(cur);
      }, true);
    });

    function openModal(id) {
      var m = document.getElementById(id);
      if (!m) return;
      document.querySelectorAll('.cmt-modal').forEach(function (el) { el.hidden = true; });
      m.hidden = false;
      try { sessionStorage.setItem(STORE_KEY, id); } catch (e) {}
    }
    function closeAll() {
      document.querySelectorAll('.cmt-modal').forEach(function (el) { el.hidden = true; });
      try { sessionStorage.removeItem(STORE_KEY); } catch (e) {}
    }

    document.querySelectorAll('[data-open-modal]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.stopPropagation();
        openModal(el.getAttribute('data-open-modal'));
      });
    });

    document.querySelectorAll('.cmt-modal').forEach(function (modal) {
      modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target.closest('.cmt-modal-close')) {
          closeAll();
        }
      });
      modal.querySelectorAll('.cmt-fbtn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var f = btn.getAttribute('data-cmt-filter');
          modal.querySelectorAll('.cmt-fbtn').forEach(function (b) {
            var on = b === btn;
            b.classList.toggle('on', on);
            b.style.background = on ? 'var(--color-primary, #0052ff)' : '#ffffff';
            b.style.color = on ? '#ffffff' : 'var(--ink)';
            b.style.fontWeight = on ? '600' : '500';
          });
          modal.querySelectorAll('.cmt-row').forEach(function (row) {
            row.hidden = !(f === 'all' || row.getAttribute('data-cmt-status') === f);
          });
        });
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeAll();
    });

    // Re-open the active modal automatically after an in-place reply swap
    try {
      var prev = sessionStorage.getItem(STORE_KEY);
      if (prev && document.getElementById(prev)) {
        openModal(prev);
      }
    } catch (e) {}
  })();
  </script>
@endif
