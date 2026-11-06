@extends('layouts.app')
@section('title', 'Templates')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <span>{{ __('Templates') }}</span>
    </div>
    <h1>{{ __('Templates') }}</h1>
    <div class="sub">{{ __('Notices, certificates, credential pages, and recognition statements. Configured in both Bahasa Melayu and English. Every change produces a new version.') }}</div>
  </div>
</div>

<div class="kpis">
  <div class="kpi">
    <div class="l">{{ __('Total templates') }}</div>
    <div class="v">{{ count($templates) }}</div>
    <div class="s">{{ __('System document templates') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Bilingual') }}</div>
    <div class="v">BM &middot; EN</div>
    <div class="s">{{ __('Parallel language texts') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Versioning') }}</div>
    <div class="v">{{ __('Versioned') }}</div>
    <div class="s">{{ __('Earlier versions kept') }}</div>
  </div>
  <div class="kpi">
    <div class="l">{{ __('Notice rules') }}</div>
    <div class="v">FR-267</div>
    <div class="s">{{ __('Mandatory notice enforcement') }}</div>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Registered document & notification templates') }}</h2>
    <span class="r small muted">{{ count($templates) }} {{ __('templates') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Template description') }}</th>
          <th>{{ __('Key') }}</th>
          <th>{{ __('Kind') }}</th>
          <th>{{ __('Policy') }}</th>
          <th class="num">{{ __('Action') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($templates as $t)
        <tr>
          <td>
            <b>{{ $t->description }}</b>
          </td>
          <td><code>{{ $t->key }}</code></td>
          <td>
            <span class="tag {{ $t->kind === 'notification' ? 'info' : ($t->kind === 'certificate' ? 'ok' : 'muted') }}">
              {{ ucfirst($t->kind) }}
            </span>
          </td>
          <td>
            @if($t->kind === 'notification')
              <span class="tag {{ $t->mandatory ? 'warn' : 'muted' }}">
                {{ $t->mandatory ? __('Mandatory notice') : __('Optional notice') }}
              </span>
            @else
              <span class="muted">—</span>
            @endif
          </td>
          <td class="num">
            <a href="{{ route('templates.edit', $t->key) }}" class="btn sm ter">{{ __('Edit') }}</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No templates configured.') }}</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
