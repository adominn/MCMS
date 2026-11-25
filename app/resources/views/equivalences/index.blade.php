@extends('layouts.app')
@section('title', __('Course Equivalence Register'))
@section('content')
<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <h1>{{ __('Course Equivalence & Transfer Register') }}</h1>
    <div class="sub">{{ __('View approved course equivalences, transfer conditions, and items pending formal evaluation.') }}</div>
  </div>
  <div class="act">
    <a class="btn ter sm" href="{{ route('programmes.compare') }}">{{ __('Compare Programmes') }}</a>
  </div>
</div>

@if($errors->any())
  <div class="note err" style="margin-bottom:16px">
    <ul style="margin:0;padding-left:18px">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div data-tabs>
  <nav class="tabs" aria-label="{{ __('Equivalence sections') }}" style="margin-bottom:16px">
    <button data-tab="register" class="on">{{ __('Equivalence Register') }} ({{ $equivalences->count() }})</button>
    @if($canPropose)
      <button data-tab="propose">{{ __('Propose Equivalence') }}</button>
    @endif
  </nav>

  <!-- Tab 1: Equivalence Register -->
  <div data-pane="register">
    <form method="get" action="{{ route('equivalences') }}" class="panel" style="margin-bottom:16px">
      <div class="bd" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;padding:12px 16px">
        <div>
          <label for="q" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Search Course') }}</label>
          <input id="q" name="q" type="text" value="{{ $search }}" placeholder="BITD 1233..." style="width:180px">
        </div>
        <div>
          <label for="type" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Equivalence Type') }}</label>
          <select id="type" name="type">
            <option value="">{{ __('All types') }}</option>
            @foreach($eqTypes as $k => $lbl)
              <option value="{{ $k }}" @selected($type === $k)>{{ __($lbl) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="status" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Governance Status') }}</label>
          <select id="status" name="status">
            <option value="">{{ __('All statuses') }}</option>
            @foreach($eqStatuses as $k => $lbl)
              <option value="{{ $k }}" @selected($status === $k)>{{ __($lbl) }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn sm">{{ __('Filter') }}</button>
        @if($search !== '' || $type !== '' || $status !== '')
          <a class="btn ter sm" href="{{ route('equivalences') }}">{{ __('Reset') }}</a>
        @endif
      </div>
    </form>

    <div class="panel">
      <div style="overflow-x:auto">
        <table style="margin:0">
          <thead>
            <tr>
              <th>{{ __('Ref') }}</th>
              <th>{{ __('Source Course') }}</th>
              <th>{{ __('Target Course') }}</th>
              <th>{{ __('Type & Credits') }}</th>
              <th>{{ __('Conditions & Validity') }}</th>
              <th>{{ __('Status') }}</th>
              @if($canEndorse || $canVerify || $canApprove)
                <th style="min-width:220px">{{ __('Decision Action') }}</th>
              @endif
            </tr>
          </thead>
          <tbody>
            @forelse($equivalences as $eq)
              @php($typeClass = match($eq->equivalence_type) {
                'exact' => 'ok',
                'partial', 'conditional' => 'warn',
                'no_transfer' => 'err',
                default => 'neutral'
              })
              <tr>
                <td style="font-family:var(--font-mono);font-size:12px;white-space:nowrap">{{ $eq->reference }}</td>
                <td>
                  <a href="{{ route('courses.show', urlencode($eq->source_code)) }}" style="font-family:var(--font-mono);font-weight:600">{{ $eq->source_code }}</a>
                  @if($eq->source_programme_code) <span class="tag neutral">{{ $eq->source_programme_code }}</span> @endif
                  <div class="s muted">{{ $eq->source_name }}</div>
                </td>
                <td>
                  <a href="{{ route('courses.show', urlencode($eq->target_code)) }}" style="font-family:var(--font-mono);font-weight:600">{{ $eq->target_code }}</a>
                  @if($eq->target_programme_code) <span class="tag neutral">{{ $eq->target_programme_code }}</span> @endif
                  <div class="s muted">{{ $eq->target_name }}</div>
                </td>
                <td>
                  <span class="tag {{ $typeClass }}">{{ __($eqTypes[$eq->equivalence_type] ?? $eq->equivalence_type) }}</span>
                  <div class="s" style="margin-top:4px">
                    <strong>{{ mcms_num($eq->credits_counted) }} cr</strong>
                    <span class="muted">&middot; {{ $eq->direction === 'bidirectional' ? __('Both ways') : __('One way') }}</span>
                  </div>
                </td>
                <td class="s">
                  {{ app()->getLocale() === 'ms' && !empty($eq->condition_note_ms) ? $eq->condition_note_ms : ($eq->condition_note_en ?: __('Standard rules apply.')) }}
                  <div class="muted" style="margin-top:2px">
                    @if($eq->min_grade) {{ __('Min grade:') }} {{ $eq->min_grade }} &middot; @endif
                    {{ __('Validity:') }} {{ $eq->validity_years }} {{ __('years') }}
                  </div>
                </td>
                <td>
                  <span class="tag {{ $eq->status === 'approved' ? 'ok' : ($eq->status === 'rejected' ? 'err' : ($eq->status === 'needs_review' ? 'warn' : 'info')) }}">
                    {{ __($eqStatuses[$eq->status] ?? $eq->status) }}
                  </span>
                  @if($eq->approval_ref)
                    <div class="s muted" style="font-family:var(--font-mono);font-size:11px;margin-top:2px">{{ $eq->approval_ref }}</div>
                  @endif
                </td>
                @if($canEndorse || $canVerify || $canApprove)
                  <td>
                    @if($canEndorse && in_array($eq->status, ['proposed', 'to_confirm', 'needs_review'], true))
                      <form method="post" action="{{ route('equivalences.endorse', $eq->id) }}" style="display:flex;gap:6px;flex-wrap:wrap" data-inplace>
                        @csrf
                        <button type="submit" name="action" value="endorse" class="btn primary sm">{{ __('Endorse (Faculty)') }}</button>
                        <button type="submit" name="action" value="reject" class="btn ter sm" style="color:var(--color-error, #da1e28)">{{ __('Return') }}</button>
                      </form>
                    @elseif($canVerify && $eq->status === 'dd_endorsed')
                      <form method="post" action="{{ route('equivalences.verify', $eq->id) }}" style="display:flex;gap:6px;flex-wrap:wrap" data-inplace>
                        @csrf
                        <button type="submit" name="action" value="verify" class="btn primary sm">{{ __('Verify (CODL)') }}</button>
                        <button type="submit" name="action" value="reject" class="btn ter sm" style="color:var(--color-error, #da1e28)">{{ __('Return') }}</button>
                      </form>
                    @elseif($canApprove && $eq->status === 'codl_verified')
                      <form method="post" action="{{ route('equivalences.approve', $eq->id) }}" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center" data-inplace>
                        @csrf
                        <input name="approval_ref" type="text" placeholder="SENATE/2026/..." style="width:130px;height:32px;font-size:12px" required>
                        <button type="submit" name="action" value="approve" class="btn primary sm">{{ __('Approve (Senate)') }}</button>
                      </form>
                    @else
                      <span class="s muted">&mdash;</span>
                    @endif
                  </td>
                @endif
              </tr>
            @empty
              <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">{{ __('No course equivalences found.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tab 2: Propose Equivalence (Lecturer, DD, CODL) -->
  @if($canPropose)
    <div data-pane="propose" style="display:none">
      <form method="post" action="{{ route('equivalences.store') }}" class="panel" data-inplace>
        @csrf
        <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Propose Course Equivalence') }}</h2></div>
        <div class="bd" style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px">
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Source Course') }}</label>
            <select name="source_course_id" required style="width:100%">
              @foreach($courses as $c)
                <option value="{{ $c->id }}" @selected(request('propose') == $c->id)>{{ $c->code }} &ndash; {{ $c->name }} ({{ $c->credit }} cr)</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Source Programme (Optional)') }}</label>
            <select name="source_programme_id" style="width:100%">
              <option value="">{{ __('Any programme') }}</option>
              @foreach($programmes as $p)
                <option value="{{ $p->id }}">{{ $p->code }} &ndash; {{ $p->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Target Course') }}</label>
            <select name="target_course_id" required style="width:100%">
              @foreach($courses as $c)
                <option value="{{ $c->id }}">{{ $c->code }} &ndash; {{ $c->name }} ({{ $c->credit }} cr)</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Target Programme (Optional)') }}</label>
            <select name="target_programme_id" style="width:100%">
              <option value="">{{ __('Any programme') }}</option>
              @foreach($programmes as $p)
                <option value="{{ $p->id }}">{{ $p->code }} &ndash; {{ $p->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Equivalence Type') }}</label>
            <select name="equivalence_type" required style="width:100%">
              @foreach($eqTypes as $k => $lbl)
                <option value="{{ $k }}">{{ __($lbl) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Credits Counted Toward Target') }}</label>
            <input name="credits_counted" type="number" step="0.5" min="0" max="20" value="3" required style="width:100%">
          </div>
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Direction') }}</label>
            <select name="direction" required style="width:100%">
              <option value="one_way">{{ __('One-way (Source to Target)') }}</option>
              <option value="bidirectional">{{ __('Bidirectional (Both ways)') }}</option>
            </select>
          </div>
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Minimum Grade') }}</label>
            <input name="min_grade" type="text" value="C" style="width:100%">
          </div>
          <div>
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Credit Validity (Years)') }}</label>
            <input name="validity_years" type="number" min="1" max="20" value="10" style="width:100%">
          </div>
          <div style="grid-column:1/-1">
            <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Transfer Condition / Justification (English)') }}</label>
            <textarea name="condition_note_en" rows="2" style="width:100%" placeholder="{{ __('State any bridging requirement, grade condition, or reason if not transferable.') }}"></textarea>
          </div>
          <div style="grid-column:1/-1">
            <button type="submit" class="btn primary">{{ __('Submit Equivalence Proposal') }}</button>
          </div>
        </div>
      </form>
    </div>
  @endif
</div>
@endsection
