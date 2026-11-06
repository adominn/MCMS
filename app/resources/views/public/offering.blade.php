@extends('layouts.app')
@section('title', $pick($o->name_en, $o->name_ms))
@section('content')
@guest @include('public._nav') @endguest
@php
  $opens = \Carbon\Carbon::parse($o->reg_opens_on);
  $modeLabel = config('proposals.delivery_modes')[$o->delivery_mode] ?? $o->delivery_mode;
@endphp

<div class="pub-sec">
  <div class="crumb" style="margin-bottom:8px">
    <a href="{{ auth()->check() ? route('tasks') : route('public.home') }}">{{ auth()->check() ? (auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard')) : $t('home') }}</a> <span>/</span>
    <a href="{{ route('catalogue') }}">{{ $t('catalogue') }}</a> <span>/</span>
    <span>{{ $pick($o->name_en, $o->name_ms) }} {{ $o->cohort_name }}</span>
  </div>

  <div class="title" style="margin-bottom:20px">
    <div>
      <h1><span class="cat">{{ strtoupper($o->category) }}</span> {{ $pick($o->name_en, $o->name_ms) }} {{ $o->cohort_name }}</h1>
      <div class="sub">{{ $o->ptj_name }} &middot; {{ $modeLabel }} &middot; {{ $o->delivery_language }}</div>
    </div>
    <div class="act">
      @if(isset($mine[$o->id]))
        <a class="btn ter" href="{{ route('enrolments.show', $mine[$o->id]) }}">View your registration</a>
      @elseif($o->status === 'open')
        <a class="btn" href="{{ route('register', $o->id) }}">{{ $t('register') }}</a>
      @endif
    </div>
  </div>

  @if($o->description)
    <p style="margin-bottom:20px;font-size:15px;line-height:1.6;color:var(--ink-light)">{{ $o->description }}</p>
  @endif

  <div class="row" style="gap:32px;align-items:flex-start">
    <div class="col grow" style="gap:24px">
      <div class="note info" id="recognition" style="flex-direction:column;gap:8px;padding:16px 20px">
        <b style="font-size:15px">{{ $t('recognition') }}</b>
        <span style="line-height:1.5">{{ $statement }}</span>
        @if($claims['credit'] !== null)
          <span><b>{{ $lang === 'ms' ? 'Kredit' : 'Credit' }}:</b> {{ $claims['credit'] }}@if($claims['mqf']) &middot; <b>MQF</b> {{ $claims['mqf'] }}@endif @if($claims['qv_until']) &middot; Quality Verification {{ $lang === 'ms' ? 'sah hingga' : 'valid until' }} {{ \Carbon\Carbon::parse($claims['qv_until'])->format('d/m/Y') }}@endif</span>
        @elseif(!empty($claims['renewal']))
          <span class="muted">{{ $lang === 'ms' ? 'Kredit selepas pembaharuan QV.' : 'Credit after QV renewal.' }}</span>
        @endif
        @if($stages)
          <span style="margin-top:4px;line-height:1.4">{{ $stages }}</span>
        @endif
      </div>

      <div class="panel">
        <div class="hd"><h2>{{ $t('outcomes') }}</h2></div>
        <div class="bd">
          <ul style="padding-left:20px;margin:0;line-height:1.6">
            @foreach($los as $lo)
              <li style="margin-bottom:6px">{{ $pick($lo->text_en, $lo->text_ms) }}</li>
            @endforeach
          </ul>
        </div>
      </div>

      <div class="panel">
        <div class="hd">
          <h2>{{ $t('modules') }}</h2>
          <span class="r">{{ $t('slt') }}: {{ $modules->sum('slt_hours') }} h</span>
        </div>
        <table>
          <thead><tr><th>#</th><th>{{ $t('modules') }}</th><th class="num">h</th></tr></thead>
          <tbody>
            @foreach($modules as $m)
              <tr>
                <td>{{ $m->seq }}</td>
                <td>{{ $pick($m->name_en, $m->name_ms) }}</td>
                <td class="num">{{ $m->slt_hours }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
        @if($o->category === 'umc')
          <div class="bd small muted" style="border-top:1px solid var(--hairline);padding:12px 16px">
            {{ $lang === 'ms' ? 'Setiap modul ialah sebahagian daripada keseluruhan UMC. Menamatkan satu modul atau lencana bukan penamatan UMC dan bukan kredit akademik.' : 'Each module is part of the whole UMC. Completing a module or earning a badge is not completion of the UMC and is not academic credit.' }}
            <br><b>{{ $t('staged') }}:</b> {{ $o->staged_completion ? $t('yes') : $t('no') }}
          </div>
        @endif
      </div>

      <div class="panel">
        <div class="hd"><h2>{{ $t('assessment') }}</h2></div>
        <table>
          <thead><tr><th>{{ $t('assessment') }}</th><th class="num">%</th></tr></thead>
          <tbody>
            @foreach($comps as $c)
              <tr>
                <td>{{ $c->name_en }} ({{ rtrim(rtrim(number_format((float) $c->weight_percent, 1), '0'), '.') }}%)</td>
                <td class="num">{{ rtrim(rtrim(number_format((float) $c->weight_percent, 1), '0'), '.') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
        @if($o->assessment_standard)
          <div class="bd" style="border-top:1px solid var(--hairline);padding:12px 16px">
            <b>{{ $t('standard') }}:</b> {{ $o->assessment_standard }}
          </div>
        @endif
      </div>

      <div class="panel">
        <div class="hd"><h2>{{ $lang === 'ms' ? 'Apa yang anda terima' : 'What you receive' }}</h2></div>
        <div class="bd">
          <p style="margin:0 0 8px">{{ implode(', ', $credentials) ?: ($lang === 'ms' ? 'Sijil micro-credential' : 'Micro-credential certificate') }}</p>
          <div class="small muted">
            {{ $lang === 'ms' ? 'Kredensial boleh disahkan secara digital oleh majikan dan institusi pendidikan di portal semakan awam UTeM.' : 'Credentials can be verified digitally by employers and educational institutions on the UTeM public verification portal.' }}
          </div>
        </div>
      </div>

      @if($o->prerequisites || $o->access_period || $o->completion_conditions || $o->learning_support)
        <div class="panel">
          <div class="hd"><h2>{{ $lang === 'ms' ? 'Syarat dan sokongan' : 'Requirements and support' }}</h2></div>
          <div class="bd col" style="gap:10px">
            @if($o->prerequisites)<div><b>{{ $t('prereq') }}:</b> {{ $o->prerequisites }}</div>@endif
            @if($o->access_period)<div><b>{{ $t('access') }}:</b> {{ $o->access_period }}</div>@endif
            @if($o->completion_conditions)<div><b>{{ $t('completion') }}:</b> {{ $o->completion_conditions }}</div>@endif
            @if($o->learning_support)<div><b>{{ $t('support') }}:</b> {{ $o->learning_support }}</div>@endif
          </div>
        </div>
      @endif
    </div>

    <div class="card col" style="width:320px;position:sticky;top:72px;gap:16px;box-shadow:var(--shadow, 0 2px 6px rgba(0,0,0,0.08))">
      <div>
        <div class="small muted">{{ $t('fee') }}</div>
        <b style="font-size:24px;font-weight:400;color:var(--ink)">{{ $o->fee > 0 ? 'RM '.number_format($o->fee, 2) : ($lang === 'ms' ? 'Percuma' : 'Free') }}</b>
      </div>

      <dl class="dl" style="grid-template-columns:110px 1fr;gap:8px;font-size:13px;border-top:1px solid var(--hairline);border-bottom:1px solid var(--hairline);padding:12px 0;margin:0">
        <dt>{{ $lang === 'ms' ? 'Institusi' : 'Institution' }}</dt><dd>UTeM (CODL)</dd>
        <dt>{{ $t('mode') }}</dt><dd>{{ $modeLabel }}</dd>
        <dt>{{ $t('language') }}</dt><dd>{{ $o->delivery_language }}</dd>
        <dt>{{ $t('start') }}</dt><dd>{{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }}</dd>
        <dt>{{ $t('end') }}</dt><dd>{{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }}</dd>
        <dt>{{ $t('capacity') }}</dt><dd>{{ $o->capacity }} {{ $lang === 'ms' ? 'tempat' : 'places' }}</dd>
        <dt>{{ $lang === 'ms' ? 'Kredit' : 'Credit' }}</dt><dd>{{ $claims['credit'] !== null ? $claims['credit'].' credits' : ($lang === 'ms' ? 'Tiada' : 'None') }}</dd>
        <dt>{{ $t('credential') }}</dt><dd>{{ implode(', ', $credentials) ?: '-' }}</dd>
      </dl>

      <div>
        @if(isset($mine[$o->id]))
          <a class="btn ter" href="{{ route('enrolments.show', $mine[$o->id]) }}" style="width:100%;text-align:center">View your registration</a>
        @elseif($o->status !== 'open')
          <span class="btn dis" style="width:100%;text-align:center">{{ $t('closed') }}</span>
        @endif
        <div class="small muted" style="margin-top:8px;line-height:1.4">
          {{ $lang === 'ms' ? 'Pendaftaran dibuka: '.$opens->format('d/m/Y').' hingga '.\Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y') : 'Registration open: '.$opens->format('d/m/Y').' to '.\Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y') }}
        </div>
      </div>
    </div>
  </div>
</div>

@include('public._foot')
@endsection
