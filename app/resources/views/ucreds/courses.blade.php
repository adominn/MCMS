@extends('layouts.app')
@section('title', 'UCreds content')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <a href="{{ route('ucreds') }}">{{ __('UCreds exchange') }}</a> /
      <span>{{ __('UCreds content') }}</span>
    </div>
    <h1>{{ __('UCreds content') }}</h1>
    <div class="sub">{{ __('What the UCreds learning platform offers. Reference data only: offerings link to course references here.') }}</div>
  </div>
  <div class="act">
    <a href="{{ route('ucreds') }}" class="btn ghost sm">&larr; {{ __('UCreds exchange') }}</a>
  </div>
</div>


<div class="panel">
  <div class="hd">
    <h2>{{ __('Platform course directory') }}</h2>
    <span class="r small muted" id="ucreds-count">{{ $rows->total() }} {{ __('courses available') }}</span>
  </div>
  <div class="bd">
    <form method="get" action="{{ route('ucreds.courses') }}" data-auto-filter data-target="#ucreds-results" data-count="#ucreds-count" role="search" class="toolbar" style="gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
      <input type="search" name="q" value="{{ $q }}" aria-label="Search UCreds content" placeholder="{{ __('Search by title, course ID or subject code...') }}" style="max-width:320px;height:36px">
      <select name="domain" aria-label="Domain" style="max-width:240px;height:36px">
        <option value="">{{ __('All domains') }}</option>
        @foreach($domains as $d)
          <option value="{{ $d }}" @selected($domain === $d)>{{ $d }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn ter sm">{{ __('Search') }}</button>
      @if($q || $domain)
        <a href="{{ route('ucreds.courses') }}" class="btn ghost sm">{{ __('Clear') }}</a>
      @endif
    </form>

    <div id="ucreds-results">
      <div class="table-wrap">
        <table id="ucreds-courses">
          <thead>
            <tr>
              <th style="width:110px">{{ __('Course ID') }}</th>
              <th>{{ __('Title') }}</th>
              <th>{{ __('Domain') }}</th>
              <th>{{ __('Level') }}</th>
              <th class="num">{{ __('Hours') }}</th>
              <th class="num">{{ __('Fee (MYR)') }}</th>
            </tr>
          </thead>
          <tbody>
          @forelse($rows as $c)
            <tr>
              <td><code>{{ $c->course_id }}</code></td>
              <td>
                @if($c->kind === 'programme')
                  <span class="tag info">{{ __('Programme') }}</span>
                @endif
                <b>{{ $c->title }}</b>
                @if($c->subject_code)
                  <div class="small muted">{{ __('Subject code') }} {{ $c->subject_code }}</div>
                @endif
              </td>
              <td>{{ $c->domain }}</td>
              <td>{{ $c->level }}</td>
              <td class="num">{{ $c->hours !== null ? rtrim(rtrim(number_format((float) $c->hours, 2), '0'), '.') : '—' }}</td>
              <td class="num">
                @if($c->fee === null)
                  —
                @elseif((float) $c->fee == 0)
                  <span class="tag ok">{{ __('Free') }}</span>
                @else
                  <b>{{ number_format((float) $c->fee, 2) }}</b>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="muted" style="text-align:center;padding:24px">{{ __('Nothing matches these filters.') }}</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      @if($rows->hasPages())
        {{ $rows->links('pagination.simple') }}
      @endif
    </div>
  </div>
</div>

<div class="note" style="margin-top:16px">
  <div>
    <b>{{ __('Note on audit records') }}:</b>
    {{ __('The audit read public platform directory pages. Course details and profiles may differ between offerings; the live UCreds course specification decides during cohort creation.') }}
  </div>
</div>
@endsection
