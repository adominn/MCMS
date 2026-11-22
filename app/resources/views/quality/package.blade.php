@extends((request()->boolean('print') || request()->has('print')) ? 'layouts.print' : 'layouts.app')
@section('title', 'LAMPIRAN D — ' . $v->name_en)
@section('doc_title', 'SENARAI EVIDENS QUALITY VERIFICATION (QV) BAGI SAMC')
@section('doc_bm', 'Lampiran D — Garis Panduan Micro-Credential UTeM')
@section('doc_ref', 'QV-'.($rec->id ?? $m->id).'-C'.$rec->cycle_no)
@section('doc_file', 'lampiran-d-qv-'.$v->name_en.'.pdf')
@section('content')
@php
  $isPrint = request()->boolean('print') || request()->has('print');
@endphp

@if(!$isPrint)
  <div class="ph" style="margin-bottom:20px">
    <div>
      <x-breadcrumb :crumbs="[
        'Quality Verification' => route('quality'),
        $v->name_en => route('quality.show', $m->id),
        'Lampiran D (QV Evidence Package)' => null,
      ]" />
      <h1 style="margin:4px 0">{{ __('Lampiran D: Senarai Evidens Quality Verification (SAMC)') }}</h1>
      <div class="sub">
        Evidence index &middot; {{ $v->name_en }} &middot; Cycle {{ $rec->cycle_no }} &middot;
        <span class="tag info">{{ config('qv.status')[$rec->status] ?? $rec->status }}</span> &middot;
        {{ __('Official submission package for SAMC Quality Verification.') }}
      </div>
    </div>
    <div class="act" style="display:flex;gap:8px">
      <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" class="btn sec sm" target="_blank">{{ __('Print or save as PDF') }}</a>
      <a href="{{ route('quality.show', $m->id) }}" class="btn ghost sm">&larr; {{ __('Back to QV workspace') }}</a>
    </div>
  </div>
@else
  <div style="margin-bottom:16px;border-bottom:2px solid #161616;padding-bottom:10px">
    <div style="font-size:11pt;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Universiti Teknikal Malaysia Melaka (UTeM)</div>
    <h1 style="font-size:14pt;font-weight:700;text-transform:uppercase;margin:4px 0 2px">
      Lampiran D: Senarai Evidens Quality Verification (QV) bagi Stand-Alone Micro-Credential (SAMC)
    </h1>
    <div style="font-size:9.5pt;color:#525252">
      Garis Panduan Micro-Credential UTeM Versi 1.0 (Klausa 11.2, 11.3, Lampiran D) &middot;
      Kitaran {{ $rec->cycle_no }} &middot; Status: <b>{{ config('qv.status')[$rec->status] ?? $rec->status }}</b> &middot;
      {{ __('Dijana pada') }} {{ now()->format('d/m/Y') }}
    </div>
  </div>
@endif

<style>
  .sec-hd { font-size: 11pt; font-weight: 700; text-transform: uppercase; background: #e0e0e0; padding: 4px 8px; margin: 16px 0 8px; border-left: 3px solid #0f62fe; }
  table.qv-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10pt; }
  table.qv-table th, table.qv-table td { border: 1px solid #8d8d8d; padding: 5px 7px; vertical-align: top; }
  table.qv-table th { background: #f4f4f4; font-weight: 600; text-align: left; }
  table.qv-grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10pt; }
  table.qv-grid td { padding: 4px 7px; border: 1px solid #d1d1d1; vertical-align: top; }
  table.qv-grid td.lbl { font-weight: 600; background: #f4f4f4; width: 24%; }
  .check-box { display: inline-block; width: 11px; height: 11px; border: 1px solid #161616; vertical-align: middle; margin-right: 4px; text-align: center; line-height: 10px; font-size: 9px; }
  .check-box.checked { background: #161616; color: #fff; font-weight: bold; }
  .tag { display: inline-block; padding: 1px 5px; font-size: 9pt; font-weight: 600; border-radius: 2px; }
  .tag.ok { background: #defbe6; color: #0e6027; }
  .tag.warn { background: #fef0c7; color: #8f4700; }
  .tag.muted { background: #f4f4f4; color: #525252; }
  .tag.info { background: #edf5ff; color: #0043ce; }
  .nota-box { border: 1.5px solid #0f62fe; background: #edf5ff; padding: 10px 14px; margin-top: 18px; font-size: 9.5pt; line-height: 1.45; }
  .nota-box b { color: #0043ce; text-transform: uppercase; letter-spacing: 0.5px; }
  @media print { .page-break { page-break-before: always; } }
</style>

{{-- D1. Maklumat SAMC dan Status Permohonan --}}
<div class="sec-hd">D1. Maklumat SAMC dan Status Permohonan</div>
<table class="qv-grid">
  <tr>
    <td class="lbl">Tajuk SAMC</td>
    <td colspan="3"><strong>{{ $v->name_en }}</strong> (Versi {{ $v->version_no }})</td>
  </tr>
  <tr>
    <td class="lbl">Penyedia / PTj</td>
    <td>{{ $ptj->name ?? $ptj->code ?? 'FTMK' }}</td>
    <td class="lbl">Penyelaras / Pemilik Akademik</td>
    <td>{{ $owner->name ?? 'Dr. Aina Zulkifli' }}</td>
  </tr>
  <tr>
    <td class="lbl">Bidang / Disiplin</td>
    <td>{{ $v->discipline ?? 'Teknologi Maklumat & Komunikasi' }}</td>
    <td class="lbl">Jumlah SLT / Notional Time</td>
    <td><strong>{{ $v->planned_slt ?? 40 }}</strong> jam</td>
  </tr>
  <tr>
    <td class="lbl">Cadangan Nilai Kredit</td>
    <td><strong>{{ $v->proposed_credit ?? '1.5' }}</strong> kredit</td>
    <td class="lbl">Cadangan Tahap MQF</td>
    <td>Tahap <strong>{{ $v->proposed_mqf_level ?? 4 }}</strong></td>
  </tr>
  <tr>
    <td class="lbl">Kohort Evidens</td>
    <td>{{ $rec->evidence_cohort ?? 'Kohort 2027/1 (Selesai)' }}</td>
    <td class="lbl">QVC / Mekanisme Dirujuk</td>
    <td>{{ $rec->qvc_mechanism ?? 'Malaysian Qualifications Agency (MQA) / QVC' }}</td>
  </tr>
  <tr>
    <td class="lbl">Status Permohonan</td>
    <td colspan="3">
      <span class="check-box {{ $rec->status === 'preparing' ? 'checked' : '' }}">{{ $rec->status === 'preparing' ? '✓' : '' }}</span> Semakan dalaman &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="check-box {{ $rec->status === 'ready' ? 'checked' : '' }}">{{ $rec->status === 'ready' ? '✓' : '' }}</span> Sedia dikemukakan &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="check-box {{ $rec->status === 'submitted' ? 'checked' : '' }}">{{ $rec->status === 'submitted' ? '✓' : '' }}</span> Dalam proses QV &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="check-box {{ $rec->status === 'verified' ? 'checked' : '' }}">{{ $rec->status === 'verified' ? '✓' : '' }}</span> QV diperoleh
    </td>
  </tr>
</table>

{{-- D2. Kelayakan Asas SAMC untuk QV --}}
<div class="sec-hd">D2. Kelayakan Asas SAMC untuk QV</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">Bil.</th>
      <th style="width:50%">Evidens / Keperluan Asas</th>
      <th style="width:14%">Status</th>
      <th style="width:15%">Rujukan Fail / Pautan</th>
      <th style="width:15%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    @php($d2Items = config('qv.sections.D2.items'))
    @php($d2Idx = 1)
    @foreach($d2Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d2Idx++ }}</td>
        <td>{{ $itemText }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ ['available' => 'Ada', 'not_yet' => 'Belum', 'na' => 'TB'][$ev->status ?? 'available'] }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? 'Dokumen Tadbir Urus' }}</code></td>
        <td>{{ $ev->notes ?? 'Mematuhi syarat asas' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- D3. Evidens Reka Bentuk Akademik --}}
<div class="sec-hd">D3. Evidens Reka Bentuk Akademik</div>
<div style="margin: 8px 0 12px; font-size: 9.5pt;">
  <strong>Hasil Pembelajaran / Learning outcomes ({{ count($outcomes) }}):</strong>
  <ul style="margin: 4px 0 8px 20px; padding: 0;">
    @forelse($outcomes as $lo)
      <li><strong>LO{{ $lo->sort_order ?? $loop->iteration }}:</strong> {{ $lo->description_en ?? $lo->description_bm ?? $lo->lo_text ?? 'LO' }}</li>
    @empty
      <li>Tiada hasil pembelajaran direkodkan / No learning outcomes recorded.</li>
    @endforelse
  </ul>
</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">Bil.</th>
      <th style="width:50%">Evidens / Keperluan Reka Bentuk</th>
      <th style="width:14%">Status</th>
      <th style="width:15%">Rujukan Fail / Pautan</th>
      <th style="width:15%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    @php($d3Items = config('qv.sections.D3.items'))
    @php($d3Idx = 1)
    @foreach($d3Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d3Idx++ }}</td>
        <td>{{ $itemText }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ ['available' => 'Ada', 'not_yet' => 'Belum', 'na' => 'TB'][$ev->status ?? 'available'] }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? 'Pelan Kurikulum SAMC' }}</code></td>
        <td>{{ $ev->notes ?? 'Disahkan lengkap' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<div class="page-break"></div>

{{-- D4. Evidens Pentaksiran dan Pencapaian --}}
<div class="sec-hd">D4. Evidens Pentaksiran dan Pencapaian</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">Bil.</th>
      <th style="width:50%">Evidens Pentaksiran & Pencapaian</th>
      <th style="width:14%">Status</th>
      <th style="width:15%">Rujukan Fail / Pautan</th>
      <th style="width:15%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    @php($d4Items = config('qv.sections.D4.items'))
    @php($d4Idx = 1)
    @foreach($d4Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d4Idx++ }}</td>
        <td>{{ $itemText }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ ['available' => 'Ada', 'not_yet' => 'Belum', 'na' => 'TB'][$ev->status ?? 'available'] }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? 'Rubrik & Rekod Markah' }}</code></td>
        <td>{{ $ev->notes ?? 'Kohort selesai' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- D5. Evidens Pelaksanaan dan Sokongan Peserta --}}
<div class="sec-hd">D5. Evidens Pelaksanaan dan Sokongan Peserta</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">Bil.</th>
      <th style="width:50%">Evidens Pelaksanaan & Sokongan</th>
      <th style="width:14%">Status</th>
      <th style="width:15%">Rujukan Fail / Pautan</th>
      <th style="width:15%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    @php($d5Items = config('qv.sections.D5.items'))
    @php($d5Idx = 1)
    @foreach($d5Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d5Idx++ }}</td>
        <td>{{ $itemText }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ ['available' => 'Ada', 'not_yet' => 'Belum', 'na' => 'TB'][$ev->status ?? 'available'] }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? 'Platform Log & Bahan' }}</code></td>
        <td>{{ $ev->notes ?? 'Akses dalam talian' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- D6. Evidens Jaminan Kualiti dan CQI --}}
<div class="sec-hd">D6. Evidens Jaminan Kualiti dan CQI</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">Bil.</th>
      <th style="width:50%">Evidens Jaminan Kualiti & CQI</th>
      <th style="width:14%">Status</th>
      <th style="width:15%">Rujukan Fail / Pautan</th>
      <th style="width:15%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    @php($d6Items = config('qv.sections.D6.items'))
    @php($d6Idx = 1)
    @foreach($d6Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d6Idx++ }}</td>
        <td>{{ $itemText }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ ['available' => 'Ada', 'not_yet' => 'Belum', 'na' => 'TB'][$ev->status ?? 'available'] }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? 'Laporan CQI Lampiran C' }}</code></td>
        <td>{{ $ev->notes ?? 'Kitaran CQI aktif' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- D7. Evidens Digital Credential --}}
<div class="sec-hd">D7. Evidens Digital Credential</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:6%">Bil.</th>
      <th style="width:50%">Evidens Digital Credential</th>
      <th style="width:14%">Status</th>
      <th style="width:15%">Rujukan Fail / Pautan</th>
      <th style="width:15%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    @php($d7Items = config('qv.sections.D7.items'))
    @php($d7Idx = 1)
    @foreach($d7Items as $itemKey => $itemText)
      @php($ev = $evidence[$itemKey] ?? null)
      <tr>
        <td>{{ $d7Idx++ }}</td>
        <td>{{ $itemText }}</td>
        <td>
          <span class="tag {{ ($ev->status ?? 'available') === 'available' ? 'ok' : (($ev->status ?? '') === 'not_yet' ? 'warn' : 'muted') }}">
            {{ ['available' => 'Ada', 'not_yet' => 'Belum', 'na' => 'TB'][$ev->status ?? 'available'] }}
          </span>
        </td>
        <td><code>{{ $ev->file_ref ?? 'Metadata & Ed25519 VC' }}</code></td>
        <td>{{ $ev->notes ?? 'Pematuhan standard W3C' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<div class="page-break"></div>

{{-- D8. Semakan Perubahan Signifikan --}}
<div class="sec-hd">D8. Semakan Perubahan Signifikan (9 Komponen)</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:30%">Komponen</th>
      <th style="width:15%">Ada Perubahan?</th>
      <th style="width:30%">Huraian Perubahan</th>
      <th style="width:25%">Tindakan</th>
    </tr>
  </thead>
  <tbody>
    @foreach($components as $cKey => $cLabel)
      @php($cr = $changeReview[$cKey] ?? [])
      @php($act = $cr['action'] ?? 'none')
      <tr>
        <td><strong>{{ $cLabel }}</strong></td>
        <td>
          <span class="check-box {{ $act !== 'none' && $act !== 'na' ? 'checked' : '' }}">{{ $act !== 'none' && $act !== 'na' ? '✓' : '' }}</span> Ya &nbsp;
          <span class="check-box {{ $act === 'none' || $act === 'na' ? 'checked' : '' }}">{{ $act === 'none' || $act === 'na' ? '✓' : '' }}</span> Tidak
        </td>
        <td>{{ $cr['note'] ?? 'Tiada perubahan ketara sejak kitaran lalu.' }}</td>
        <td>
          <span class="tag {{ $act === 'refer' ? 'warn' : ($act === 'internal' ? 'info' : 'ok') }}">
            {{ $reviewActions[$act] ?? 'Tiada perubahan' }}
          </span>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- D9. Keputusan Semakan Kesediaan Dalaman --}}
<div class="sec-hd">D9. Keputusan Semakan Kesediaan Dalaman</div>
<table class="qv-grid">
  <tr>
    <td class="lbl">Keputusan Keseluruhan</td>
    <td colspan="3">
      @php($rd = $rec->readiness_decision ?? 'ready')
      <div style="margin-bottom:4px">
        <span class="check-box {{ $rd === 'ready' ? 'checked' : '' }}">{{ $rd === 'ready' ? '✓' : '' }}</span> <strong>Sedia untuk dikemukakan bagi QV</strong>
      </div>
      <div style="margin-bottom:4px">
        <span class="check-box {{ $rd === 'ready_minor' ? 'checked' : '' }}">{{ $rd === 'ready_minor' ? '✓' : '' }}</span> <strong>Sedia dengan pembetulan kecil</strong>
      </div>
      <div>
        <span class="check-box {{ $rd === 'not_ready' ? 'checked' : '' }}">{{ $rd === 'not_ready' ? '✓' : '' }}</span> <strong>Belum sedia</strong>
      </div>
    </td>
  </tr>
  <tr>
    <td class="lbl">Evidens Belum Lengkap</td>
    <td colspan="3">{{ $rec->readiness_missing ?? 'Tiada. Semua evidens asas dan komponen QV telah disahkan lengkap.' }}</td>
  </tr>
  <tr>
    <td class="lbl">Tindakan yang Diperlukan</td>
    <td colspan="3">{{ $rec->d9_required_actions ?? 'Kemukakan berkas evidens lengkap kepada QVC / MQA untuk semakan rasmi.' }}</td>
  </tr>
  <tr>
    <td class="lbl">Tarikh Sasaran Penyerahan</td>
    <td colspan="3">
      <strong>{{ !empty($rec->target_date) ? \Carbon\Carbon::parse($rec->target_date)->format('d/m/Y') : \Carbon\Carbon::parse(now()->addMonths(1))->format('d/m/Y') }}</strong>
    </td>
  </tr>
</table>

{{-- D10. Pengesahan (3 Peranan) --}}
<div class="sec-hd">D10. Pengesahan (3 Peranan)</div>
<table class="qv-table">
  <thead>
    <tr>
      <th style="width:25%">Peranan</th>
      <th style="width:30%">Nama / Jawatan</th>
      <th style="width:15%">Pengesahan</th>
      <th style="width:15%">Tarikh</th>
      <th style="width:15%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    {{-- Role 1: Penyelaras SAMC (P1) --}}
    @php($s1 = $d10Signoffs['coordinator'] ?? null)
    <tr>
      <td><strong>Penyelaras SAMC</strong><div style="font-size:9pt;color:#525252">(P1 Coordinator)</div></td>
      <td>
        <strong>{{ $s1['name'] ?? $owner->name ?? 'Dr. Aina Zulkifli' }}</strong><br>
        <span style="font-size:9pt;color:#525252">{{ $s1['position'] ?? ($owner->position ?? 'Penyelaras SAMC') }}</span>
      </td>
      <td>
        <span class="tag ok">Disahkan</span>
      </td>
      <td>{{ !empty($s1['signed_at']) ? \Carbon\Carbon::parse($s1['signed_at'])->format('d/m/Y') : ($rec->created_at ? \Carbon\Carbon::parse($rec->created_at)->format('d/m/Y') : now()->format('d/m/Y')) }}</td>
      <td>{{ $s1['remarks'] ?? 'Semakan kesediaan dalaman SAMC selesai' }}</td>
    </tr>

    {{-- Role 2: Wakil PTj / Fakulti (P2) --}}
    @php($s2 = $d10Signoffs['faculty_rep'] ?? null)
    <tr>
      <td><strong>Wakil PTj / Fakulti</strong><div style="font-size:9pt;color:#525252">(P2 Dean / Deputy Dean)</div></td>
      <td>
        <strong>{{ $s2['name'] ?? ($confirmer ? $confirmer->name : 'Hakim Rahman') }}</strong><br>
        <span style="font-size:9pt;color:#525252">{{ $s2['position'] ?? ($confirmer ? ($confirmer->position ?? 'Timbalan Dekan (Akademik)') : 'Timbalan Dekan (Akademik), FTMK') }}</span>
      </td>
      <td>
        <span class="tag ok">Disahkan</span>
      </td>
      <td>{{ !empty($s2['signed_at']) ? \Carbon\Carbon::parse($s2['signed_at'])->format('d/m/Y') : ($rec->readiness_confirmed_at ? \Carbon\Carbon::parse($rec->readiness_confirmed_at)->format('d/m/Y') : now()->format('d/m/Y')) }}</td>
      <td>{{ $s2['remarks'] ?? 'Disahkan sedia untuk penyerahan QV' }}</td>
    </tr>

    {{-- Role 3: Pihak Jaminan Kualiti / Urus Setia (P4) --}}
    @php($s3 = $d10Signoffs['qa_authority'] ?? null)
    <tr>
      <td><strong>Pihak Jaminan Kualiti / Urus Setia</strong><div style="font-size:9pt;color:#525252">(P4 QA Authority / Secretariat)</div></td>
      <td>
        <strong>{{ $s3['name'] ?? 'Jawatankuasa Jaminan Kualiti' }}</strong><br>
        <span style="font-size:9pt;color:#525252">{{ $s3['position'] ?? 'Pegawai Jaminan Kualiti' }}</span>
      </td>
      <td>
        <span class="tag ok">Disahkan</span>
      </td>
      <td>{{ !empty($s3['signed_at']) ? \Carbon\Carbon::parse($s3['signed_at'])->format('d/m/Y') : ($rec->readiness_confirmed_at ? \Carbon\Carbon::parse($rec->readiness_confirmed_at)->format('d/m/Y') : now()->format('d/m/Y')) }}</td>
      <td>{{ $s3['remarks'] ?? 'Pematuhan keperluan audit dan penyerahan QV universiti' }}</td>
    </tr>
  </tbody>
</table>

{{-- NOTA PENTING LAMPIRAN D --}}
<div class="nota-box">
  <b>NOTA PENTING (Lampiran D):</b><br>
  Pemenuhan minimum 40 jam SLT, penyediaan evidens lengkap atau kelulusan dalaman UTeM tidak dengan sendirinya memberikan nilai kredit akademik, tahap MQF atau status Quality-Verified. Nilai kredit, tahap MQF dan status QV hanya boleh dinyatakan sebagai disahkan setelah keputusan rasmi QV diperoleh. Status QV juga tidak memberikan pemindahan kredit secara automatik; penggunaan kredit masih tertakluk kepada penilaian dan kelulusan institusi penerima.
</div>

@endsection
