@extends('layouts.app')
@section('title', 'Feedback')
@section('content')
<div class="crumb" style="margin-bottom:8px"><a href="{{ route('feedback') }}">{{ __('Feedback') }}</a> <span>/</span> <span>{{ $e->name_en }} {{ $e->cohort_name }}</span></div>
<div class="title"><div><h1>{{ $e->name_en }} {{ $e->cohort_name }}</h1><div class="sub">{{ $e->reference }} &middot; {{ \Carbon\Carbon::parse($e->start_date)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($e->end_date)->format('d/m/Y') }} &middot; @if($e->given)<span class="tag ok">{{ __("Given") }}</span>@else<span class="tag warn">{{ __("Not given yet") }}</span>@endif</div></div></div>
@if($e->given)
  <div class="note ok" style="margin-bottom:16px">Thank you, your feedback is recorded. It cannot be changed.</div>
@else
  <div class="note" style="margin-bottom:16px">Rate from 1 (poor) to 5 (excellent). The six overall ratings are required; the others help us most when you answer them.</div>
@endif
<form method="post" action="{{ route('feedback.submit', $e->id) }}" id="feedback-form">@csrf
  @foreach($groups as $group => $items)
  <div class="panel" style="margin-bottom:16px"><div class="hd"><h2>{{ $group }}</h2></div>
    <table>
      <thead><tr><th>{{ __("Rate") }}</th><th class="num" style="width:36px">1</th><th class="num" style="width:36px">2</th><th class="num" style="width:36px">3</th><th class="num" style="width:36px">4</th><th class="num" style="width:36px">5</th></tr></thead>
      <tbody>
      @foreach($items as $k => $label)
        <tr><td>{{ $label }}@if(in_array($k, $required, true)) <span class="muted small">required</span>@endif</td>
          @for($n = 1; $n <= 5; $n++)<td class="num"><input type="radio" name="rating[{{ $k }}]" value="{{ $n }}" aria-label="{{ $label }}: {{ $n }}" @if($e->given) disabled @checked(($answers[$k] ?? null) === $n) @elseif(in_array($k, $required, true)) @checked($n === 4) @endif></td>@endfor</tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endforeach
  <div class="panel"><div class="bd col">
    <div class="field"><label for="comment">{{ __("Comment") }}</label><textarea id="comment" name="comment" rows="3" @disabled($e->given)>{{ $e->given->comment ?? '' }}</textarea></div>
    @unless($e->given)<div style="display:flex;gap:8px"><button type="submit">{{ __("Send feedback") }}</button><a class="btn ter" href="{{ route('feedback') }}">{{ __("Back") }}</a></div>@else<div><a class="btn ter" href="{{ route('feedback') }}">{{ __("Back to feedback") }}</a></div>@endunless
  </div></div>
</form>
@endsection
