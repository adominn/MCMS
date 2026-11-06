@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', 'Submission package: ' . $v->name_en)
@section('doc_title', 'Quality Verification Submission Package')
@section('doc_bm', 'Pakej Penyerahan Pengesahan Kualiti')
@section('doc_ref', 'QV-'.($rec->id ?? $m->id).'-C'.$rec->cycle_no)
@section('doc_file', 'qv-package-'.$v->name_en.'.pdf')
@section('content')
@php
  $isPrint = request()->boolean('print') || request()->has('print');
@endphp

@if(!$isPrint)
  <div class="ph">
    <div>
      <div class="crumb">
        <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
        <a href="{{ route('quality') }}">{{ __('Quality Verification') }}</a> /
        <a href="{{ route('quality.show', $m->id) }}">{{ $v->name_en }}</a> /
        <span>{{ __('Submission package') }}</span>
      </div>
      <h1>Submission package: {{ $v->name_en }}</h1>
      <div class="sub">
        Cycle {{ $rec->cycle_no }} &middot;
        {{ config('qv.status')[$rec->status] ?? $rec->status }} &middot;
        {{ __('Official submission package for SAMC Quality Verification.') }}
      </div>
    </div>
    <div class="act" style="display:flex;gap:8px">
      <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn ter sm">{{ __('Print or save as PDF') }}</a>
      <a href="{{ route('quality.show', $m->id) }}" class="btn ghost sm">&larr; {{ __('Back to QV workspace') }}</a>
    </div>
  </div>
@else
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">Submission package: {{ $v->name_en }}</h1>
    <div class="small muted">
      Cycle {{ $rec->cycle_no }} &middot;
      Status: <b>{{ config('qv.status')[$rec->status] ?? $rec->status }}</b> &middot;
      {{ __('Generated on') }} {{ now()->format('d/m/Y H:i') }}
    </div>
  </div>
@endif

<div class="panel" style="margin-bottom:20px">
  <div class="hd">
    <h2>{{ __("Summary") }}</h2>
  </div>
  <div class="bd">
    <p style="font-size:14px;line-height:1.5;margin-bottom:12px"><b>{{ __('Rationale / Need') }}:</b> {{ $v->need }}</p>
    <p class="small" style="line-height:1.6;margin-bottom:16px">
      Proposed credit <b>{{ $v->proposed_credit }}</b>, MQF level <b>{{ $v->proposed_mqf_level }}</b> (proposed until verified).
      Readiness: <b>{{ config('qv.readiness')[$rec->readiness_decision] ?? 'Pending' }}</b>.
    </p>

    <div style="margin-bottom:16px">
      <h3 style="font-size:14px;margin-bottom:6px">{{ __("Learning outcomes") }}</h3>
      <ul style="padding-left:20px;line-height:1.6;font-size:13px">
        @foreach($outcomes as $o)
          <li><b>{{ $o->code }}:</b> {{ $o->text_en }}</li>
        @endforeach
      </ul>
    </div>

    <div>
      <h3 style="font-size:14px;margin-bottom:6px">{{ __("Modules and learning time") }}</h3>
      <ul style="padding-left:20px;line-height:1.6;font-size:13px">
        @foreach($modules as $x)
          <li><b>{{ $x->name_en }}:</b> {{ $x->slt_hours }} hours</li>
        @endforeach
      </ul>
    </div>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __("Evidence index") }}</h2>
    <span class="r small muted">{{ count(config('qv.checklist')) }} {{ __('checklist items') }}</span>
  </div>
  <div class="table-wrap">
    <table id="evidence-index">
      <thead>
        <tr>
          <th>{{ __('Item') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('File reference') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach(config('qv.checklist') as $k => $label)
        @php($e = $evidence[$k] ?? null)
        <tr>
          <td><b>{{ $label }}</b></td>
          <td>
            <span class="tag {{ ($e->status ?? '') === 'available' ? 'ok' : (($e->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
              {{ ['available' => 'Available', 'not_yet' => 'Not yet', 'na' => 'Not applicable'][$e->status ?? 'not_yet'] }}
            </span>
          </td>
          <td><code>{{ $e->file_ref ?? '—' }}</code></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="foot">
    <span class="small muted">{{ __('Evidence files are listed by reference. A compressed copy of the files comes with file upload.') }}</span>
  </div>
</div>

@if($isPrint)
  <div class="panel" style="margin-top:20px">
    <div class="hd"><h2>{{ __('Declaration') }}</h2></div>
    <div class="bd" style="padding:14px 16px;font-size:10pt;line-height:1.5">
      {{ __('I declare that the evidence in this package is complete and true, that the figures count only verified results, and that no credit or MQF level is claimed beyond the period of valid Quality Verification.') }}
    </div>
  </div>

  <div style="display:flex;gap:40px;margin-top:30px;padding-top:20px;border-top:1px solid var(--border)">
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">{{ __('Programme Leader') }}</div>
      <div class="small muted">{{ __('Ketua Program') }}</div>
    </div>
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">{{ __('Deputy Dean (Academic)') }}</div>
      <div class="small muted">{{ __('Timbalan Dekan (Akademik)') }}</div>
    </div>
  </div>
@endif
@endsection
