@extends('layouts.app')
@section('title', 'Adapters')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <span>{{ __('Adapters') }}</span>
    </div>
    <h1>{{ __('Adapter status') }}</h1>
    <div class="sub">{{ __('Each outside connection runs in one of three modes: stub (test double), manual (a person moves files), or live.') }}</div>
  </div>
</div>


@if($failed->isNotEmpty())
  <div class="note err" style="margin-bottom:16px">
    <div>
      <b>{{ __('Adapter attention required') }}:</b>
      {{ __(':count integration calls have failed and are waiting in the retry queue. A failed call never blocks the core business process; messages wait and retry automatically.', ['count' => $failed->count()]) }}
    </div>
  </div>
@endif


<div class="panel" style="margin-bottom:20px">
  <div class="hd">
    <h2>{{ __('Configured system adapters') }}</h2>
    <span class="r small muted">{{ count($adapters) }} {{ __('adapters active') }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Adapter') }}</th>
          <th>{{ __('Mode') }}</th>
          <th>{{ __('Last run') }}</th>
          <th>{{ __('Recent status / errors') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach($adapters as $key => $a)
        @php($mode = strtolower($a['mode']))
        <tr>
          <td>
            <b>{{ $a['label'] }}</b>
            <div style="margin-top:2px"><code>{{ $key }}</code></div>
          </td>
          <td>
            <span class="tag {{ $mode === 'live' ? 'ok' : ($mode === 'stub' ? 'info' : 'muted') }}">
              {{ ucfirst($a['mode']) }}
            </span>
          </td>
          <td>{{ $a['last_run'] ? \Carbon\Carbon::parse($a['last_run'])->format('d/m/Y H:i') : __('Never') }}</td>
          <td>
            @if(!empty($a['errors']))
              @foreach($a['errors'] as $e)
                <div class="small" style="color:var(--err)">{{ $e }}</div>
              @endforeach
            @else
              <span class="tag ok">{{ __('Working') }}</span>
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Calls that failed (Retry queue)') }}</h2>
    <span class="r small muted">{{ $failed->count() }} {{ __('waiting') }}</span>
  </div>
  @if($failed->isEmpty())
    <div class="bd">
      <p class="muted" style="margin:0">{{ __('No failed calls. All adapter transmissions are operational.') }}</p>
    </div>
  @else
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ __('Adapter') }}</th>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Attempts') }}</th>
            <th>{{ __('Last error') }}</th>
            <th class="num">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
        @foreach($failed as $f)
          <tr>
            <td><b>{{ $f->adapter }}</b></td>
            <td><code>{{ $f->external_ref }}</code></td>
            <td>{{ $f->attempts }}</td>
            <td class="small" style="color:var(--err)">{{ $f->last_error }}</td>
            <td class="num">
              @if($f->adapter === 'email')
                <form method="post" action="{{ route('adapters.retry', $f->id) }}" style="display:inline">
                  @csrf
                  <button type="submit" class="btn sm ter">{{ __('Retry now') }}</button>
                </form>
              @else
                <span class="tag muted">{{ __('Declined by provider') }}</span>
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
