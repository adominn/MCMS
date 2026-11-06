@extends('layouts.app')
@section('title', __('Review'))
@section('content')
<div class="crumb">
  <a href="{{ route('reviews') }}">{{ auth()->user()->hasAnyRole('lecturer') ? __('Reviews for colleagues') : __('My review invitations') }}</a>
  <span>/</span>
  <span>{{ $v->name_en }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ $a->kind === 'expert' ? __('Comment on a proposal') : __('Review a proposal') }}: {{ $v->name_en }}</h1>
    <div class="sub">
      {{ __('Due :date', ['date' => \Carbon\Carbon::parse($a->due_on)->format('d/m/Y')]) }} &middot; {{ __('Scope: :scope', ['scope' => $a->scope]) }}@if(!empty($v->category)) &middot; <x-cat :c="$v->category" />@endif
    </div>
  </div>
  <div class="act">
    <a class="btn ter sm" href="{{ route('reviews') }}">{{ __('Back to invitations') }}</a>
  </div>
</div>

@if($status === 'invited')
  <form method="post" action="{{ route('reviews.coi', $a->id) }}" style="max-width:680px">
    @csrf
    <div class="panel">
      <div class="hd"><h2>{{ __('Step 1: conflict of interest') }}</h2></div>
      <div class="bd">
        <p class="muted" style="margin-top:0">
          {{ __('The proposal stays closed until you answer. Declaring a conflict of interest declines the review and returns the task to the deputy dean to assign another reviewer.') }}
        </p>

        <div class="col" style="gap:12px;margin:20px 0">
          <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
            <input type="radio" name="conflict" value="none" required style="margin-top:3px">
            <div>
              <b>{{ __('I have no conflict of interest') }}</b>
              <div class="s" style="color:var(--muted);font-size:12px">{{ __('You did not co-develop this micro-credential, have no supervisory relationship with the author, and have no personal or commercial interest in it.') }}</div>
            </div>
          </label>
          <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
            <input type="radio" name="conflict" value="conflict" style="margin-top:3px">
            <div>
              <b>{{ __('I have a conflict of interest') }}</b>
              <div class="s" style="color:var(--muted);font-size:12px">{{ __('Declines this invitation immediately.') }}</div>
            </div>
          </label>
        </div>

        <div class="row" style="gap:12px">
          <button type="submit" class="btn">{{ __('Confirm') }}</button>
          <a class="btn ter" href="{{ route('reviews') }}">{{ __('Cancel') }}</a>
        </div>
      </div>
    </div>
  </form>
@elseif($status === 'declined')
  <div class="note warn" role="alert" style="max-width:680px">
    <b>{{ __('Declined:') }}</b> {{ __('You declared a conflict of interest and were removed from this review.') }}
  </div>
  <div style="margin-top:16px">
    <a class="btn ter" href="{{ route('reviews') }}">{{ __('Back to invitations') }}</a>
  </div>
@else
  <div style="margin-bottom:20px">
    @include('approval._summary')
  </div>

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
              <textarea id="text" name="text" rows="3" required minlength="5" placeholder="{{ __('Share your feedback on competencies and relevance') }}"></textarea>
            </div>
            <button type="submit" class="btn">{{ __('Send comments') }}</button>
          </div>
        </div>
      </form>
    @else
      <div class="note info"><p class="muted" style="margin:0">{{ __('Comments are closed: the decision is recorded.') }}</p></div>
    @endif
  @elseif($status === 'submitted')
    <div class="panel">
      <div class="hd">
        <h2>{{ __('Your review') }}</h2>
        <span class="r">
          <span class="tag ok">{{ __(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$review->recommendation] ?? ucfirst($review->recommendation)) }}</span>
        </span>
      </div>
      <div class="bd">
        <dl class="dl">
          <dt>{{ __('Recommendation') }}</dt>
          <dd><b>{{ __(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$review->recommendation] ?? $review->recommendation) }}</b></dd>
          <dt>{{ __('Summary') }}</dt>
          <dd>{{ $review->summary }}</dd>
        </dl>
      </div>
    </div>
  @else
    @php($list = collect($items)->when($v->category !== 'umc', fn ($c) => $c->except('parent_mapping')))
    <form method="post" action="{{ route('reviews.submit', $a->id) }}">
      @csrf
      <div class="panel" style="margin-bottom:24px">
        <div class="hd"><h2>{{ __('Step 2: rate each item') }}</h2></div>
        <div class="bd" style="padding:0">
          <table>
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
              <tr>
                <td><b>{{ __($label) }}</b></td>
                <td>
                  <select name="rating[{{ $k }}]" aria-label="{{ __('Rating for :item', ['item' => $label]) }}">
                    <option value="">-</option>
                    <option value="satisfactory" @selected(old("rating.$k") === 'satisfactory')>{{ __('Satisfactory') }}</option>
                    <option value="needs_amendment" @selected(old("rating.$k") === 'needs_amendment')>{{ __('Needs amendment') }}</option>
                  </select>
                </td>
                <td>
                  <input type="text" name="comment[{{ $k }}]" value="{{ old("comment.$k") }}" aria-label="{{ __('Comment for :item', ['item' => $label]) }}" placeholder="{{ __('Required if needs amendment') }}">
                </td>
                <td style="text-align:center">
                  <input type="checkbox" name="blocking[{{ $k }}]" value="1" aria-label="Mark the comment on {{ $label }} as blocking">
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
          <div class="field" style="margin-bottom:16px;max-width:320px">
            <label for="recommendation">{{ __('Recommendation') }}</label>
            <select id="recommendation" name="recommendation">
              <option value="">-</option>
              <option value="support">{{ __('Support') }}</option>
              <option value="amend">{{ __('Amend') }}</option>
              <option value="not_support">{{ __('Do not support') }}</option>
            </select>
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
  @endif
@endif
@endsection
