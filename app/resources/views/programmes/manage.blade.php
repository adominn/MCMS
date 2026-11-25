@extends('layouts.app')
@section('title', __('Manage Curriculum').' - '.$programme->code)
@section('content')
<x-breadcrumb :crumbs="[
  __('Degree Programmes') => route('programmes.index'),
  $programme->code => route('programmes.show', $programme->code),
  __('Manage Curriculum') => null,
]" />

<div class="ph" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
  <div>
    <h1>{{ __('Manage Curriculum:') }} {{ $programme->code }}</h1>
    <div class="sub">{{ $programme->name }} &middot; {{ __('Create or clone annual curriculum versions without code changes.') }}</div>
  </div>
  <div class="act">
    <a class="btn ter sm" href="{{ route('programmes.show', $programme->code) }}">{{ __('View Public Plan') }}</a>
  </div>
</div>

@if($errors->any())
  <div class="note err" style="margin-bottom:16px">
    <ul style="margin:0;padding-left:18px">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

<!-- Clone / Create New Academic Session -->
<div class="panel" style="margin-bottom:20px">
  <div class="hd"><h2 style="margin:0;font-size:15px">{{ __('Create or Clone Curriculum Version for a New Academic Session') }}</h2></div>
  <form method="post" action="{{ route('programmes.versions.clone', $programme->code) }}" class="bd" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;padding:16px 20px" data-inplace>
    @csrf
    <div>
      <label for="academic_session" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('New Academic Session') }}</label>
      <input id="academic_session" name="academic_session" type="text" placeholder="2026/2027" required style="width:160px">
    </div>
    <div>
      <label for="source_version_id" class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Clone From Existing Version') }}</label>
      <select id="source_version_id" name="source_version_id" style="min-width:220px">
        <option value="">{{ __('Start blank') }}</option>
        @foreach($plan['versions'] as $ver)
          <option value="{{ $ver->id }}">{{ $ver->academic_session }} ({{ ucfirst($ver->status) }} &middot; {{ $ver->total_credits }} cr)</option>
        @endforeach
      </select>
    </div>
    <button type="submit" class="btn primary sm">{{ __('Create Draft Version') }}</button>
  </form>
</div>

@if($plan['version'])
  <div class="panel" style="margin-bottom:20px">
    <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <h2 style="margin:0;font-size:15px">{{ __('Editing Session:') }} {{ $plan['version']->academic_session }}</h2>
        <span class="tag {{ $plan['version']->status === 'active' ? 'ok' : ($plan['version']->status === 'draft' ? 'info' : 'neutral') }}">{{ ucfirst($plan['version']->status) }}</span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <form method="get" action="{{ route('programmes.manage', $programme->code) }}" style="display:inline">
          <select name="version" onchange="this.form.submit()" style="height:34px;padding:0 10px">
            @foreach($plan['versions'] as $ver)
              <option value="{{ $ver->id }}" @selected($ver->id === $plan['version']->id)>{{ $ver->academic_session }} ({{ ucfirst($ver->status) }})</option>
            @endforeach
          </select>
        </form>
        @if($plan['version']->status === 'draft' && $canPublish)
          <form method="post" action="{{ route('programmes.versions.publish', $plan['version']->id) }}" style="display:inline" data-inplace>
            @csrf
            <button type="submit" class="btn primary sm">{{ __('Publish as Active Version') }}</button>
          </form>
        @endif
      </div>
    </div>

    <div class="bd" style="padding:16px 20px">
      @if($validation)
        <div class="note {{ $validation['valid'] ? 'ok' : 'warn' }}" style="margin-bottom:16px">
          @if($validation['valid'])
            <strong>{{ __('Validation passed:') }}</strong> {{ mcms_num($validation['placed_credits']) }} / {{ mcms_num($validation['target_credits']) }} {{ __('credits match programme requirement with no circular prerequisites.') }}
          @else
            <strong>{{ __('Validation issues:') }}</strong> {{ implode(' ', $validation['errors']) }}
          @endif
        </div>
      @endif

      @if($plan['version']->status === 'draft')
        <div class="cols" style="gap:20px;margin-bottom:20px">
          <form method="post" action="{{ route('programmes.placements.store', $plan['version']->id) }}" class="panel" style="flex:1.3" data-inplace>
            @csrf
            <div class="hd"><h3 style="margin:0;font-size:14px">{{ __('Add Course to Semester') }}</h3></div>
            <div class="bd" style="padding:14px 16px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
              <div style="grid-column:1/-1">
                <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Course') }}</label>
                <select name="parent_course_id" required style="width:100%">
                  @foreach($allCourses as $c)
                    <option value="{{ $c->id }}">{{ $c->code }} &ndash; {{ $c->name }} ({{ $c->credit }} cr)</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Year') }}</label>
                <select name="year_no" style="width:100%">
                  @for($y = 1; $y <= 4; $y++) <option value="{{ $y }}">{{ __('Year') }} {{ $y }}</option> @endfor
                </select>
              </div>
              <div>
                <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Semester') }}</label>
                <select name="semester_no" style="width:100%">
                  <option value="1">{{ __('Semester 1') }}</option>
                  <option value="2">{{ __('Semester 2') }}</option>
                  <option value="3">{{ __('Short Semester') }}</option>
                </select>
              </div>
              <div>
                <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Component') }}</label>
                <select name="component_code" style="width:100%">
                  @foreach($componentLabels as $k => $lbl)
                    <option value="{{ $k }}">{{ $k }} &ndash; {{ __($lbl) }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Requirement') }}</label>
                <select name="requirement_type" style="width:100%">
                  <option value="required">{{ __('Required') }}</option>
                  <option value="elective_slot">{{ __('Elective Slot') }}</option>
                </select>
              </div>
              <div style="grid-column:1/-1">
                <button type="submit" class="btn primary sm">{{ __('Add Course') }}</button>
              </div>
            </div>
          </form>

          <form method="post" action="{{ route('programmes.prerequisites.store', $plan['version']->id) }}" class="panel" style="flex:1" data-inplace>
            @csrf
            <div class="hd"><h3 style="margin:0;font-size:14px">{{ __('Add Prerequisite') }}</h3></div>
            <div class="bd" style="padding:14px 16px;display:flex;flex-direction:column;gap:10px">
              <div>
                <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Course') }}</label>
                <select name="parent_course_id" required style="width:100%">
                  @foreach($allCourses as $c)
                    <option value="{{ $c->id }}">{{ $c->code }} &ndash; {{ $c->name }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="s" style="display:block;font-weight:600;margin-bottom:4px">{{ __('Requires Prior Course') }}</label>
                <select name="prerequisite_course_id" required style="width:100%">
                  @foreach($allCourses as $c)
                    <option value="{{ $c->id }}">{{ $c->code }} &ndash; {{ $c->name }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <button type="submit" class="btn sec sm">{{ __('Add Prerequisite') }}</button>
              </div>
            </div>
          </form>
        </div>
      @endif

      @foreach($plan['years'] as $year)
        @foreach($year->semesters as $sem)
          <div style="margin-bottom:14px">
            <div style="font-weight:700;font-size:13px;margin-bottom:6px">
              {{ __($year->label) }} &ndash; {{ __($sem->label) }} ({{ mcms_num($sem->credits) }} cr)
            </div>
            <table style="margin:0">
              <thead>
                <tr>
                  <th style="width:120px">{{ __('Code') }}</th>
                  <th>{{ __('Course') }}</th>
                  <th style="width:60px">{{ __('Type') }}</th>
                  <th class="num" style="width:70px">{{ __('Credits') }}</th>
                  @if($plan['version']->status === 'draft')
                    <th style="width:90px"></th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @foreach($sem->courses as $c)
                  <tr>
                    <td style="font-family:var(--font-mono);font-size:12px">{{ $c->code }}</td>
                    <td>{{ $c->name }}</td>
                    <td><span class="tag neutral">{{ $c->component_code }}</span></td>
                    <td class="num">{{ mcms_num($c->credits) }}</td>
                    @if($plan['version']->status === 'draft')
                      <td class="num">
                        <form method="post" action="{{ route('programmes.placements.destroy', $c->placement_id) }}" style="display:inline" data-inplace>
                          @csrf
                          @method('delete')
                          <button type="submit" class="btn sm ter" style="color:var(--color-error, #da1e28)">{{ __('Remove') }}</button>
                        </form>
                      </td>
                    @endif
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endforeach
      @endforeach
    </div>
  </div>
@endif
@endsection
