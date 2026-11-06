<form method="post" action="{{ route('proposals.submit', $v->id) }}" class="card">@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  <h2>{{ __('Intellectual property') }}</h2>
  <p><label style="display:inline"><input type="checkbox" name="ip_confirmed" value="1" @checked($v->ip_confirmed)> {{ __('I confirm that third-party materials respect copyright and intellectual property rules.') }}</label></p>
  <label for="ip_note">{{ __('Note on any licensed material') }}</label><textarea id="ip_note" name="ip_note" rows="2">{{ $v->ip_note }}</textarea>
  @if($checks['warnings'])
    <h2>{{ __('Warnings need a written response') }}</h2>
    <ul>@foreach($checks['warnings'] as $c)<li>{{ __($c['msg']) }}</li>@endforeach</ul>
    <label for="response">{{ __('Your response to the warnings') }}</label><textarea id="response" name="response" rows="3">{{ $v->submit_response }}</textarea>
  @endif
  @unless($locked)
    @php($others = collect($checks['blocking'])->where('fr', '!=', 'FR-47')->count()) {{-- the IP box is ticked on this very form --}}
    <p>
      <button type="submit" class="btn" @disabled($others > 0)>{{ __('Submit for review') }}</button>
      @if($others > 0)<span class="muted s" style="margin-left:8px">{{ __('Submit is off: :count blocking error(s) in the checks panel.', ['count' => $others]) }}</span>@endif
    </p>
  @else
    <p><b>{{ __('Submitted') }}</b> {{ \Carbon\Carbon::parse($v->submitted_at)->format('d/m/Y H:i') }}. @if($v->submit_response){{ __('Your response: :resp', ['resp' => $v->submit_response]) }}@endif</p>
  @endunless
  </fieldset>
</form>
