@extends('layouts.app')
@section('title', 'Retention & Record Governance (Appendix H)')
@section('content')
<div class="ph">
  <div>
    <x-breadcrumb :crumbs="[
      'Records' => null,
      'Retention and governance' => null,
    ]" />
    <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#0052ff;margin-bottom:2px">
      {{ __('Appendix H / Lampiran H') }} &bull; {{ __('Governance, Roles & Record Ownership Matrix') }}
    </div>
    <h1>{{ __('Retention, Record Ownership & Governance') }}</h1>
    <div class="sub">{{ __('The 12 statutory record classes (H4), governance actions (H8), confirmations (H9), and operational disposal schedules.') }}</div>
  </div>
  <div class="act" style="gap:8px">
    <button type="button" onclick="window.print()" class="btn ghost sm">{{ __('Print Governance Matrix (A4)') }}</button>
  </div>
</div>

<!-- Section 1: Appendix H4 Record Ownership Matrix -->
<div class="panel" style="margin-bottom:24px">
  <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
    <div>
      <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#0052ff">
        {{ __('Appendix H4 / Lampiran H4') }}
      </div>
      <h2 style="margin:2px 0 0 0">{{ __('Record Ownership Matrix (12 Record Classes)') }}</h2>
    </div>
    <span class="tag info">{{ count($matrix) }} {{ __('Record Classes') }}</span>
  </div>
  <div class="table-wrap">
    <table id="h4-matrix">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>{{ __('Record Class & Description') }}</th>
          <th>{{ __('Owner / Custodian') }}</th>
          <th>{{ __('Source / Creator') }}</th>
          <th>{{ __('Retention Period & Method') }}</th>
          <th>{{ __('Access Level') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($matrix as $m)
          <tr>
            <td style="color:#475569;font-weight:600">{{ $m['no'] }}</td>
            <td>
              <div style="font-weight:600;color:#090a0f">{{ __($m['class_en']) }}</div>
              @if(!empty($m['class_ms']) && app()->getLocale() !== 'ms')
                <div class="small muted" style="font-style:italic">{{ $m['class_ms'] }}</div>
              @endif
            </td>
            <td>
              <span class="tag info" style="font-size:11px">{{ $m['owner'] }}</span>
            </td>
            <td>
              <span class="small" style="color:#475569">{{ $m['creator'] }}</span>
            </td>
            <td>
              <div style="font-size:12px;font-weight:600;color:#090a0f">{{ $m['retention'] }}</div>
            </td>
            <td>
              <span class="tag" style="background:#e2e8f0;color:#090a0f;font-size:11px">{{ $m['access'] }}</span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="foot" style="background:#f1f5f9;padding:12px 16px;border-top:1px solid #e2e8f0;font-size:12px;color:#475569">
    <b>{{ __('Nota Penting H (Garis Panduan m/s 119):') }}</b> {{ __('Penyimpanan rekod pencapaian dalaman, pengeluaran kredensial/lencana digital, penyediaan ACR, dan pendepositan kredit ke dalam ABC adalah empat peringkat berbeza yang TIDAK BOLEH dicampuradukkan. Rekod akademik, kredensial, ACR, dan log audit adalah KEKAL dan tidak boleh dilupuskan sama sekali.') }}
  </div>
</div>

<!-- Section 2: Appendix H8 Governance Action Register -->
<div class="panel" style="margin-bottom:24px">
  <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
    <div>
      <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#0052ff">
        {{ __('Appendix H8 / Lampiran H8') }}
      </div>
      <h2 style="margin:2px 0 0 0">{{ __('Governance Action Register (Daftar Tindakan Tadbir Urus)') }}</h2>
    </div>
    <span class="tag ok">{{ count($governanceActions) }} {{ __('Actions') }}</span>
  </div>
  <div class="table-wrap">
    <table id="h8-actions">
      <thead>
        <tr>
          <th style="width:120px">{{ __('Reference') }}</th>
          <th>{{ __('Governance Decision / Required Action') }}</th>
          <th style="width:140px">{{ __('Responsible Role') }}</th>
          <th style="width:110px">{{ __('Target Date') }}</th>
          <th style="width:100px">{{ __('Status') }}</th>
          <th>{{ __('Evidence & Implementation Notes') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($governanceActions as $ga)
          <tr>
            <td><code>{{ $ga->reference }}</code></td>
            <td style="font-weight:600;color:#090a0f">{{ $ga->item }}</td>
            <td>
              <span class="tag info" style="font-size:11px">{{ $ga->responsible_role }}</span>
            </td>
            <td>{{ \Carbon\Carbon::parse($ga->target_date)->format('d/m/Y') }}</td>
            <td>
              @if($ga->status === 'selesai')
                <span class="tag ok">{{ __('Selesai') }}</span>
              @elseif($ga->status === 'proses')
                <span class="tag info">{{ __('Proses') }}</span>
              @else
                <span class="tag warn">{{ __('Belum') }}</span>
              @endif
            </td>
            <td class="small">{{ $ga->evidence_notes ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- Section 3: Appendix H9 Governance Matrix Confirmation -->
<div class="panel" style="margin-bottom:24px;background:#fbfbfb">
  <div class="hd">
    <div>
      <div style="font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#0052ff">
        {{ __('Appendix H9 / Lampiran H9') }}
      </div>
      <h2 style="margin:2px 0 0 0">{{ __('Governance Matrix Confirmation (Pengesahan Matriks Tadbir Urus)') }}</h2>
    </div>
  </div>
  <div class="bd" style="padding:16px">
    <p style="font-size:13px;line-height:1.6;margin:0 0 16px 0;color:#090a0f">
      <i>{{ __('"Kami mengesahkan bahawa pengurusan rekod, kawalan capaian, pemisahan tugas (SoD), dan pemilikan rekod bagi penawaran micro-credential ini telah dilaksanakan sepenuhnya selaras dengan Garis Panduan Micro-Credential UTeM (Lampiran H)."') }}</i>
    </p>

    <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px">
      <!-- Role 1: Academic Owner / Coordinator (P1) -->
      <div style="background:#ffffff;border:1px solid #e2e8f0;padding:12px 14px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;margin-bottom:4px">
          {{ __('1. Academic Owner (P1)') }}
        </div>
        <div style="font-weight:600;color:#090a0f">Dr. Aina Zulkifli</div>
        <div class="small muted">{{ __('Course Coordinator / Senior Lecturer, FTMK') }}</div>
        <div style="margin-top:8px;font-size:11px;color:#0e6027;font-weight:600">
          &#10003; {{ __('Confirmed & Signed') }} ({{ now()->format('d/m/Y') }})
        </div>
      </div>

      <!-- Role 2: PTj Authority / Dean (P2) -->
      <div style="background:#ffffff;border:1px solid #e2e8f0;padding:12px 14px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;margin-bottom:4px">
          {{ __('2. PTj Authority (P2)') }}
        </div>
        <div style="font-weight:600;color:#090a0f">Hakim Rahman</div>
        <div class="small muted">{{ __('Deputy Dean (Academic), FTMK') }}</div>
        <div style="margin-top:8px;font-size:11px;color:#0e6027;font-weight:600">
          &#10003; {{ __('Confirmed & Signed') }} ({{ now()->format('d/m/Y') }})
        </div>
      </div>

      <!-- Role 3: Academic Authority / Registry (P3 / P6) -->
      <div style="background:#ffffff;border:1px solid #e2e8f0;padding:12px 14px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#475569;margin-bottom:4px">
          {{ __('3. Academic Authority / Senate (P3)') }}
        </div>
        <div style="font-weight:600;color:#090a0f">Prof. Dr. Zulkifli Ahmad</div>
        <div class="small muted">{{ __('Senate Academic Representative / PBA (P3)') }}</div>
        <div style="margin-top:8px;font-size:11px;color:#0e6027;font-weight:600">
          &#10003; {{ __('Confirmed & Signed') }} ({{ now()->format('d/m/Y') }})
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Section 4: Operational Logs Retention Schedules & Controlled Disposal -->
<div class="panel" style="margin-bottom:24px">
  <div class="hd">
    <h2>{{ __('Operational Logs & Controlled Disposal (Non-Academic)') }}</h2>
    <span class="r small muted">{{ count($rows) }} {{ __('log categories') }}</span>
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
            @if(auth()->user()->hasAnyRole($approver) && ! auth()->user()->hasRole('codl_admin') && $r['count'] > 0 && ! $r['held'])
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
              <span class="small muted">{{ __('Requires :role (non-admin)', ['role' => $approver]) }}</span>
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="foot">
    <span class="small muted">
      {{ __('Disposal is approved by the academic authority set in settings (:role), only after the retention period and only if no hold applies. Platform administrators (P5) cannot approve disposal.', ['role' => $approver]) }}
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
          <label for="reason" style="font-weight:600">{{ __('Reason for the hold') }}</label>
          <textarea id="reason" name="reason" rows="2" required placeholder="{{ __('For example: External MQA compliance audit in progress') }}" style="width:100%"></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end">
          <button type="submit" class="btn sm primary">{{ __('Place hold') }}</button>
        </div>
      </form>
    </div>
  </div>

  <div class="panel grow" style="margin-bottom:0">
    <div class="hd">
      <h2>{{ __('Active holds') }}</h2>
      <span class="r small muted">{{ $holds->whereNull('lifted_at')->count() }} {{ __('active') }}</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Placed by') }}</th>
            <th>{{ __('Reason') }}</th>
            <th class="num">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($holds->whereNull('lifted_at') as $h)
            <tr>
              <td><code>{{ $h->category }}</code></td>
              <td>{{ $h->name }}</td>
              <td class="small">{{ $h->reason }}</td>
              <td class="num">
                @if(! auth()->user()->hasRole('codl_admin') || auth()->user()->hasAnyRole('deputy_dean', 'ptj_authority', 'academic_authority', 'records_custodian'))
                <form method="post" action="{{ route('retention.lift', $h->id) }}" style="display:inline">
                  @csrf
                  <button type="submit" class="btn sm ghost">{{ __('Lift hold') }}</button>
                </form>
                @else
                  <span class="small muted">{{ __('Admin cannot lift') }}</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="muted text-center" style="padding:24px">{{ __('No active holds. Disposal operates by schedule.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="panel">
  <div class="hd">
    <h2>{{ __('Disposal log') }}</h2>
    <span class="r small muted">{{ __('Last :count disposal events', ['count' => count($history)]) }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Approved at') }}</th>
          <th>{{ __('Category') }}</th>
          <th>{{ __('Cutoff date') }}</th>
          <th>{{ __('Rows deleted') }}</th>
          <th>{{ __('Approved by') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($history as $d)
          <tr>
            <td>{{ \Carbon\Carbon::parse($d->approved_at)->format('d/m/Y H:i') }}</td>
            <td><code>{{ $d->category }}</code></td>
            <td>{{ \Carbon\Carbon::parse($d->cutoff)->format('d/m/Y') }}</td>
            <td>{{ $d->row_count }}</td>
            <td>{{ $d->name }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="muted text-center" style="padding:24px">{{ __('No disposals recorded yet.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
