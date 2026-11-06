@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <a href="{{ route('documents') }}">{{ __('Documents') }}</a> /
      <span>{{ __('Reports') }}</span>
    </div>
    <h1>{{ __('University Standard Reports') }}</h1>
    <div class="sub">{{ __('Each report is limited to your role scope. Reports show references, not personal names. Open any report to view it, print or save as A4 PDF, or export as CSV for spreadsheet analysis.') }}</div>
  </div>
</div>


<div class="panel">
  <div class="hd">
    <h2>{{ __('Standard reports registry') }}</h2>
    <span class="r small muted">{{ count($kinds) }} {{ __('reports available') }}</span>
  </div>
  <div class="table-wrap">
    <table id="report-list">
      <thead>
        <tr>
          <th>{{ __('Report') }}</th>
          <th>{{ __('Type') }}</th>
          <th class="num">{{ __('Actions') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($kinds as $k => $t)
          <tr>
            <td>
              <a href="{{ route('reports.show', $k) }}"><b>{{ $t }}</b></a>
              <div class="small muted">Key: <code>{{ $k }}</code> &middot; Scope-filtered</div>
            </td>
            <td><span class="s muted">Standard</span></td>
            <td class="num">
              <a href="{{ route('reports.print', $k) }}" class="btn sm ter">{{ __('Print') }}</a>
              &middot;
              <a href="{{ route('reports.csv', $k) }}" class="btn sm ghost">{{ __('CSV') }}</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="note" style="margin-top:20px">
  <div><b>{{ __("Method & Privacy:") }}</b> Figures are read directly from MCMS production records at generation time. Individual participants appear by registration or matric reference only.</div>
</div>
@endsection
