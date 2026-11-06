<table>
  <thead><tr><th>{{ __('Participant') }}</th><th>{{ __('Progress') }}</th>@foreach($comps as $c)<th>{{ $c->name_en }}<br><span class="muted"><code>{{ $keys[$c->id] }}</code> {{ __('pass') }} {{ rtrim(rtrim((string) $c->pass_mark, '0'), '.') }}/{{ rtrim(rtrim((string) $c->max_mark, '0'), '.') }}</span></th>@endforeach<th></th></tr></thead>
  <tbody>
  @forelse($enrol as $e)
    <tr>
      <td><b>{{ $e->name }}</b><br><span class="muted s">{{ $e->institution_id }}</span>@if(in_array($e->id, $openCases))<br><span class="tag overdue">{{ __('Integrity case open') }}</span>@endif</td>
      <td>{{ $e->progress_percent !== null ? $e->progress_percent.'%' : '-' }}</td>
      @foreach($comps as $c)
        @php($r = $results[$e->id][$c->id] ?? null)
        <td>
          @if($editable ?? false)
            @if($r && $r->status === 'verified')<b>{{ rtrim(rtrim((string) $r->score, '0'), '.') }}</b> <span class="tag ok">{{ __('Verified') }}</span>
            @else<input type="text" inputmode="decimal" name="score[{{ $e->id }}][{{ $c->id }}]" value="{{ isset($r->score) ? rtrim(rtrim((string) $r->score, '0'), '.') : '' }}" style="max-width:90px" aria-label="{{ $keys[$c->id] }} for {{ $e->name }}">@if($r)<span class="tag {{ $r->status === 'held' ? 'err' : 'warn' }}">{{ $r->status === 'held' ? __('Held') : __('Provisional') }}</span>@endif @endif
          @else
            @if($r && $r->score !== null)<b>{{ rtrim(rtrim((string) $r->score, '0'), '.') }}</b> <span class="tag {{ $r->status === 'verified' ? 'ok' : ($r->status === 'held' || $r->status === 'on_hold' ? 'err' : 'warn') }}">{{ __(['provisional' => 'Provisional', 'held' => 'Held', 'on_hold' => 'Held', 'verified' => 'Verified'][$r->status] ?? ucfirst(str_replace('_', ' ', $r->status))) }}</span>
              @if($r->status === 'verified' && ($changeable ?? false))
                <form method="post" action="{{ route('results.change', $r->id) }}" style="margin-top:4px">@csrf
                  <input type="text" name="new_score" placeholder="{{ __('New mark') }}" style="max-width:90px" aria-label="New mark for {{ $keys[$c->id] }} of {{ $e->name }}"><input type="text" name="reason" placeholder="{{ __('Reason') }}" aria-label="Reason to change {{ $keys[$c->id] }} of {{ $e->name }}"> <button type="submit" class="btn sm">{{ __('Request change') }}</button>
                </form>
              @endif
            @else<span class="muted">{{ __('No mark') }}</span>@endif
          @endif
        </td>
      @endforeach
      <td>@if($flag ?? false)<form method="post" action="{{ route('results.flag', $o->id) }}">@csrf<input type="hidden" name="enrolment_id" value="{{ $e->id }}"><input type="text" name="reason" placeholder="{{ __('Reason') }}" aria-label="Reason to flag {{ $e->name }}"> <button type="submit" class="btn danger sm">{{ __('Flag integrity case') }}</button></form>@endif</td>
    </tr>
  @empty
    <tr><td colspan="{{ 3 + $comps->count() }}" class="muted" style="text-align:center;padding:24px">{{ __('No enrolled participants.') }}</td></tr>
  @endforelse
  </tbody>
</table>
