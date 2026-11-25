@extends('layouts.print')
@section('title', $progA->code . ' vs ' . $progB->code . ' - ' . __('Programme Comparison'))
@section('doc_title', __('Programme Comparison'))
@section('doc_bm', 'Perbandingan Program')
@section('doc_ref', $progA->code . ' vs ' . $progB->code)
@section('doc_file', 'programme-compare-' . strtolower($progA->code) . '-' . strtolower($progB->code) . '.pdf')
@section('content')
  <h1 style="font-size:14pt;margin:0 0 4px">{{ __('Programme Comparison:') }} {{ $progA->code }} &rarr; {{ $progB->code }}</h1>
  <div class="muted small" style="margin-bottom:14px">
    {{ __('From:') }} {{ $progA->code }} ({{ $progA->name }}, {{ $progA->total_credits }} cr) &middot;
    {{ __('To:') }} {{ $progB->code }} ({{ $progB->name }}, {{ $progB->total_credits }} cr) &middot;
    {{ __('Generated:') }} {{ now()->format('d/m/Y') }}
  </div>

  <table class="print-audit-table">
    <thead>
      <tr>
        <th>{{ __('Shared Credits') }}</th>
        <th>{{ __('Approved Equivalent Credits') }}</th>
        <th>{{ __('Not Carried Over') }}</th>
        <th>{{ __('Remaining in :code', ['code' => $progB->code]) }}</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="num"><strong>{{ mcms_num($comparison['summary']['shared_credits']) }} cr</strong></td>
        <td class="num"><strong>{{ mcms_num($comparison['summary']['approved_equiv_credits']) }} cr</strong></td>
        <td class="num"><strong>{{ mcms_num($comparison['summary']['not_carried_credits']) }} cr</strong></td>
        <td class="num"><strong>{{ mcms_num($comparison['summary']['remaining_credits_b']) }} cr</strong></td>
      </tr>
    </tbody>
  </table>

  <h2 style="font-size:10.5pt;margin:14px 0 6px;border-bottom:1px solid #090a0f;padding-bottom:3px">{{ __('Shared Courses') }} ({{ $comparison['shared']->count() }})</h2>
  <table class="print-audit-table">
    <thead>
      <tr>
        <th style="width:16%">{{ __('Code') }}</th>
        <th>{{ __('Course') }}</th>
        <th class="num" style="width:12%">{{ __('Credits') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($comparison['shared'] as $row)
        <tr>
          <td class="mono">{{ $row->course->code }}</td>
          <td>{{ $row->course->name }}</td>
          <td class="num">{{ mcms_num($row->credits) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <h2 style="font-size:10.5pt;margin:14px 0 6px;border-bottom:1px solid #090a0f;padding-bottom:3px">{{ __('Course Equivalences') }} ({{ $comparison['equivalences']->count() }})</h2>
  <table class="print-audit-table">
    <thead>
      <tr>
        <th>{{ $progA->code }}</th>
        <th>{{ $progB->code }}</th>
        <th>{{ __('Type') }}</th>
        <th class="num">{{ __('Credits') }}</th>
        <th>{{ __('Status') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse($comparison['equivalences'] as $eq)
        <tr>
          <td class="mono">{{ $eq->from_code }}</td>
          <td class="mono">{{ $eq->to_code }}</td>
          <td>{{ __($eqTypes[$eq->equivalence_type] ?? $eq->equivalence_type) }}</td>
          <td class="num">{{ mcms_num($eq->credits_counted) }}</td>
          <td>{{ __($eqStatuses[$eq->status] ?? $eq->status) }}</td>
        </tr>
      @empty
        <tr><td colspan="5">{{ __('None recorded.') }}</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="print-note-box">
    {{ __('This comparison is for academic planning only. Formal credit transfer and programme admission require evaluation and approval by UTeM. At least :min% of graduating credits must be earned directly at UTeM.', ['min' => mcms_num($minHomePercent)]) }}
  </div>
@endsection
