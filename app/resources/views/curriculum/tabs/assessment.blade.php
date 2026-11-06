<form method="post" action="{{ route('proposals.save', [$v->id, 'assessment']) }}" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  
  @if($v->category === 'umc')
    @if($v->credit_model === 'separate_assessment')
      <div style="background:#f6f2ff;border:1px solid #e8daff;border-left:4px solid #6929c4;padding:12px 14px;border-radius:2px;margin-bottom:16px;font-size:12.5px;color:#491d8b">
        <b>{{ __('Model Penyahhimpunan (c) Berasingan Aktif:') }}</b>
        {{ __('Sila pastikan komponen pentaksiran bersepadu (integrated summative assessment) dihubungkan kepada Modul Pentaksiran yang telah ditetapkan.') }}
      </div>
    @elseif($v->credit_model === 'credit_based')
      <div style="background:#edf5ff;border:1px solid #d0e2ff;border-left:4px solid #0f62fe;padding:12px 14px;border-radius:2px;margin-bottom:16px;font-size:12.5px;color:#0043ce">
        <b>{{ __('Model Penyahhimpunan (a) Berasaskan Kredit Aktif:') }}</b>
        {{ __('Setiap modul mempunyai nilai kredit kendiri (≥ 40 jam SLT) dan merangkumi pentaksiran tersendiri.') }}
      </div>
    @elseif($v->credit_model === 'topic_based')
      <div style="background:#e5f6f6;border:1px solid #9ef0f0;border-left:4px solid #005d5d;padding:12px 14px;border-radius:2px;margin-bottom:16px;font-size:12.5px;color:#005d5d">
        <b>{{ __('Model Penyahhimpunan (b) Berasaskan Topik Aktif:') }}</b>
        {{ __('Modul dibentuk mengikut kelompok topik pembelajaran dengan agihan jam SLT yang bersesuaian.') }}
      </div>
    @endif
  @endif

  <label for="assessment_standard">{{ __('Pass or completion standard') }}</label><textarea id="assessment_standard" name="assessment_standard" rows="2">{{ $v->assessment_standard }}</textarea>
  <label for="integrity_mechanism">{{ __('Identity and integrity mechanism') }}</label><textarea id="integrity_mechanism" name="integrity_mechanism" rows="2">{{ $v->integrity_mechanism }}</textarea>
  <label for="feedback_method">{{ __('Feedback to participants') }}</label><textarea id="feedback_method" name="feedback_method" rows="2">{{ $v->feedback_method }}</textarea>
  <h2>{{ __('Components and alignment') }}</h2>
  <table>
    <thead><tr><th>{{ __('Component') }}</th><th>{{ __('Module') }}</th><th>{{ __('Weight %') }}</th><th>{{ __('Mark column') }}</th><th>{{ __('Max mark') }}</th><th>{{ __('Pass mark') }}</th>@foreach($los as $lo)<th>{{ $lo->code }}</th>@endforeach<th></th></tr></thead>
    <tbody>
    @forelse($comps as $c)
      @php($linked = ($links[$c->id] ?? collect())->pluck('learning_outcome_id')->all())
      <tr>
        <td><input type="text" name="rows[{{ $c->id }}][name_en]" value="{{ $c->name_en }}" aria-label="Component name"></td>
        <td><select name="rows[{{ $c->id }}][mc_module_id]" aria-label="Module assessed by {{ $c->name_en ?: 'the component' }}"><option value="">{{ __('Whole micro-credential') }}</option>@foreach($modules as $m)<option value="{{ $m->id }}" @selected($c->mc_module_id == $m->id)>Module {{ $m->seq }}{{ $m->name_en ? ': '.$m->name_en : '' }}@if($m->is_assessment) [{{ __('Integrated Assessment Module') }}]@endif</option>@endforeach</select></td>
        <td><input type="text" inputmode="decimal" name="rows[{{ $c->id }}][weight_percent]" value="{{ rtrim(rtrim(number_format((float) $c->weight_percent, 1), '0'), '.') }}" style="max-width:80px" aria-label="Weight in percent"></td>
        <td><input type="text" name="rows[{{ $c->id }}][csv_key]" value="{{ $c->csv_key }}" placeholder="module1_mark" style="max-width:130px" aria-label="Mark column of the component"></td>
        <td><input type="text" inputmode="decimal" name="rows[{{ $c->id }}][max_mark]" value="{{ rtrim(rtrim((string) $c->max_mark, '0'), '.') }}" style="max-width:70px" aria-label="Maximum mark"></td>
        <td><input type="text" inputmode="decimal" name="rows[{{ $c->id }}][pass_mark]" value="{{ rtrim(rtrim((string) $c->pass_mark, '0'), '.') }}" style="max-width:70px" aria-label="Pass mark"></td>
        @foreach($los as $lo)<td><input type="checkbox" name="align[{{ $c->id }}][]" value="{{ $lo->id }}" @checked(in_array($lo->id, $linked)) aria-label="{{ $c->name_en ?: 'Component' }} assesses {{ $lo->code }}"></td>@endforeach
        <td>@unless($locked)<button type="submit" name="remove" value="{{ $c->id }}" class="btn ter sm">{{ __('Remove') }}</button>@endunless</td>
      </tr>
    @empty
      <tr><td colspan="{{ 7 + $los->count() }}" class="muted">{{ __('No assessment components yet.') }}</td></tr>
    @endforelse
    <tr><td><b>{{ __('Total') }}</b></td><td><b>{{ rtrim(rtrim(number_format((float) $comps->sum('weight_percent'), 1), '0'), '.') }}%</b></td><td colspan="{{ 5 + $los->count() }}"></td></tr>
    </tbody>
  </table>
  @unless($locked)<p><button type="submit" name="action" value="add" class="btn ter sm">{{ __('Add component') }}</button> <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button></p>@endunless
  </fieldset>
</form>
