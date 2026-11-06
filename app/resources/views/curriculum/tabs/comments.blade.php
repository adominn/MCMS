<div class="card">
  <h2>{{ __('Review comments') }}</h2>
  @forelse($comments as $c)
    <div class="card" style="margin-bottom:12px">
      <p><b>{{ $c->author }}</b> @if($c->blocking)<span class="tag overdue">{{ __('Blocking') }}</span>@endif <span class="tag">{{ __(['open' => 'Open', 'answered' => 'Answered', 'resolved' => 'Resolved'][$c->status]) }}</span></p>
      <p>{{ $c->text }}</p>
      @if($c->response)<p><b>{{ __('Your response:') }}</b> {{ $c->response }}</p>@endif
      @if($c->resolve_reason)<p class="muted">{{ __('Resolved by the deputy dean: :reason', ['reason' => $c->resolve_reason]) }}</p>@endif
      @if($c->status === 'open')
        <form method="post" action="{{ route('comments.respond', [$v->id, $c->id]) }}">@csrf
          <label for="resp-{{ $c->id }}">{{ __('Your response') }}</label><input type="text" id="resp-{{ $c->id }}" name="response" required>
          <p><button type="submit" class="btn sm">{{ __('Send response') }}</button></p>
        </form>
      @endif
    </div>
  @empty
    <p class="muted">{{ __('No comments yet.') }}</p>
  @endforelse
</div>
