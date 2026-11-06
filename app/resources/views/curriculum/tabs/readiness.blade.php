<form method="post" action="{{ route('proposals.save', [$v->id, 'readiness']) }}" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  <table>
    <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Status') }}</th><th>{{ __('Evidence note') }}</th></tr></thead>
    <tbody>
    @foreach($cfg['readiness'] as $k => $l)
      @php($r = $ready[$k] ?? null)
      <tr><td><label for="r-{{ $k }}" style="margin:0"><b>{{ __($l) }}</b></label></td>
        <td><select id="r-{{ $k }}" name="ready[{{ $k }}][status]">@foreach(['yes' => 'Yes', 'not_yet' => 'Not yet', 'na' => 'Not applicable'] as $sv => $sl)<option value="{{ $sv }}" @selected(($r->status ?? 'not_yet') === $sv)>{{ __($sl) }}</option>@endforeach</select></td>
        <td><input type="text" name="ready[{{ $k }}][note]" value="{{ $r->note ?? '' }}" aria-label="Evidence note for {{ $l }}"></td></tr>
    @endforeach
    </tbody>
  </table>
  @unless($locked)<p><button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button></p>@endunless
  </fieldset>
</form>
