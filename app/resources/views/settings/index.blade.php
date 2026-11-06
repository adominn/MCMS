@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <span>{{ __('Settings') }}</span>
    </div>
    <h1>{{ __('System Settings') }}</h1>
    <div class="sub">{{ __('Values the business rules read. Every change requires an audit reason and an effective date. Earlier decisions keep the value that applied on their date.') }}</div>
  </div>
</div>


<div class="panel">
  <div class="hd">
    <h2>{{ __('Configurable parameters') }}</h2>
    <span class="r small muted">{{ $rows->count() }} {{ __('rules') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="min-width:280px">{{ __('Setting') }}</th>
          <th style="min-width:140px">{{ __('Current value') }}</th>
          <th style="min-width:440px">{{ __('Change setting') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach($rows as $r)
        @php($cur = \App\Modules\Core\Settings::show($r['current']))
        <tr id="s-{{ $r['key'] }}">
          <td>
            <div style="font-weight:600;color:var(--ink)">{{ $r['en'] }}</div>
            <div style="margin-top:2px">
              <code>{{ $r['key'] }}</code>
              @if($r['ref'])
                <span class="tag muted" style="margin-left:4px">{{ $r['ref'] }}</span>
              @endif
            </div>
          </td>
          <td>
            <div style="font-size:15px;font-weight:700;color:var(--primary)">
              {{ $cur }} <span class="small muted" style="font-weight:normal">{{ $r['current'] !== null ? $r['unit'] : '' }}</span>
            </div>
          </td>
          <td>
            <form method="post" action="{{ route('settings.update', $r['key']) }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
              @csrf
              <label style="display:inline-flex;flex-direction:column;font-size:11px;font-weight:600;gap:2px">
                {{ __('New value') }}
                <input type="text" name="value" value="{{ $cur === 'No value yet' ? '' : $cur }}" style="height:30px;width:130px;font-size:13px">
              </label>
              <label style="display:inline-flex;flex-direction:column;font-size:11px;font-weight:600;gap:2px">
                {{ __('Effective date') }}
                <input type="date" name="effective_from" min="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" style="height:30px;font-size:12px">
              </label>
              <label style="display:inline-flex;flex-direction:column;font-size:11px;font-weight:600;gap:2px">
                {{ __('Reason') }}
                <input type="text" name="reason" required minlength="5" placeholder="{{ __('Why changing?') }}" style="height:30px;width:150px;font-size:13px">
              </label>
              <div style="display:inline-flex;gap:6px;align-items:center;margin-top:16px">
                <button type="submit" class="btn sm ter">{{ __('Change') }}</button>
                <a href="{{ route('settings.history', $r['key']) }}" class="btn sm ghost">{{ __('History') }}</a>
              </div>
            </form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="note" style="margin-top:16px">
  <div>
    <b>{{ __('Note on module credit flags') }}:</b>
    {{ __('The module credit flag is not a setting. Whether a module carries academic credit is decided with the programme structure and the deputy dean, not by CODL administrators.') }}
  </div>
</div>
@endsection
