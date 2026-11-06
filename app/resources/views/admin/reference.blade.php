@extends('layouts.app')
@section('title', 'Reference data')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <span>{{ __('Reference data') }}</span>
    </div>
    <h1>{{ __('Reference data') }}</h1>
    <div class="sub">{{ __('PTj, academic programmes, and parent courses. Used across micro-credential proposals, credit transfer matrices, and stackable pathways. Changes are audited.') }}</div>
  </div>
</div>


<nav class="tabs" aria-label="Reference data" style="margin-bottom:16px">
  @foreach($nav as $k => $n)
    <a href="{{ route('reference', $k) }}" @if($k === $entity) aria-current="page" class="active" @endif>
      {{ $n['label'] }}
    </a>
  @endforeach
</nav>

@if($entity === 'course')
  @php
    $groupedCourses = $rows->groupBy(fn ($r) => $r->programme_code ? ($r->programme_code . ' — ' . ($r->programme_name ?? $r->programme_code)) : __('Unassigned Programme'));
  @endphp
  <div style="margin-bottom:24px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <h2 style="margin:0;font-size:18px;line-height:24px;font-weight:600">{{ $nav[$entity]['label'] ?? __('Items') }}</h2>
      <span class="small muted">{{ count($rows) }} {{ __('courses across') }} {{ count($groupedCourses) }} {{ __('programmes') }}</span>
    </div>

    @forelse($groupedCourses as $progLabel => $cRows)
      <details class="accordion-row">
        <summary>
          <span style="font-weight:600;font-size:15px;color:var(--ink)">{{ $progLabel }}</span>
          <span class="s muted" style="margin-left:auto">{{ count($cRows) }} {{ count($cRows) === 1 ? __('course') : __('courses') }}</span>
        </summary>
        <div class="accordion-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>{{ __('Code') }}</th>
                  <th>{{ __('Course name') }}</th>
                  <th>{{ __('Credit') }}</th>
                  <th>{{ __('SLT (hours)') }}</th>
                  <th>{{ __('CLOs') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($cRows as $r)
                  <tr>
                    <td><code>{{ $r->code }}</code></td>
                    <td><b>{{ $r->name }}</b></td>
                    <td>{{ $r->credit }}</td>
                    <td>{{ $r->slt_hours }}</td>
                    <td class="small muted" style="max-width:320px">{{ $r->clos }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </details>
    @empty
      <div class="card muted" style="text-align:center;padding:24px">{{ __('No records found.') }}</div>
    @endforelse
  </div>
@else
<div class="panel" style="margin-bottom:24px">
  <div class="hd">
    <h2>{{ $nav[$entity]['label'] ?? __('Items') }}</h2>
    <span class="r small muted">{{ count($rows) }} {{ __('records') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          @foreach($e['cols'] as $label)
            <th>{{ $label }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        <tr>
          @foreach($e['cols'] as $c => $l)
            <td>
              @if($c === 'code')
                <code>{{ $r->$c }}</code>
              @else
                {{ $r->$c }}
              @endif
            </td>
          @endforeach
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($e['cols']) }}" class="muted" style="text-align:center;padding:24px">{{ __('No records found.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="row" style="align-items:stretch;gap:20px">
  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Add or change :entity', ['entity' => $nav[$entity]['label'] ?? 'record']) }}</h2>
    </div>
    <div class="bd">
      <form method="post" action="{{ route('reference.store', $entity) }}" class="col" style="gap:12px">
        @csrf
        @foreach($e['cols'] as $c => $label)
          <div>
            <label for="f-{{ $c }}" style="font-weight:600">{{ $label }}</label>
            <input type="text" id="f-{{ $c }}" name="{{ $c }}" required style="width:100%">
          </div>
        @endforeach
        <div style="margin-top:8px">
          <button type="submit" class="btn ter">{{ __('Save') }}</button>
          <span class="small muted" style="margin-left:8px">{{ __('An existing code is updated in place.') }}</span>
        </div>
      </form>
    </div>
  </div>

  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Import from CSV') }}</h2>
    </div>
    <div class="bd">
      <form method="post" action="{{ route('reference.import', $entity) }}" enctype="multipart/form-data" class="col" style="gap:12px">
        @csrf
        <p class="small muted" style="margin:0">
          {{ __('Required header column names in first row') }}:<br>
          <code>{{ implode(', ', array_keys($e['cols'])) }}</code>
        </p>
        <div>
          <label for="file" style="font-weight:600">{{ __('CSV file') }}</label>
          <input type="file" id="file" name="file" accept=".csv,text/csv" required style="width:100%">
        </div>
        <div style="margin-top:8px">
          <button type="submit" class="btn">{{ __('Import') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
