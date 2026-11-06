@extends('layouts.app')
@section('title', __('Search'))
@section('content')
<div class="title"><div><h1>{{ __('Search') }}</h1><div class="sub">@if(mb_strlen($q) >= 2){{ __('Results for ":q"', ['q' => $q]) }}@else{{ __('Type at least two letters in the search box above.') }}@endif</div></div></div>
@if(mb_strlen($q) >= 2)
<div class="panel" style="margin-bottom:16px"><div class="hd"><h2>{{ __('Micro-credentials') }}</h2><span class="r">{{ $mcs->count() }}</span></div>
  <table><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Category') }}</th><th>{{ __('Latest version') }}</th></tr></thead><tbody>
  @forelse($mcs as $m)<tr><td><a href="{{ route('mcs.show', $m->id) }}">{{ $m->name_en }}</a>@if($m->code)<span class="s">{{ $m->code }}</span>@endif</td><td><span class="cat">{{ strtoupper($m->category) }}</span></td><td>{{ __(\App\Modules\Approval\ApprovalController::STATUS[$m->status] ?? ucfirst($m->status)) }}</td></tr>@empty<tr><td colspan="3" class="muted">{{ __('Nothing matches.') }}</td></tr>@endforelse
  </tbody></table></div>
<div class="panel" style="margin-bottom:16px"><div class="hd"><h2>{{ __('Cohorts') }}</h2><span class="r">{{ $cohorts->count() }}</span></div>
  <table><thead><tr><th>{{ __('Cohort') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
  @forelse($cohorts as $c)<tr><td><a href="{{ route('offerings.show', $c->id) }}">{{ $c->name_en }} {{ $c->cohort_name }}</a></td><td>{{ __(ucfirst($c->status)) }}</td></tr>@empty<tr><td colspan="2" class="muted">{{ __('Nothing matches.') }}</td></tr>@endforelse
  </tbody></table></div>
@if(auth()->user()->hasAnyRole('deputy_dean', 'codl_admin'))
<div class="panel"><div class="hd"><h2>{{ __('People') }}</h2><span class="r">{{ $people->count() }}</span></div>
  <table><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th></tr></thead><tbody>
  @forelse($people as $p)<tr><td>@if(auth()->user()->hasAnyRole('codl_admin'))<a href="{{ route('users.show', $p->id) }}">{{ $p->name }}</a>@else{{ $p->name }}@endif</td><td>{{ $p->email }}</td></tr>@empty<tr><td colspan="2" class="muted">{{ __('Nothing matches.') }}</td></tr>@endforelse
  </tbody></table></div>
@endif
@endif
@endsection
