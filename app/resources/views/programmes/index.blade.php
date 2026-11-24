@extends('layouts.app')
@section('title', __('Degree Programmes'))
@section('content')
<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <h1>{{ __('Degree Programmes') }}</h1>
    <div class="sub">{{ __('Browse programme degree plans by year and semester, inspect course equivalences, and compare pathways.') }}</div>
  </div>
  <div class="act" style="display:flex;gap:8px;flex-wrap:wrap">
    <a class="btn primary sm" href="{{ route('programmes.compare') }}">{{ __('Compare Two Programmes') }}</a>
    <a class="btn ter sm" href="{{ route('equivalences') }}">{{ __('Equivalence Register') }}</a>
  </div>
</div>

<form method="get" action="{{ route('programmes.index') }}" class="panel" style="margin-bottom:20px">
  <div class="bd" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;padding:14px 18px">
    <div>
      <label for="faculty" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Faculty') }}</label>
      <select id="faculty" name="faculty" style="min-width:180px">
        <option value="">{{ __('All faculties') }}</option>
        @foreach($faculties as $f)
          <option value="{{ $f->code }}" @selected($faculty === $f->code)>{{ $f->code }} &ndash; {{ $f->name }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label for="level" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Qualification Level') }}</label>
      <select id="level" name="level" style="min-width:160px">
        <option value="">{{ __('All levels') }}</option>
        <option value="bachelor" @selected($level === 'bachelor')>{{ __('Bachelor') }}</option>
        <option value="diploma" @selected($level === 'diploma')>{{ __('Diploma') }}</option>
        <option value="postgraduate" @selected($level === 'postgraduate')>{{ __('Postgraduate') }}</option>
      </select>
    </div>
    <button type="submit" class="btn sm">{{ __('Filter') }}</button>
    @if($faculty !== '' || $level !== '')
      <a class="btn ter sm" href="{{ route('programmes.index') }}">{{ __('Reset') }}</a>
    @endif
  </div>
</form>

<div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(340px, 1fr));gap:16px">
  @forelse($programmes as $prog)
    <div class="panel" style="display:flex;flex-direction:column;justify-content:space-between">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
        <div>
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:6px">
            <span class="tag info" style="font-family:var(--font-mono)"><strong>{{ $prog->code }}</strong></span>
            @if(!empty($prog->faculty_code))
              <span class="tag neutral">{{ $prog->faculty_code }}</span>
            @endif
            <span class="tag neutral">MQF {{ $prog->mqf_level }}</span>
          </div>
          <h2 style="margin:0;font-size:15px;line-height:1.35">{{ $prog->name }}</h2>
        </div>
      </div>
      <div class="bd" style="padding:14px 18px;flex:1;display:flex;flex-direction:column;justify-content:space-between;gap:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px">
          <div>
            <div class="s muted">{{ __('Required Credits') }}</div>
            <strong>{{ $prog->total_credits }} cr</strong>
          </div>
          <div>
            <div class="s muted">{{ __('Active Session') }}</div>
            <strong>{{ $prog->active_session ?? __('Not structured yet') }}</strong>
          </div>
          <div>
            <div class="s muted">{{ __('Courses Mapped') }}</div>
            <strong>{{ $prog->course_count }}</strong>
          </div>
          <div>
            <div class="s muted">{{ __('Transfer Limit') }}</div>
            <strong>{{ $prog->transfer_limit_percent ?? 50 }}%</strong>
          </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:10px;border-top:1px solid var(--color-line-hairline, #e2e8f0)">
          <a class="btn primary sm" href="{{ route('programmes.show', $prog->code) }}">{{ __('View Degree Plan') }}</a>
          <a class="btn ter sm" href="{{ route('programmes.compare', ['from' => $prog->code]) }}">{{ __('Compare') }}</a>
          @if($canManage)
            <a class="btn sec sm" href="{{ route('programmes.manage', $prog->code) }}">{{ __('Manage') }}</a>
          @endif
        </div>
      </div>
    </div>
  @empty
    <div class="panel" style="grid-column:1/-1">
      <div class="bd" style="padding:28px;text-align:center">
        <p class="muted" style="margin:0">{{ __('No programmes match the selected filter.') }}</p>
      </div>
    </div>
  @endforelse
</div>
@endsection
