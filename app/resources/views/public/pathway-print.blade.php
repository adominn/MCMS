@extends('layouts.print')
@section('title', $w->code . ' ' . $w->name . ' - ' . __('Pathway Articulation'))
@section('doc_title', __('Degree Articulation Pathway Specification'))
@section('doc_bm', 'Jadual Spesifikasi Laluan Artikulasi Ijazah')
@section('doc_ref', 'PW-' . $w->code . ' · v' . $w->version_no)

@section('content')
<div style="margin-bottom:16px">
  <h1 style="font-size:16pt;margin:0 0 4px">{{ $w->code }} - {{ $w->name }}</h1>
  <div class="muted" style="font-size:10pt">
    MQF Level {{ $w->mqf_level }} &middot; {{ $w->total_credits }} graduating credits &middot; {{ ucfirst($w->mode) }}
    @if(!empty($w->approval_ref))
      &middot; Senate Reference: <b>{{ $w->approval_ref }}</b>
    @endif
    @if(!empty($w->approved_on))
      ({{ \Carbon\Carbon::parse($w->approved_on)->format('d/m/Y') }})
    @endif
  </div>
</div>

<div class="note info" style="margin-bottom:16px;font-size:9.5pt;line-height:1.4">
  <b>{{ __('Official Articulation Roadmap:') }}</b>
  {{ __('This document lists the credit-bearing micro-credentials approved by the Senate for articulation into this undergraduate degree programme. A pathway transfers no credit by itself; each participant\'s credit is decided individually through credit transfer upon official Pejabat Pendaftar admission. At least :min% of graduating credits must be earned directly at UTeM under residency rules.', ['min' => $min]) }}
</div>

<h2 style="font-size:12pt;border-bottom:1px solid var(--primary);padding-bottom:4px;margin-bottom:8px">
  {{ __('Approved Micro-Credential to Degree Course Mappings') }}
</h2>

<table style="width:100%;margin-bottom:16px;font-size:9.5pt">
  <thead>
    <tr>
      <th style="width:35%">{{ __('Micro-credential') }}</th>
      <th style="width:40%">{{ __('Mapped Degree Course') }}</th>
      <th style="width:15%">{{ __('Equivalence') }}</th>
      <th class="num" style="width:10%">{{ __('Credits') }}</th>
    </tr>
  </thead>
  <tbody>
    @php $tot = 0; @endphp
    @forelse($items as $i)
      @php $tot += (float) $i->credits; @endphp
      <tr>
        <td><b>{{ $i->name_en }}</b></td>
        <td>{{ $i->course_code }} {{ $i->course_name }}</td>
        <td>
          <span style="font-size:8.5pt;text-transform:capitalize">{{ $i->preliminary ?? 'Suitable' }}</span>
        </td>
        <td class="num"><b>{{ mcms_num($i->credits) }}</b></td>
      </tr>
    @empty
      <tr>
        <td colspan="4" class="muted" style="text-align:center;padding:12px">{{ __('No mapped credentials recorded.') }}</td>
      </tr>
    @endforelse
    <tr style="border-top:2px solid var(--hairline);background:var(--s1)">
      <td colspan="3"><b>{{ __('Total Potential Transferable Credits Under This Roadmap') }}</b></td>
      <td class="num"><b>{{ mcms_num($tot) }}</b></td>
    </tr>
  </tbody>
</table>

<div style="margin-top:20px;border-top:1px solid var(--border, #090a0f);padding-top:12px;font-size:8.5pt;color:var(--muted);line-height:1.4">
  <b>{{ __('Academic Governance & Regulatory Notice:') }}</b>
  {{ __('No micro-credential leads to a degree automatically, however many are accumulated. Credit exemption is subject to faculty approval, valid Academic Bank of Credit (ABC) deposits within 10 years of ABC deposit date (Cl. 12.8), and compliance with the 30% UTeM minimum home-credit requirement.') }}
</div>

<div class="page-break" style="page-break-before:always;margin-top:24px"></div>

{{-- G4. Pemetaan kepada Hasil Pembelajaran Program (PLO) --}}
<h2 style="font-size:11pt;border-bottom:1.5px solid #0052ff;padding-bottom:3px;margin:16px 0 8px;text-transform:uppercase">
  G4. Pemetaan kepada Hasil Pembelajaran Program (PLO)
</h2>
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;font-size:9pt">
  <thead>
    <tr style="background:#f1f5f9">
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:15%">PLO</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:30%">Credential / Komponen</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:15%">Sumbangan</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:20%">Evidens Pencapaian</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:20%">Jurang</th>
    </tr>
  </thead>
  <tbody>
    @forelse($g4 ?? [] as $ploKey => $pRow)
      <tr>
        <td style="border:1px solid #8d8d8d;padding:4px 6px"><strong>{{ $pRow['plo'] ?? $ploKey }}</strong></td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $pRow['components'] ?? '-' }}</td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $pRow['contribution'] ?? 'Sokongan' }}</td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $pRow['evidence'] ?? 'Pentaksiran MC' }}</td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $pRow['gaps'] ?? 'Tiada jurang' }}</td>
      </tr>
    @empty
      <tr><td colspan="5" style="border:1px solid #8d8d8d;padding:6px;text-align:center;color:#475569">Tiada pemetaan PLO khusus direkodkan.</td></tr>
    @endforelse
  </tbody>
</table>

{{-- G5. Pemetaan kepada Body of Knowledge (BoK) --}}
<h2 style="font-size:11pt;border-bottom:1.5px solid #0052ff;padding-bottom:3px;margin:16px 0 8px;text-transform:uppercase">
  G5. Pemetaan kepada Body of Knowledge (BoK)
</h2>
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;font-size:9pt">
  <thead>
    <tr style="background:#f1f5f9">
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:20%">Domain BoK</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:25%">Keperluan Program</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:25%">Credential / Kursus Menyumbang</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:15%">Liputan</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:15%">Jurang / Tindakan</th>
    </tr>
  </thead>
  <tbody>
    @forelse($g5 ?? [] as $bKey => $bRow)
      <tr>
        <td style="border:1px solid #8d8d8d;padding:4px 6px"><strong>{{ $bRow['domain'] ?? $bKey }}</strong></td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $bRow['requirement'] ?? '-' }}</td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $bRow['contributing'] ?? '-' }}</td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">
          @php($cov = $bRow['coverage'] ?? 'none')
          <span style="font-weight:600;color:{{ $cov === 'full' ? '#0e6027' : ($cov === 'partial' ? '#8f4700' : '#da1e28') }}">
            {{ ['full' => 'Lengkap', 'partial' => 'Sebahagian', 'none' => 'Tiada'][$cov] ?? ucfirst($cov) }}
          </span>
        </td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $bRow['gap_action'] ?? '-' }}</td>
      </tr>
    @empty
      <tr><td colspan="5" style="border:1px solid #8d8d8d;padding:6px;text-align:center;color:#475569">Tiada pemetaan Body of Knowledge direkodkan.</td></tr>
    @endforelse
  </tbody>
</table>

{{-- G8. Baki Keperluan Program yang Perlu Disempurnakan (7 Komponen) --}}
<h2 style="font-size:11pt;border-bottom:1.5px solid #0052ff;padding-bottom:3px;margin:16px 0 8px;text-transform:uppercase">
  G8. Baki Keperluan Program yang Perlu Disempurnakan (7 Komponen)
</h2>
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;font-size:9pt">
  <thead>
    <tr style="background:#f1f5f9">
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:25%">Komponen Program</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:15%">Status</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:25%">Kaedah Pemenuhan</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:10%;text-align:right">Kredit</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:25%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    @foreach($g8 ?? [] as $rKey => $rRow)
      <tr>
        <td style="border:1px solid #8d8d8d;padding:4px 6px"><strong>{{ $rRow['name'] ?? ucfirst($rKey) }}</strong></td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">
          @php($st = $rRow['status'] ?? 'todo')
          <span>{{ ['done' => 'Lengkap', 'todo' => 'Baki', 'na' => 'TB'][$st] ?? ucfirst($st) }}</span>
        </td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $rRow['method'] ?? 'Kursus bersemuka / ODL' }}</td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px;text-align:right"><strong>{{ $rRow['credits'] ?? 0 }}</strong></td>
        <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $rRow['remarks'] ?? '-' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- G9. Ringkasan Analisis Kredit dan Jurang --}}
<h2 style="font-size:11pt;border-bottom:1.5px solid #0052ff;padding-bottom:3px;margin:16px 0 8px;text-transform:uppercase">
  G9. Ringkasan Analisis Kredit dan Jurang
</h2>
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;font-size:9pt">
  <tbody>
    <tr>
      <td style="border:1px solid #8d8d8d;padding:4px 6px;background:#f1f5f9;width:35%"><strong>Jumlah Kredit Bergraduat</strong></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px;width:15%"><strong>{{ $g9['total_credits'] ?? $w->total_credits }}</strong> kredit</td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px;background:#f1f5f9;width:35%"><strong>Kredit Langsung UTeM (&ge; 30%)</strong></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px;width:15%"><strong>{{ $g9['direct_utem_credits'] ?? 36 }}</strong> kredit</td>
    </tr>
    <tr>
      <td style="border:1px solid #8d8d8d;padding:4px 6px;background:#f1f5f9"><strong>Kredit Dicadangkan dari Credential Terdahulu</strong></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $g9['proposed_prior_credits'] ?? $tot }} kredit</td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px;background:#f1f5f9"><strong>Kredit Diluluskan Selepas Kesetaraan</strong></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px"><strong>{{ $g9['approved_credits'] ?? $tot }}</strong> kredit</td>
    </tr>
    <tr>
      <td style="border:1px solid #8d8d8d;padding:4px 6px;background:#f1f5f9"><strong>Baki Kredit Diperlukan untuk Bergraduat</strong></td>
      <td colspan="3" style="border:1px solid #8d8d8d;padding:4px 6px"><strong>{{ $g9['remaining_credits'] ?? 0 }}</strong> kredit</td>
    </tr>
    <tr>
      <td style="border:1px solid #8d8d8d;padding:4px 6px;background:#f1f5f9"><strong>Jurang Utama Dikenal Pasti</strong></td>
      <td colspan="3" style="border:1px solid #8d8d8d;padding:4px 6px">{{ $g9['key_gaps'] ?? 'Sekurang-kurangnya 30% kredit wajib disempurnakan terus di UTeM.' }}</td>
    </tr>
  </tbody>
</table>

{{-- G11. Pengesahan Pemetaan (3 Peranan) --}}
<h2 style="font-size:11pt;border-bottom:1.5px solid #0052ff;padding-bottom:3px;margin:16px 0 8px;text-transform:uppercase">
  G11. Pengesahan Pemetaan (3 Peranan)
</h2>
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;font-size:9pt">
  <thead>
    <tr style="background:#f1f5f9">
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:25%">Peranan</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:30%">Nama / Jawatan</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:15%">Status</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:15%">Tarikh</th>
      <th style="border:1px solid #8d8d8d;padding:4px 6px;width:15%">Catatan</th>
    </tr>
  </thead>
  <tbody>
    @php($s1 = $g11['coordinator'] ?? null)
    <tr>
      <td style="border:1px solid #8d8d8d;padding:4px 6px"><strong>Penyelaras Program Stackable</strong><br><span style="font-size:8pt;color:#475569">(P1 Coordinator)</span></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">
        <strong>{{ $s1['name'] ?? 'Penyelaras Program Stackable' }}</strong><br>
        <span style="font-size:8pt;color:#475569">{{ $s1['position'] ?? 'Penyelaras Program' }}</span>
      </td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px"><span style="color:#0e6027;font-weight:600">Disahkan</span></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ !empty($s1['signed_at']) ? \Carbon\Carbon::parse($s1['signed_at'])->format('d/m/Y') : now()->format('d/m/Y') }}</td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $s1['remarks'] ?? 'Pemetaan disahkan lengkap' }}</td>
    </tr>
    @php($s2 = $g11['faculty_rep'] ?? null)
    <tr>
      <td style="border:1px solid #8d8d8d;padding:4px 6px"><strong>Wakil Fakulti / Timbalan Dekan</strong><br><span style="font-size:8pt;color:#475569">(P2 Faculty Rep)</span></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">
        <strong>{{ $s2['name'] ?? 'Timbalan Dekan (Akademik)' }}</strong><br>
        <span style="font-size:8pt;color:#475569">{{ $s2['position'] ?? 'Timbalan Dekan (Akademik)' }}</span>
      </td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px"><span style="color:#0e6027;font-weight:600">Disahkan</span></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ !empty($s2['signed_at']) ? \Carbon\Carbon::parse($s2['signed_at'])->format('d/m/Y') : now()->format('d/m/Y') }}</td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $s2['remarks'] ?? 'Kelulusan Senat direkodkan' }}</td>
    </tr>
    @php($s3 = $g11['qa_assessor'] ?? null)
    <tr>
      <td style="border:1px solid #8d8d8d;padding:4px 6px"><strong>Penilai Kesetaraan / Urus Setia QA</strong><br><span style="font-size:8pt;color:#475569">(P3/P4 Assessor)</span></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">
        <strong>{{ $s3['name'] ?? 'Pegawai Penilai Kesetaraan' }}</strong><br>
        <span style="font-size:8pt;color:#475569">{{ $s3['position'] ?? 'Pegawai Penilai Kesetaraan' }}</span>
      </td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px"><span style="color:#0e6027;font-weight:600">Disahkan</span></td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ !empty($s3['signed_at']) ? \Carbon\Carbon::parse($s3['signed_at'])->format('d/m/Y') : now()->format('d/m/Y') }}</td>
      <td style="border:1px solid #8d8d8d;padding:4px 6px">{{ $s3['remarks'] ?? 'Pematuhan standard MQF' }}</td>
    </tr>
  </tbody>
</table>

{{-- NOTA PENTING LAMPIRAN G --}}
<div style="border:1.5px solid #0052ff;background:#edf4ff;padding:10px 14px;margin-top:14px;font-size:9.5pt;line-height:1.45">
  <b style="color:#0043ce;text-transform:uppercase;letter-spacing:0.5px">NOTA PENTING (Lampiran G):</b><br>
  Laluan Stackable Degree bukan proses menjumlahkan kredit semata-mata. Pemetaan ini menetapkan kesetaraan kurikulum yang berpotensi untuk pemindahan kredit, namun pemindahan sebenar tertakluk kepada permohonan individu, kelulusan Fakulti, pemenuhan sekurang-kurangnya 30% kredit disempurnakan terus di UTeM sebagai institusi penganugerah, dan sekurang-kurangnya satu (1) semester residensi bagi program mod konvensional.
</div>
@endsection
