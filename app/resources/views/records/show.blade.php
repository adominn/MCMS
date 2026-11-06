@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', __('Achievement record: :name', ['name' => $d['e']->name_en]))
@section('doc_title', 'Achievement Record')
@section('doc_bm', 'Rekod Pencapaian')
@section('doc_ref', 'MCMS-R-'.($d['e']->institution_id ?? $d['e']->user_id))
@section('doc_file', 'achievement-record-'.$d['e']->reference.'.pdf')
@section('content')

@if(request()->boolean('print') || request()->has('print'))
  {{-- Printable Individual Record Layout --}}
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">{{ __('Achievement Record') }}</h1>
    <div class="small muted">
      {{ __('Holder') }}: <b>{{ $d['e']->person }}</b> &middot; {{ __('Learner ID') }}: <b>{{ $d['e']->institution_id ?? '-' }}</b> &middot; {{ __('Generated on') }} {{ now()->format('d/m/Y H:i') }}
    </div>
  </div>

  <div class="note warn" style="margin-bottom:16px;font-size:9.5pt">
    <div>
      <b>{{ __('Internal record, not an Academic Credit Record.') }}</b> {{ __("This is MCMS's own record of modules and completions. It cannot be deposited in ABC or used for credit transfer.") }}
    </div>
  </div>

  @include('records._record', ['d' => $d])

  <div style="display:flex;gap:40px;margin-top:auto;padding-top:20px;border-top:1px solid var(--border)">
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">Registrar's Office / CODL</div>
      <div class="bm small">Pejabat Pendaftar / CODL</div>
    </div>
    <div style="flex:1;text-align:center">
      <div style="height:36px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:4px">{{ now()->format('d/m/Y') }}</div>
      <div class="bm small">Tarikh</div>
    </div>
  </div>

@else
  {{-- Carbon Screen Layout --}}
  <div class="ph">
    <div>
      <div class="crumb">
        <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
        <a href="{{ route('documents') }}">{{ __('Documents') }}</a> /
        <a href="{{ route('records.mine') }}">{{ __('Achievement records') }}</a> /
        <span>{{ $d['e']->name_en }}</span>
      </div>
      <a href="{{ route('records.mine') }}" class="btn ghost sm" style="margin-bottom:10px;display:inline-flex;align-items:center;gap:6px">
        <span class="btn-lbl">&larr; {{ __('Back to all achievement records') }}</span>
      </a>
      <h1>{{ __('Achievement Record') }}: {{ $d['e']->name_en }}</h1>
      <div class="sub">{{ $d['e']->cohort_name }} &middot; {{ __('Reference') }} <code>{{ $d['e']->reference }}</code></div>
    </div>
  </div>

  @include('records._record', ['d' => $d])

  <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--rule);display:flex;justify-content:space-between;align-items:center">
    <a href="{{ route('records.mine') }}" class="btn ghost sm">
      <span class="btn-lbl">&larr; {{ __('Back to all achievement records') }}</span>
    </a>
    <span class="s muted">{{ __('Record identifier: :ref', ['ref' => $d['e']->reference]) }}</span>
  </div>
@endif

@endsection