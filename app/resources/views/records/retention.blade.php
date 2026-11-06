@extends('layouts.app')
@section('title', 'Retention')
@section('content')
<div class="ph">
  <div>
    <div class="crumb">
      <a href="{{ route('tasks') }}">{{ __('Home') }}</a> /
      <span>{{ __('Retention and disposal') }}</span>
    </div>
    <h1>{{ __('Retention and disposal') }}</h1>
    <div class="sub">{{ __('How long non-academic logs and operational records are kept before disposal. Academic records, credentials, and the audit trail are permanent and never disposed of.') }}</div>
  </div>
</div>



<div class="panel" style="margin-bottom:24px">
  <div class="hd">
    <h2>{{ __('Record categories and retention schedules') }}</h2>
    <span class="r small muted">{{ count($rows) }} {{ __('categories') }}</span>
  </div>
  <div class="table-wrap">
    <table id="retention">
      <thead>
        <tr>
          <th>{{ __('Category') }}</th>
          <th>{{ __('Retention period') }}</th>
          <th>{{ __('Older than') }}</th>
          <th>{{ __('Eligible for disposal') }}</th>
          <th>{{ __('Hold status') }}</th>
          <th class="num">{{ __('Action') }}</th>
        </tr>
      </thead>
      <tbody>
      @foreach($rows as $r)
        <tr>
          <td>
            <b>{{ $r['label'] }}</b>
            <div class="small muted"><code>{{ $r['category'] }}</code></div>
          </td>
          <td>{{ $r['years'] }} {{ __('years') }}</td>
          <td>{{ \Carbon\Carbon::parse($r['cutoff'])->format('d/m/Y') }}</td>
          <td>
            @if($r['count'] > 0)
              <span class="tag {{ $r['held'] ? 'muted' : 'info' }}">{{ $r['count'] }} {{ __('row(s)') }}</span>
            @else
              <span class="muted">0</span>
            @endif
          </td>
          <td>
            <span class="tag {{ $r['held'] ? 'warn' : 'ok' }}">
              {{ $r['held'] ? __('On hold') : __('No hold') }}
            </span>
          </td>
          <td class="num">
            @if(auth()->user()->hasAnyRole($approver) && $r['count'] > 0 && ! $r['held'])
              <form method="post" action="{{ route('retention.dispose', $r['category']) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn sm danger" aria-label="Approve disposal of {{ $r['label'] }}">
                  {{ __('Approve disposal') }}
                </button>
              </form>
            @elseif($r['held'])
              <span class="small muted">{{ __('Frozen by hold') }}</span>
            @elseif($r['count'] == 0)
              <span class="small muted">{{ __('None due') }}</span>
            @else
              <span class="small muted">{{ __('Requires :role', ['role' => $approver]) }}</span>
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="foot">
    <span class="small muted">
      {{ __('Disposal is approved by the role set in settings (:role), only after the retention period and only if no hold applies. CODL cannot approve disposal.', ['role' => $approver]) }}
    </span>
  </div>
</div>

<div class="row" style="align-items:stretch;margin-bottom:24px;gap:20px">
  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Place a records hold') }}</h2>
      <span class="tag warn">{{ __('Freezes disposal') }}</span>
    </div>
    <div class="bd">
      <form method="post" action="{{ route('retention.hold') }}" class="col" style="gap:12px">
        @csrf
        <p class="small muted" style="margin:0">
          {{ __('A hold stops disposal of records while they are needed, for example during an audit or investigation.') }}
        </p>
        <div>
          <label for="category" style="font-weight:600">{{ __('Category') }}</label>
          <select id="category" name="category" required style="width:100%">
            @foreach($rows as $r)
              <option value="{{ $r['category'] }}">{{ $r['label'] }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="reason" style="font-weight:600">{{ __('Reason') }}</label>
          <input id="reason" name="reason" type="text" required placeholder="{{ __('Audit, QA, legal, or participant protection') }}" style="width:100%">
        </div>
        <div style="margin-top:6px">
          <button type="submit" class="btn ter">{{ __('Place hold') }}</button>
        </div>
      </form>
    </div>
  </div>

  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Active holds') }}</h2>
      <span class="r small muted">{{ $holds->whereNull('lifted_at')->count() }} {{ __('active') }}</span>
    </div>
    <div class="bd">
      <ul id="holds" style="list-style:none;padding:0;margin:0">
      @forelse($holds as $h)
        <li style="padding:10px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px">
          <div>
            <b>{{ $h->category }}</b>: {{ $h->reason }}
            <div class="small muted">
              {{ $h->name }}, {{ \Carbon\Carbon::parse($h->placed_at)->format('d/m/Y') }}
              @if($h->lifted_at)
                &middot; <span class="tag muted">{{ __('Lifted') }}</span>
              @endif
            </div>
          </div>
          <div>
            @if(!$h->lifted_at)
              <form method="post" action="{{ route('retention.lift', $h->id) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn sm ghost" aria-label="Lift the hold on {{ $h->category }}">
                  {{ __('Lift hold') }}
                </button>
              </form>
            @endif
          </div>
        </li>
      @empty
        <li class="muted" style="padding:12px 0">{{ __('No hold active.') }}</li>
      @endforelse
      </ul>
    </div>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Disposals approved') }}</h2>
    <span class="r small muted">{{ $history->count() }} {{ __('disposal log entries') }}</span>
  </div>
  <div class="bd">
    <ul id="disposals" style="list-style:none;padding:0;margin:0">
    @forelse($history as $d)
      <li style="padding:8px 0;border-bottom:1px solid var(--border)">
        <b>{{ $d->category }}</b>: {{ $d->row_count }} {{ __('row(s) older than :cutoff, approved by :name', ['cutoff' => \Carbon\Carbon::parse($d->cutoff)->format('d/m/Y'), 'name' => $d->name]) }}
      </li>
    @empty
      <li class="muted" style="padding:12px 0">{{ __('None.') }}</li>
    @endforelse
    </ul>
  </div>
</div>
@endsection
