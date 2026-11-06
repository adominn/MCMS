@extends('layouts.app')
@section('title', $v->name_en)
@section('content')
@php($inReview = $v->status === 'in_review')
<div class="crumb">
  <a href="{{ route('approvals') }}">{{ __('Proposals and approval') }}</a>
  <span>/</span>
  <span>{{ $v->name_en }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ $v->name_en }}</h1>
    <div class="sub">
      <x-cat :c="$v->category" /> &middot;
      {{ __('Category :status', ['status' => __(['proposed' => 'proposed', 'verified' => 'verified', 'confirmed' => 'confirmed'][$v->category_status] ?? $v->category_status)]) }} &middot;
      <x-pill :s="__($labels[$v->status] ?? ucfirst($v->status))" /> &middot;
      {{ __('Version :num', ['num' => $v->version_no]) }}@if($v->supersedes_id) ({{ __(['revision' => 'Revision', 'reoffer' => 'Re-offer'][$v->proposal_type] ?? ucfirst($v->proposal_type)) }} {{ __('of version :num', ['num' => $v->version_no - 1]) }})@endif
    </div>
  </div>
  <div class="act" style="gap:8px">
    <a class="btn ter sm" href="{{ route('proposals.print', $v->id) }}" target="_blank">{{ __('Print proposal') }}</a>
    <a class="btn ter sm" href="{{ route('approvals.pack', $v->id) }}">{{ __('Decision pack') }}</a>
    @if($v->status === 'endorsed')
      <a class="btn sm" href="{{ route('approvals.senate', $v->id) }}">{{ __('Record Senate decision') }}</a>
    @endif
  </div>
</div>

@php($blocks = count($checks['blocking']))
@if(in_array($v->status, ['submitted', 'in_review']))
  <div class="note {{ $blocks ? 'warn' : 'info' }}" style="margin-bottom:20px">
    @if($blocks)
      <b>{{ $blocks }} {{ $blocks === 1 ? __('blocking error') : __('blocking errors') }}</b> {{ __('to fix before this proposal can be endorsed.') }}
    @else
      {{ __('No blocking errors. Open the comments and record your endorsement once academic reviews are in.') }}
    @endif
  </div>
@endif

<div class="col" style="gap:20px;margin-bottom:24px">
  <details class="fold">
    <summary><b>{{ __('Proposal summary') }}</b> <span class="muted small">{{ __('modules, outcomes, assessment, readiness') }}</span></summary>
    <div style="margin-top:12px">
      @include('approval._summary')
    </div>
  </details>

  <details class="fold" id="checks">
    <summary><b>{{ __('System checks') }}</b> <span class="muted small">{{ __(':blocks blocking, :warns warnings', ['blocks' => $blocks, 'warns' => count($checks['warnings'])]) }}</span></summary>
    <div class="card" style="margin-top:12px">
      @forelse($checks['blocking'] as $c)
        <p><span class="tag overdue">{{ __('Error') }}</span> {{ $c['msg'] }}</p>
      @empty
        <p><span class="tag ok">{{ __('Clear') }}</span> {{ __('No blocking errors.') }}</p>
      @endforelse
      @foreach($checks['warnings'] as $c)
        <p><span class="tag">{{ __('Warning') }}</span> {{ $c['msg'] }}</p>
      @endforeach
      @if($v->submit_response)
        <p><b>{{ __("Proposer's response to the warnings:") }}</b> {{ $v->submit_response }}</p>
      @endif
    </div>
  </details>
</div>

<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Reviewers') }}</h2></div>
  <div class="bd" style="padding:0">
    <table id="reviewers">
      <thead>
        <tr>
          <th>{{ __('Name') }}</th>
          <th>{{ __('Role') }}</th>
          <th>{{ __('Due') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Recommendation') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($assign as $a)
        <tr>
          <td><b>{{ $a->reviewer }}</b></td>
          <td>{{ $a->kind === 'expert' ? __('Industry expert (comments only)') : __('Reviewer') }}</td>
          <td>{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</td>
          <td>{{ __(['invited' => 'Invited', 'in_progress' => 'In progress', 'submitted' => 'Submitted', 'declined' => 'Declined (conflict of interest)'][$a->status] ?? ucfirst($a->status)) }}</td>
          <td>
            @if($a->recommendation)
              <x-pill :s="__(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$a->recommendation] ?? ucfirst($a->recommendation))" />
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted" style="text-align:center;padding:20px">{{ __('No reviewers assigned yet.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  @if(in_array($v->status, ['submitted', 'in_review']))
    <div class="bd" style="border-top:1px solid var(--border)">
      <form method="post" action="{{ route('approvals.assign', $v->id) }}">
        @csrf
        <h3>{{ __('Assign reviewers') }}</h3>
        <fieldset style="border:0;padding:0;margin-bottom:16px">
          <legend style="font-weight:600;margin-bottom:8px">{{ __('Colleague reviewers') }}</legend>
          @forelse($lecturers as $u)
            <label style="display:inline-flex;align-items:center;gap:6px;margin-right:16px;cursor:pointer">
              <input type="checkbox" name="reviewers[]" value="{{ $u->id }}">
              <span>{{ $u->name }}</span>
            </label>
          @empty
            <span class="muted">{{ __('No other lecturer is available.') }}</span>
          @endforelse
        </fieldset>

        <fieldset style="border:0;padding:0;margin-bottom:16px">
          <legend style="font-weight:600;margin-bottom:8px">{{ __('Industry experts (comments only)') }}</legend>
          @forelse($experts as $u)
            <label style="display:inline-flex;align-items:center;gap:6px;margin-right:16px;cursor:pointer">
              <input type="checkbox" name="reviewers[]" value="{{ $u->id }}">
              <span>{{ $u->name }}</span>
            </label>
          @empty
            <span class="muted">{{ __('No industry expert is available.') }}</span>
          @endforelse
        </fieldset>

        <div class="row" style="gap:16px;margin-bottom:16px">
          <div class="field" style="max-width:200px">
            <label for="due_on">{{ __('Due date') }}</label>
            <input type="date" id="due_on" name="due_on" min="{{ today()->toDateString() }}" required>
          </div>
          <div class="field" style="flex:1">
            <label for="scope">{{ __('Scope of review') }}</label>
            <input type="text" id="scope" name="scope" required minlength="5" placeholder="{{ __('e.g. Full proposal, syllabus alignment, assessment rubrics') }}">
          </div>
        </div>

        <button type="submit" class="btn sm">{{ __('Assign') }}</button>
      </form>
    </div>
  @endif
</div>

@foreach($assign->where('status', 'submitted')->where('kind', 'reviewer') as $a)
  <div class="panel" style="margin-bottom:20px">
    <div class="hd">
      <h3>{{ __('Review by :name', ['name' => $a->reviewer]) }}</h3>
      <span class="r">
        <x-pill :s="__(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$a->recommendation] ?? ucfirst($a->recommendation))" />
      </span>
    </div>
    <div class="bd">
      <p style="margin-top:0">{{ $a->summary }}</p>
      <ul style="margin:0;padding-left:18px">
        @foreach(json_decode($a->ratings ?? '[]', true) ?: [] as $k => $r)
          <li style="margin-bottom:6px">
            {{ $items[$k] ?? $k }}: <b>{{ $r['rating'] === 'satisfactory' ? __('Satisfactory') : __('Needs amendment') }}</b>
            @if(!empty($r['comment'])) &mdash; {{ $r['comment'] }} @endif
            @if(!empty($r['blocking'])) <span class="tag overdue">{{ __('Blocking') }}</span> @endif
          </li>
        @endforeach
      </ul>
    </div>
  </div>
@endforeach

<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2>{{ __('Comments') }}</h2></div>
  <div class="bd" style="padding:0">
    <table id="comments">
      <thead>
        <tr>
          <th style="width:20%">{{ __('From') }}</th>
          <th style="width:35%">{{ __('Comment') }}</th>
          <th style="width:12%">{{ __('Status') }}</th>
          <th style="width:33%">{{ __('Response & Resolution') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($comments as $c)
        <tr>
          <td><b>{{ $c->author }}</b></td>
          <td>
            {{ $c->text }}
            @if($c->blocking) <span class="tag overdue">{{ __('Blocking') }}</span> @endif
          </td>
          <td>{{ __(['open' => 'Open', 'answered' => 'Answered', 'resolved' => 'Resolved'][$c->status] ?? ucfirst($c->status)) }}</td>
          <td>
            @if($c->response)
              <div style="margin-bottom:6px"><b>{{ __('Response:') }}</b> {{ $c->response }}</div>
            @endif
            @if($c->resolve_reason)
              <div class="muted s">{{ __('Resolved: :reason', ['reason' => $c->resolve_reason]) }}</div>
            @endif
            @if($c->status === 'answered')
              <form method="post" action="{{ route('approvals.resolve', [$v->id, $c->id]) }}" class="row" style="gap:6px;align-items:center;margin-top:6px">
                @csrf
                <input type="text" name="reason" placeholder="{{ __('Reason') }}" required minlength="5" aria-label="Reason to resolve comment {{ $c->id }}" style="font-size:12px;padding:4px 8px">
                <button type="submit" class="btn sm">{{ __('Mark resolved') }}</button>
              </form>
            @elseif($c->status === 'open' && $c->blocking)
              <span class="muted s">{{ __("Waiting for the lecturer's response") }}</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted" style="text-align:center;padding:20px">{{ __('No comments.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

@if(in_array($v->status, ['submitted', 'in_review']))
  <div class="panel" style="margin-bottom:24px">
    <div class="hd"><h2>{{ __('Decision') }}</h2></div>
    <div class="bd">
      @if($inReview)
        <form method="post" action="{{ route('approvals.endorse', $v->id) }}" style="margin-bottom:24px;border-bottom:1px solid var(--border);padding-bottom:20px">
          @csrf
          <h3>{{ __('Endorse and verify the category') }}</h3>
          <p class="muted s" style="margin-top:0">
            {{ __('Proposed category: :cat. Endorsing records the category you verify, separately from the proposed one.', ['cat' => strtoupper($v->category)]) }}
          </p>
          <div style="margin:16px 0">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" name="category_verified" value="1">
              <span>{{ __('I have checked the category and confirm :cat.', ['cat' => strtoupper($v->category)]) }}</span>
            </label>
          </div>
          <div class="field" style="margin-bottom:16px;max-width:540px">
            <label for="comments">{{ __('Comments (optional)') }}</label>
            <input type="text" id="comments" name="comments" placeholder="{{ __('Endorsement comments for Senate') }}">
          </div>
          @if($blocking > 0)
            <div class="note warn" style="margin-bottom:12px"><p style="margin:0">{{ __('Endorse will be refused: :count blocking comment(s) are not resolved.', ['count' => $blocking]) }}</p></div>
          @endif
          @if($submittedReviews === 0)
            <div class="note warn" style="margin-bottom:12px"><p style="margin:0">{{ __('Endorse will be refused: no academic review has been submitted yet.') }}</p></div>
          @endif
          <button type="submit" class="btn">{{ __('Endorse') }}</button>
        </form>
      @endif

      <div class="row" style="gap:24px;flex-wrap:wrap">
        <form method="post" action="{{ route('approvals.sendback', [$v->id, 'return']) }}" style="flex:1;min-width:280px">
          @csrf
          <h3>{{ __('Request amendment') }}</h3>
          <div class="field" style="margin-bottom:12px">
            <label for="r1">{{ __('Reason') }}</label>
            <input type="text" id="r1" name="reason" required minlength="5" placeholder="{{ __('Specify amendments required') }}">
          </div>
          <button type="submit" class="btn ter sm">{{ __('Return for amendment') }}</button>
        </form>

        <form method="post" action="{{ route('approvals.sendback', [$v->id, 'reject']) }}" style="flex:1;min-width:280px">
          @csrf
          <h3>{{ __('Record not supported') }}</h3>
          <div class="field" style="margin-bottom:12px">
            <label for="r2">{{ __('Reason') }}</label>
            <input type="text" id="r2" name="reason" required minlength="5" placeholder="{{ __('Reason for not supporting') }}">
          </div>
          <button type="submit" class="btn danger sm">{{ __('Not supported') }}</button>
        </form>
      </div>
    </div>
  </div>
@endif

<div class="panel">
  <div class="hd"><h2>{{ __('Decisions') }}</h2></div>
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
          <td><b>{{ $d->stage === 'senate' ? __('Senate') : __('Endorsement') }}</b></td>
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
@endsection
