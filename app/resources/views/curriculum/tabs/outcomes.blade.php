<form method="post" action="{{ route('proposals.save', [$v->id, 'outcomes']) }}" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  <table>
    <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Outcome (English)') }}</th><th>{{ __('Outcome (Bahasa Melayu)') }}</th><th></th></tr></thead>
    <tbody>
    @forelse($los as $lo)
      <tr><td><b>{{ $lo->code }}</b></td>
        <td><input type="text" name="rows[{{ $lo->id }}][text_en]" value="{{ $lo->text_en }}" aria-label="{{ $lo->code }} in English"></td>
        <td><input type="text" name="rows[{{ $lo->id }}][text_ms]" value="{{ $lo->text_ms }}" aria-label="{{ $lo->code }} in Bahasa Melayu"></td>
        <td>@unless($locked)<button type="submit" name="remove" value="{{ $lo->id }}" class="btn ter sm">{{ __('Remove') }}</button>@endunless</td></tr>
    @empty
      <tr><td colspan="4" class="muted">{{ __('No learning outcomes yet.') }}</td></tr>
    @endforelse
    </tbody>
  </table>
  @unless($locked)<p><button type="submit" name="action" value="add" class="btn ter sm">{{ __('Add outcome') }}</button> <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button></p>@endunless
  </fieldset>
</form>
