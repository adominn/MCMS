@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', __('Decision pack: :name', ['name' => $v->name_en]))
@section('doc_title', 'Senate Decision Pack')
@section('doc_bm', 'Pakej Keputusan Senat')
@section('doc_ref', 'SEN-PACK-'.$v->id.'-v'.$v->version_no)
@section('doc_file', 'senate-pack-'.$v->id.'-v'.$v->version_no.'.pdf')
@section('content')
@php
  $isPrint = request()->boolean('print') || request()->has('print');
@endphp

@if(!$isPrint)
  <div class="ph">
    <div>
      <div class="crumb">
        <a href="{{ route('approvals') }}">{{ __('Proposals and approval') }}</a> /
        <a href="{{ route('approvals.show', $v->id) }}">{{ $v->name_en }}</a> /
        <span>{{ __('Decision pack') }}</span>
      </div>
      <h1>{{ __('Decision pack: :name', ['name' => $v->name_en]) }}</h1>
      <div class="sub">{{ __('Version :num · Decision pack for Senate submission', ['num' => $v->version_no]) }}</div>
    </div>
    <div class="act" style="display:flex;gap:8px">
      <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn ter sm">{{ __('Print pack') }}</a>
      <a class="btn ghost sm" href="{{ route('approvals.show', $v->id) }}">&larr; {{ __('Back to proposal') }}</a>
    </div>
  </div>
@else
  <div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
    <h1 style="font-size:18pt;margin:0 0 4px">Senate Decision Pack: {{ $v->name_en }}</h1>
    <div class="small muted">
      Version {{ $v->version_no }} &middot; Reference: SEN-PACK-{{ $v->id }}-v{{ $v->version_no }} &middot; {{ __('Generated on') }} {{ now()->format('d/m/Y H:i') }}
    </div>
  </div>
@endif

<div class="panel" style="margin-bottom:20px">
  <div class="hd"><h2>{{ __('Category verification') }}</h2></div>
  <div class="bd">
    <p style="margin:0 0 8px">
      {{ __('Proposed: :cat.', ['cat' => strtoupper($v->category)]) }}
      @if($endorsement)
        {{ __('Verified by :by on :date: :cat.', ['by' => $endorsement->by, 'date' => \Carbon\Carbon::parse($endorsement->created_at)->format('d/m/Y'), 'cat' => strtoupper($endorsement->verified_category)]) }}
      @else
        <b>{{ __('Not verified yet.') }}</b>
      @endif
    </p>
    <p class="muted s" style="margin:0">
      {{ __('Approval to offer is one decision. It sets no Quality Verification, ABC, credit transfer or stackable status.') }}
    </p>
  </div>
</div>

<div style="margin-bottom:20px">
  @include('approval._summary')
</div>

<div class="panel" style="margin-bottom:20px">
  <div class="hd"><h2>{{ __('Review summary') }}</h2></div>
  <div class="bd">
    <div style="margin-bottom:16px">
      @forelse($assign as $a)
        <p style="margin:4px 0">
          <b>{{ $a->reviewer }}</b> ({{ $a->kind === 'expert' ? __('industry expert') : __('reviewer') }}):
          <span class="tag ok">{{ $a->recommendation ? __(['support' => 'Support', 'amend' => 'Amend', 'not_support' => 'Do not support'][$a->recommendation]) : __('no recommendation') }}</span>
          @if($a->summary) &mdash; {{ $a->summary }} @endif
        </p>
      @empty
        <p class="muted">{{ __('No reviewers.') }}</p>
      @endforelse
    </div>

    <h3 style="margin:20px 0 10px">{{ __('Comments and responses') }}</h3>
    <div style="margin-bottom:16px">
      @forelse($comments as $c)
        <div class="panel" style="margin-bottom:8px;background:var(--surface)">
          <div class="bd">
            <div class="row" style="justify-content:space-between;margin-bottom:4px">
              <b>{{ $c->text }}</b>
              <x-pill :s="__(['open' => 'Open', 'answered' => 'Answered', 'resolved' => 'Resolved'][$c->status])" />
            </div>
            @if($c->blocking) <span class="tag overdue">{{ __('Blocking') }}</span> @endif
            @if($c->response) <div class="s" style="margin-top:4px"><b>{{ __('Response:') }}</b> {{ $c->response }}</div> @endif
          </div>
        </div>
      @empty
        <p class="muted">{{ __('None.') }}</p>
      @endforelse
    </div>

    <h3 style="margin:20px 0 10px">{{ __('Unresolved warnings') }}</h3>
    <div>
      @forelse($checks['warnings'] as $c)
        <p style="margin:4px 0"><span class="tag warn">{{ __('Warning') }}</span> {{ $c['msg'] }}</p>
      @empty
        <p class="muted">{{ __('None.') }}</p>
      @endforelse
      @if($v->submit_response)
        <p style="margin-top:10px"><b>{{ __("Proposer's response:") }}</b> {{ $v->submit_response }}</p>
      @endif
    </div>
  </div>
</div>
@if($isPrint)
  <div class="panel" style="margin-top:20px">
    <div class="hd"><h2>{{ __('Senate decision record') }}</h2></div>
    <div class="bd" style="padding:16px">
      <div style="border:1px dashed var(--border);padding:14px;font-size:10pt;display:flex;flex-direction:column;gap:10px">
        <div><b>{{ __("Senate meeting:") }}</b> ___________________________ &nbsp;&nbsp;&nbsp;&nbsp; <b>{{ __("Date:") }}</b> ___________________________</div>
        <div><b>{{ __("Decision:") }}</b> Approved &nbsp;/&nbsp; Approved with conditions &nbsp;/&nbsp; Returned for amendment &nbsp;/&nbsp; Not approved</div>
        <div><b>{{ __("Minute reference (official minuted wording):") }}</b> __________________________________________________</div>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:40px;margin-top:30px;padding-top:20px;border-top:1px solid var(--border)">
    <div style="flex:1;text-align:center">
      <div style="height:40px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:6px">{{ $v->owner_name ?? 'Proposer' }}</div>
      <div class="small muted">Proposer</div>
    </div>
    <div style="flex:1;text-align:center">
      <div style="height:40px;border-bottom:1px solid var(--text)"></div>
      <div style="font-weight:600;margin-top:6px">{{ $endorsement->by ?? 'Deputy Dean (Academic)' }}</div>
      <div class="small muted">Deputy Dean (Academic)</div>
    </div>
  </div>
@endif

@endsection
