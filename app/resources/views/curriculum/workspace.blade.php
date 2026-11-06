@extends('layouts.app')
@section('title', $v->name_en ?: __('Proposal'))
@section('content')
<div class="crumb">
  <a href="{{ route('proposals') }}">{{ __('Proposals and versions') }}</a>
  <span>/</span>
  <span>{{ $v->name_en ?: __('Proposal') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ $v->name_en ?: __('Untitled proposal') }}</h1>
    <div class="sub">
      {{ __('Appendix A') }} &middot; {{ __('Version :num', ['num' => $v->version_no]) }}@if($v->proposal_type !== 'new') &middot; {{ __(['revision' => 'Revision', 'reoffer' => 'Re-offer'][$v->proposal_type] ?? ucfirst($v->proposal_type)) }}@endif &middot;
      <span id="stamp" class="muted">{{ $locked ? __('Locked') : __('Autosaved') }}</span>
    </div>
  </div>
  <div class="act" style="gap:8px;align-items:center">
    <x-cat :c="$v->category" />
    <x-pill :s="$v->category_status === 'proposed' ? __('Proposed') : __('Verified')" />
    <x-pill :s="__(\App\Modules\Approval\ApprovalController::STATUS[$v->status] ?? $v->status)" />
    <a class="btn ter sm" href="{{ route('proposals.print', $v->id) }}" target="_blank">{{ __('Print (Appendix A)') }}</a>
    <a class="btn ter sm" href="{{ route('proposals') }}">{{ __('All proposals') }}</a>
    @if($v->status === 'draft')
      <form method="post" action="{{ route('proposals.destroy', $v->id) }}" data-confirm="{{ __('Delete this draft proposal?') }}|{{ __('This draft proposal and its unsaved changes will be permanently removed. This action cannot be undone.') }}" data-danger data-label="{{ __('Delete draft') }}" style="display:inline;margin:0">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn danger sm">{{ __('Delete draft') }}</button>
      </form>
    @endif
  </div>
</div>

@if($v->change_summary)
  <div class="note info" style="margin-bottom:16px">
    <b>{{ __('Change note:') }}</b> {{ $v->change_summary }}
  </div>
@endif

@if(!empty($returned))
  <div class="note err" role="alert" style="margin-bottom:16px">
    <b>{{ __('Returned for amendment:') }}</b> {{ $returned->comments }}
  </div>
@endif

<nav class="tabs" aria-label="Proposal sections" style="margin-bottom:20px">
  @foreach($tabs as $k => $label)
    <a href="{{ route('proposals.show', ['id' => $v->id, 'tab' => $k]) }}" @if($tab === $k) aria-current="page" class="on" @endif>
      {{ __($label) }}
      @if($k === 'comments' && !empty($commentsCount))
        <span class="c">{{ $commentsCount }}</span>
      @endif
    </a>
  @endforeach
</nav>

<div class="ws">
  <div>
    @if($locked && $tab !== 'submit')
      <div class="note info" role="status" style="margin-bottom:16px">
        {{ __('This proposal is :status. Its content is locked.', ['status' => strtolower(\App\Modules\Approval\ApprovalController::STATUS[$v->status] ?? $v->status)]) }}
      </div>
    @endif

    @include('curriculum.tabs.'.$tab)
  </div>

  <aside class="panel checks" role="complementary" aria-label="Checks" style="position:sticky;top:20px">
    <div class="hd">
      <h2>{{ __('Checks') }}</h2>
      <span class="r" id="checks-badge">
        @if(empty($checks['blocking']))
          <span class="tag ok">{{ __('Clear') }}</span>
        @else
          <span class="tag err">{{ count($checks['blocking']) }}</span>
        @endif
      </span>
    </div>
    <div class="bd">
      <p aria-live="polite" id="autosave" class="s muted" style="margin-top:0">
        @if(!$locked){{ __('Changes save automatically.') }}@endif
      </p>

      <div id="checks-blocking-container">
        @if($checks['blocking'])
          <div style="margin-bottom:16px">
            <b><span id="blocking-count">{{ count($checks['blocking']) }}</span> {{ __('blocking error(s)') }}</b>
            <ul id="blocking-list" style="margin:8px 0 0;padding-left:18px">
              @foreach($checks['blocking'] as $c)
                <li style="margin-bottom:6px">
                  <span class="tag err">{{ __('Error') }}</span>
                  <a href="{{ route('proposals.show', ['id' => $v->id, 'tab' => $c['tab']]) }}" style="font-weight:500">{{ __($c['msg']) }}</a>
                  <span class="muted s" style="display:block">{{ $c['fr'] }}</span>
                </li>
              @endforeach
            </ul>
          </div>
        @else
          <div class="check" style="margin-bottom:16px">
            <span class="dot ok"></span>
            <div><b>{{ __('Clear') }}</b> {{ __('No blocking errors.') }}</div>
          </div>
        @endif
      </div>

      <div id="checks-warnings-container">
        @if($checks['warnings'])
          <div style="margin-bottom:16px">
            <b><span id="warnings-count">{{ count($checks['warnings']) }}</span> {{ __('warning(s)') }}</b>
            <ul id="warnings-list" style="margin:8px 0 0;padding-left:18px">
              @foreach($checks['warnings'] as $c)
                <li style="margin-bottom:6px">
                  <span class="tag warn">{{ __('Warning') }}</span>
                  <a href="{{ route('proposals.show', ['id' => $v->id, 'tab' => $c['tab']]) }}">{{ __($c['msg']) }}</a>
                </li>
              @endforeach
            </ul>
          </div>
        @endif
      </div>

      <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:12px">
        <p class="muted" style="margin:0"><span id="slt-line">{{ __('Total learning time: :hours h', ['hours' => $checks['slt']]) }}</span></p>
      </div>
    </div>
  </aside>
</div>

@unless($locked)
<script>
  // FR-46: Auto-save on edit & preserve unsaved changes when navigating between sections.
  (function () {
    var form = document.querySelector('form[data-autosave]');
    if (!form) return;

    var timer = null;
    var isDirty = false;
    var isSaving = false;
    var note = document.getElementById('autosave');
    var stamp = document.getElementById('stamp');
    var proposalBaseUrl = '{{ route("proposals.show", ["id" => $v->id]) }}';

    var txtSaving = '{{ __("Saving...") }}';
    var txtSavedAuto = '{{ __("Saved automatically at") }}';
    var txtNotSaved = '{{ __("Not saved yet. Use Save.") }}';
    var txtClear = '{{ __("Clear") }}';
    var txtNoBlocking = '{{ __("No blocking errors.") }}';
    var txtBlockingErrors = '{{ __("blocking error(s)") }}';
    var txtError = '{{ __("Error") }}';
    var txtWarning = '{{ __("Warning") }}';
    var txtWarnings = '{{ __("warning(s)") }}';

    function saveNow(callback) {
      if (isSaving) {
        if (callback) callback();
        return;
      }
      isSaving = true;
      clearTimeout(timer);
      if (note) note.textContent = txtSaving;
      if (stamp) stamp.textContent = txtSaving;

      var fd = new FormData(form);
      fd.set('action', 'save');

      var actionUrl = form.getAttribute('action');
      var tokenInput = form.querySelector('input[name="_token"]');
      var csrfToken = tokenInput ? tokenInput.value : '{{ csrf_token() }}';

      fetch(actionUrl, {
        method: 'POST',
        body: fd,
        headers: {
          'X-Autosave': '1',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        }
      })
      .then(function (r) {
        if (!r.ok) throw new Error('Save error');
        return r.json();
      })
      .then(function (j) {
        isDirty = false;
        isSaving = false;
        if (note) note.textContent = txtSavedAuto + ' ' + j.saved_at + '.';
        if (stamp) stamp.textContent = '{{ __("Autosaved") }}';

        if (j.checks) {
          updateSidebarChecks(j.checks);
        }
        if (callback) callback(null, j);
      })
      .catch(function (err) {
        isSaving = false;
        if (note) note.textContent = txtNotSaved;
        if (callback) callback(err);
      });
    }

    function scheduleSave() {
      isDirty = true;
      clearTimeout(timer);
      if (stamp) stamp.textContent = '{{ __("Editing...") }}';
      timer = setTimeout(function () {
        saveNow();
      }, 700);
    }

    // Trigger on text typing AND dropdown/checkbox toggles
    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);

    // Form manual submit clears dirty flag
    form.addEventListener('submit', function () {
      isDirty = false;
      clearTimeout(timer);
    });

    // Save changes before user clicks ANY link (top tabs or check guide in sidebar)
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a');
      if (!a || !a.href || a.target === '_blank') return;
      var href = a.getAttribute('href');
      if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

      if (isDirty) {
        e.preventDefault();
        var targetUrl = a.href;
        if (note) note.textContent = txtSaving;
        saveNow(function () {
          window.location.href = targetUrl;
        });
        // Safety timeout: navigate anyway if network is stuck
        setTimeout(function () {
          window.location.href = targetUrl;
        }, 1200);
      }
    });

    // Save on beforeunload / tab close
    window.addEventListener('beforeunload', function () {
      if (isDirty) {
        var fd = new FormData(form);
        fd.set('action', 'save');
        var actionUrl = form.getAttribute('action');
        var tokenInput = form.querySelector('input[name="_token"]');
        var csrfToken = tokenInput ? tokenInput.value : '{{ csrf_token() }}';
        fetch(actionUrl, {
          method: 'POST',
          body: fd,
          headers: {
            'X-Autosave': '1',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          keepalive: true
        });
      }
    });

    function updateSidebarChecks(checks) {
      // 1. Update header badge
      var badge = document.getElementById('checks-badge');
      if (badge) {
        if (!checks.blocking || checks.blocking.length === 0) {
          badge.innerHTML = '<span class="tag ok">' + txtClear + '</span>';
        } else {
          badge.innerHTML = '<span class="tag err">' + checks.blocking.length + '</span>';
        }
      }

      // 2. Update blocking container
      var blockBox = document.getElementById('checks-blocking-container');
      if (blockBox) {
        if (!checks.blocking || checks.blocking.length === 0) {
          blockBox.innerHTML = '<div class="check" style="margin-bottom:16px"><span class="dot ok"></span><div><b>' + txtClear + '</b> ' + txtNoBlocking + '</div></div>';
        } else {
          var html = '<div style="margin-bottom:16px"><b><span id="blocking-count">' + checks.blocking.length + '</span> ' + txtBlockingErrors + '</b><ul id="blocking-list" style="margin:8px 0 0;padding-left:18px">';
          checks.blocking.forEach(function (c) {
            var url = proposalBaseUrl + (proposalBaseUrl.indexOf('?') >= 0 ? '&' : '?') + 'tab=' + encodeURIComponent(c.tab);
            html += '<li style="margin-bottom:6px"><span class="tag err">' + txtError + '</span> <a href="' + url + '" style="font-weight:500">' + escapeHtml(c.msg) + '</a><span class="muted s" style="display:block">' + escapeHtml(c.fr) + '</span></li>';
          });
          html += '</ul></div>';
          blockBox.innerHTML = html;
        }
      }

      // 3. Update warnings container
      var warnBox = document.getElementById('checks-warnings-container');
      if (warnBox) {
        if (!checks.warnings || checks.warnings.length === 0) {
          warnBox.innerHTML = '';
        } else {
          var wHtml = '<div style="margin-bottom:16px"><b><span id="warnings-count">' + checks.warnings.length + '</span> ' + txtWarnings + '</b><ul id="warnings-list" style="margin:8px 0 0;padding-left:18px">';
          checks.warnings.forEach(function (c) {
            var wUrl = proposalBaseUrl + (proposalBaseUrl.indexOf('?') >= 0 ? '&' : '?') + 'tab=' + encodeURIComponent(c.tab);
            wHtml += '<li style="margin-bottom:6px"><span class="tag warn">' + txtWarning + '</span> <a href="' + wUrl + '">' + escapeHtml(c.msg) + '</a></li>';
          });
          wHtml += '</ul></div>';
          warnBox.innerHTML = wHtml;
        }
      }

      // 4. Update SLT
      var sltLine = document.getElementById('slt-line');
      if (sltLine && checks.slt !== undefined) {
        sltLine.textContent = '{{ __("Total learning time: :hours h", ["hours" => "___SLT___"]) }}'.replace('___SLT___', checks.slt);
      }
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
  })();
</script>
@endunless
@endsection
