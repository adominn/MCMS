<form method="post" action="{{ route('proposals.save', [$v->id, 'assessment']) }}" class="card" data-autosave>@csrf
  <fieldset @disabled($locked) style="border:0;padding:0;margin:0">
  
  @if($v->category === 'umc')
    @if($v->credit_model === 'separate_assessment')
      <div style="background:var(--info-bg);border:1px solid var(--hairline);border-left:4px solid var(--accent, #0f62fe);padding:12px 14px;border-radius:2px;margin-bottom:16px;font-size:12.5px;color:var(--ink)">
        <b>{{ __('Active Unbundling Model (c) Separate Learning & Assessment:') }}</b>
        {{ __('Please ensure integrated summative assessment components are mapped to the designated Assessment Module.') }}
      </div>
    @elseif($v->credit_model === 'credit_based')
      <div style="background:var(--info-bg);border:1px solid var(--hairline);border-left:4px solid var(--accent, #0f62fe);padding:12px 14px;border-radius:2px;margin-bottom:16px;font-size:12.5px;color:var(--ink)">
        <b>{{ __('Active Unbundling Model (a) Credit-Based:') }}</b>
        {{ __('Each module has its own credit value (≥ 40 hours SLT) and encompasses independent assessments.') }}
      </div>
    @elseif($v->credit_model === 'topic_based')
      <div style="background:var(--info-bg);border:1px solid var(--hairline);border-left:4px solid var(--accent, #0f62fe);padding:12px 14px;border-radius:2px;margin-bottom:16px;font-size:12.5px;color:var(--ink)">
        <b>{{ __('Active Unbundling Model (b) Topic-Based:') }}</b>
        {{ __('Modules are formed by learning topic clusters with flexible student learning time distribution.') }}
      </div>
    @endif
  @endif

  <label for="assessment_standard">{{ __('Pass or completion standard') }}</label><textarea id="assessment_standard" name="assessment_standard" rows="2">{{ $v->assessment_standard }}</textarea>
  <label for="integrity_mechanism">{{ __('Identity and integrity mechanism') }}</label><textarea id="integrity_mechanism" name="integrity_mechanism" rows="2">{{ $v->integrity_mechanism }}</textarea>
  <label for="feedback_method">{{ __('Feedback to participants') }}</label><textarea id="feedback_method" name="feedback_method" rows="2">{{ $v->feedback_method }}</textarea>
  <h2>{{ __('Components and alignment') }}</h2>
  <div class="table-wrap">
    <table style="width:100%;min-width:680px">
      <thead><tr><th>{{ __('Component') }}</th><th>{{ __('Module') }}</th><th>{{ __('Weight %') }}</th><th>{{ __('Mark column') }}</th><th>{{ __('Max mark') }}</th><th>{{ __('Pass mark') }}</th>@foreach($los as $lo)<th>{{ $lo->code }}</th>@endforeach<th></th></tr></thead>
      <tbody>
      @forelse($comps as $c)
        @php($linked = ($links[$c->id] ?? collect())->pluck('learning_outcome_id')->all())
        <tr>
          <td><input type="text" name="rows[{{ $c->id }}][name_en]" value="{{ $c->name_en }}" aria-label="Component name"></td>
          <td><select name="rows[{{ $c->id }}][mc_module_id]" aria-label="Module assessed by {{ $c->name_en ?: 'the component' }}" style="min-width:180px"><option value="">{{ __('Whole micro-credential') }}</option>@foreach($modules as $m)<option value="{{ $m->id }}" @selected($c->mc_module_id == $m->id)>Module {{ $m->seq }}{{ $m->name_en ? ': '.$m->name_en : '' }}@if($m->is_assessment) [{{ __('Integrated Assessment Module') }}]@endif</option>@endforeach</select></td>
          <td><input type="text" inputmode="decimal" name="rows[{{ $c->id }}][weight_percent]" value="{{ mcms_num($c->weight_percent, 1) }}" style="max-width:80px" aria-label="Weight in percent"></td>
          <td><input type="text" name="rows[{{ $c->id }}][csv_key]" value="{{ $c->csv_key }}" placeholder="module1_mark" style="max-width:130px" aria-label="Mark column of the component"></td>
          <td><input type="text" inputmode="decimal" name="rows[{{ $c->id }}][max_mark]" value="{{ mcms_num($c->max_mark) }}" style="max-width:70px" aria-label="Maximum mark"></td>
          <td><input type="text" inputmode="decimal" name="rows[{{ $c->id }}][pass_mark]" value="{{ mcms_num($c->pass_mark) }}" style="max-width:70px" aria-label="Pass mark"></td>
          @foreach($los as $lo)<td><input type="checkbox" name="align[{{ $c->id }}][]" value="{{ $lo->id }}" @checked(in_array($lo->id, $linked)) aria-label="{{ $c->name_en ?: 'Component' }} assesses {{ $lo->code }}"></td>@endforeach
          <td>@unless($locked)<button type="submit" name="remove" value="{{ $c->id }}" class="btn ter sm">{{ __('Remove') }}</button>@endunless</td>
        </tr>
      @empty
        <tr><td colspan="{{ 7 + $los->count() }}" class="muted">{{ __('No assessment components yet.') }}</td></tr>
      @endforelse
      <tr><td><b>{{ __('Total') }}</b></td><td><b>{{ mcms_num($comps->sum('weight_percent'), 1) }}%</b></td><td colspan="{{ 5 + $los->count() }}"></td></tr>
      </tbody>
    </table>
  </div>
  @unless($locked)<p><button type="submit" name="action" value="add" class="btn ter sm">{{ __('Add component') }}</button> <button type="submit" name="action" value="save" class="btn sm">{{ __('Save') }}</button></p>@endunless
  </fieldset>
</form>
