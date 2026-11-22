<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LAMPIRAN C — {{ $r->reference }}</title>
  <style>
    @page { size: A4 portrait; margin: 14mm 14mm 16mm 14mm; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 11px; line-height: 1.45; color: #090a0f; background: #fff; margin: 0; padding: 20px; }
    .print-bar { display: flex; justify-content: space-between; align-items: center; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 8px 14px; margin-bottom: 20px; font-size: 12px; }
    .btn { display: inline-block; padding: 5px 12px; background: #0052ff; color: #fff; text-decoration: none; font-weight: 500; font-size: 11px; cursor: pointer; border: 1px solid #0052ff; }
    .btn-outline { background: #fff; color: #090a0f; border-color: #8d8d8d; margin-left: 6px; }
    @media print { .print-bar { display: none !important; } body { padding: 0; } }

    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; border-bottom: 2px solid #090a0f; padding-bottom: 8px; }
    .header-table td { vertical-align: top; }
    .inst-title { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #090a0f; letter-spacing: 0.5px; }
    .doc-badge { text-align: right; font-size: 10px; font-weight: 700; color: #475569; }
    .doc-badge span { display: inline-block; border: 1px solid #090a0f; padding: 2px 6px; font-size: 11px; font-weight: 700; color: #090a0f; background: #f1f5f9; }
    h1 { font-size: 14px; font-weight: 700; text-transform: uppercase; margin: 4px 0 2px; letter-spacing: 0.5px; }
    .subtitle { font-size: 10px; color: #475569; margin-bottom: 12px; }

    .sec-title { font-size: 11px; font-weight: 700; text-transform: uppercase; background: #e2e8f0; padding: 4px 8px; margin: 14px 0 6px; border-left: 3px solid #0052ff; }
    table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10.5px; }
    table.data-table th, table.data-table td { border: 1px solid #8d8d8d; padding: 4px 6px; vertical-align: top; }
    table.data-table th { background: #f1f5f9; font-weight: 600; text-align: left; }
    table.grid-form { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10.5px; }
    table.grid-form td { padding: 3px 6px; border: 1px solid #d1d1d1; vertical-align: top; }
    table.grid-form td.label { font-weight: 600; background: #f1f5f9; width: 22%; }
    .tag { display: inline-block; padding: 1px 5px; font-size: 9.5px; font-weight: 600; border-radius: 2px; }
    .tag.ok { background: #edfcf0; color: #0e6027; }
    .tag.warn { background: #fef0c7; color: #8f4700; }
    .tag.err { background: #ffebee; color: #b71c1c; }
    .tag.info { background: #edf4ff; color: #0043ce; }

    .nota-box { border: 1.5px solid #0052ff; background: #edf4ff; padding: 8px 12px; margin-top: 14px; font-size: 10px; line-height: 1.45; }
    .nota-box b { color: #0043ce; text-transform: uppercase; letter-spacing: 0.5px; }
    .check-box { display: inline-block; width: 11px; height: 11px; border: 1px solid #090a0f; vertical-align: middle; margin-right: 4px; text-align: center; line-height: 10px; font-size: 9px; }
    .check-box.checked { background: #090a0f; color: #fff; font-weight: bold; }
    .page-break { page-break-before: always; }
  </style>
</head>
<body>

  <div class="print-bar">
    <div><strong>LAMPIRAN C — Template Jaminan Kualiti, Pemantauan dan CQI</strong> &middot; {{ $r->reference }}</div>
    <div>
      <a href="javascript:window.print()" class="btn">Print / Save PDF</a>
      <a href="{{ route('cqi.show', $r->id) }}" class="btn btn-outline">&larr; Back to CQI View</a>
    </div>
  </div>

  <table class="header-table">
    <tr>
      <td>
        <div class="inst-title">Universiti Teknikal Malaysia Melaka (UTeM)</div>
        <h1>Lampiran C: Template Jaminan Kualiti, Pemantauan dan Penambahbaikan Kualiti Berterusan (CQI)</h1>
        <div class="subtitle">Berdasarkan Garis Panduan Micro-Credential UTeM Versi 1.0 (Klausa 11.3–11.5, 17.1–17.12; Lampiran C)</div>
      </td>
      <td class="doc-badge">
        <span>LAMPIRAN C</span>
        <div style="margin-top:4px;font-size:9.5px;color:#475569">Ref: {{ $r->reference }}</div>
        <div style="font-size:9.5px;color:#475569">Tarikh: {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</div>
      </td>
    </tr>
  </table>

  {{-- C1. Maklumat Penawaran dan Tempoh Pemantauan --}}
  <div class="sec-title">C1. Maklumat Penawaran dan Tempoh Pemantauan</div>
  <table class="grid-form">
    <tr>
      <td class="label">Tajuk Micro-Credential</td>
      <td colspan="3"><strong>{{ $offering->name_en ?? $offering->name_ms }}</strong> (Versi {{ $offering->version_no ?? 1 }})</td>
    </tr>
    <tr>
      <td class="label">Kategori</td>
      <td style="width:28%">
        <span class="check-box {{ ($offering->category ?? '') === 'UMC' ? 'checked' : '' }}">{{ ($offering->category ?? '') === 'UMC' ? '✓' : '' }}</span> UMC &nbsp;
        <span class="check-box {{ ($offering->category ?? '') === 'SAMC' ? 'checked' : '' }}">{{ ($offering->category ?? '') === 'SAMC' ? '✓' : '' }}</span> SAMC &nbsp;
        <span class="check-box {{ ($offering->category ?? '') === 'MCE' ? 'checked' : '' }}">{{ ($offering->category ?? '') === 'MCE' ? '✓' : '' }}</span> MCE
      </td>
      <td class="label">PTj / Fakulti</td>
      <td>{{ $offering->ptj_name ?? $offering->ptj_code ?? '–' }}</td>
    </tr>
    <tr>
      <td class="label">Penyelaras</td>
      <td>{{ $offering->owner_name ?? '–' }}</td>
      <td class="label">Kohort / Sesi</td>
      <td>{{ $offering->cohort_name ?? '–' }}</td>
    </tr>
    <tr>
      <td class="label">Tempoh Pelaksanaan</td>
      <td>
        @if(!empty($offering->start_date) && !empty($offering->end_date))
          {{ \Carbon\Carbon::parse($offering->start_date)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($offering->end_date)->format('d/m/Y') }}
        @else
          –
        @endif
      </td>
      <td class="label">Tarikh Semakan CQI</td>
      <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <td class="label">Bilangan Peserta Berdaftar</td>
      <td><strong>{{ $enrolledCount ?? $ind['participants'] }}</strong> orang</td>
      <td class="label">Bilangan Peserta Menyempurnakan</td>
      <td><strong>{{ $completedCount ?? 0 }}</strong> orang</td>
    </tr>
  </table>

  {{-- C2. Ringkasan Prestasi Kohort --}}
  <div class="sec-title">C2. Ringkasan Prestasi Kohort</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:30%">Indikator</th>
        <th style="width:20%">Nilai / Dapatan</th>
        <th style="width:20%">Sasaran / Rujukan</th>
        <th style="width:15%">Status</th>
        <th style="width:15%">Catatan</th>
      </tr>
    </thead>
    <tbody>
      @foreach($ind['rows'] as $rx)
        <tr>
          <td><strong>{{ __($rx['label']) }}</strong></td>
          <td>
            @if($rx['key'] === 'access_issues')
              {{ $rx['value'] }}
            @elseif($rx['value'] === null)
              <span class="tag info">{{ __('Not recorded') }}</span>
            @else
              {{ $rx['value'] }}%
            @endif
          </td>
          <td>
            @if($rx['key'] === 'access_issues')
              {{ $rx['target'] }}
            @else
              {{ $rx['target'] }}%
            @endif
          </td>
          <td>
            @if($rx['value'] === null)
              <span class="tag info">{{ __('Not recorded') }}</span>
            @elseif($rx['below'])
              <span class="tag err">{{ $rx['key'] === 'access_issues' ? 'Ada' : 'Tidak Capai' }}</span>
            @else
              <span class="tag ok">{{ $rx['key'] === 'access_issues' ? 'Tiada' : 'Capai' }}</span>
            @endif
          </td>
          <td>{{ $rx['notes'] ?? '–' }}</td>
        </tr>
      @endforeach
      <tr>
        <td><strong>Purata / median pencapaian</strong></td>
        <td>Purata: {{ $ind['average'] !== null ? $ind['average'].'%' : 'n/a' }} &middot; Median: {{ $ind['median'] !== null ? $ind['median'].'%' : 'n/a' }}</td>
        <td>&ge; 50%</td>
        <td>
          @if($ind['average'] === null)
            <span class="tag info">{{ __('Not recorded') }}</span>
          @elseif($ind['average'] >= 50)
            <span class="tag ok">Capai</span>
          @else
            <span class="tag err">Tidak Capai</span>
          @endif
        </td>
        <td>Markah pentaksiran disahkan</td>
      </tr>
    </tbody>
  </table>

  {{-- C3. Analisis Pencapaian Hasil Pembelajaran --}}
  <div class="sec-title">C3. Analisis Pencapaian Hasil Pembelajaran</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:6%">Bil.</th>
        <th style="width:10%">Kod</th>
        <th style="width:40%">Hasil Pembelajaran</th>
        <th style="width:20%">Kaedah Pentaksiran / Evidens</th>
        <th style="width:12%">Pencapaian (%)</th>
        <th style="width:12%">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($ind['outcomes'] as $idx => $lo)
        <tr>
          <td>{{ $idx + 1 }}</td>
          <td><strong>{{ $lo['code'] }}</strong></td>
          <td>{{ $lo['text_ms'] ?? $lo['text'] }}</td>
          <td>{{ $lo['assessment_method'] ?? 'Pentaksiran Kursus' }}</td>
          <td><strong>{{ $lo['share'] !== null ? $lo['share'].'%' : '–' }}</strong></td>
          <td>
            @if($lo['share'] === null)
              <span class="tag info">n/a</span>
            @elseif($lo['share'] >= 50)
              <span class="tag ok">Memuaskan</span>
            @else
              <span class="tag err">Perlu CQI</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:#475569">Tiada pemetaan hasil pembelajaran.</td></tr>
      @endforelse
    </tbody>
  </table>
  <div style="border:1px solid #8d8d8d;padding:6px 8px;margin-bottom:12px;background:#fbfbfb">
    <div style="font-weight:600;font-size:10px;text-transform:uppercase;margin-bottom:2px">Rumusan Pencapaian Hasil Pembelajaran:</div>
    <div style="font-size:10.5px">{{ $r->c3_summary ?? 'Pencapaian hasil pembelajaran dinilai memuaskan berasaskan evidens pentaksiran verified. Sebarang jurang pencapaian dibawa ke pelan tindakan CQI.' }}</div>
  </div>

  {{-- C4. Maklum Balas Peserta dan Pihak Berkepentingan --}}
  <div class="sec-title">C4. Maklum Balas Peserta dan Pihak Berkepentingan</div>
  @php($c4Data = json_decode($r->c4_feedback ?? '[]', true) ?: [])
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:20%">Sumber Maklum Balas</th>
        <th style="width:22%">Aspek Dinilai</th>
        <th style="width:23%">Dapatan Utama</th>
        <th style="width:20%">Isu / Kekuatan</th>
        <th style="width:15%">Tindakan Awal</th>
      </tr>
    </thead>
    <tbody>
      @foreach(config('monitoring.feedback_sources') as $srcKey => $srcDef)
        @php($rowVal = $c4Data[$srcKey] ?? [])
        <tr>
          <td><strong>{{ $srcDef['label'] }}</strong></td>
          <td>{{ $srcDef['aspect'] }}</td>
          <td>
            @if($srcKey === 'learners' && $ind['feedback']['count'] > 0)
              Skor purata: {{ round(collect($ind['feedback']['areas'])->filter()->avg(), 1) }} / 5.0 ({{ $ind['feedback']['count'] }} respons)
              @if(!empty($rowVal['findings'])) &middot; {{ $rowVal['findings'] }} @endif
            @else
              {{ $rowVal['findings'] ?? 'Maklum balas dikumpul dan direkodkan mengikut kohort.' }}
            @endif
          </td>
          <td>{{ $rowVal['issues_strengths'] ?? 'Tahap penglibatan dan penerimaan positif.' }}</td>
          <td>{{ $rowVal['initial_action'] ?? 'Teruskan pemantauan berkala.' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- C5. Pemantauan Kualiti Pelaksanaan --}}
  <div class="sec-title">C5. Pemantauan Kualiti Pelaksanaan (8 Domain)</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:5%">Bil.</th>
        <th style="width:40%">Domain</th>
        <th style="width:20%">Status</th>
        <th style="width:35%">Evidens / Rujukan</th>
      </tr>
    </thead>
    <tbody>
      @php($domIndex = 1)
      @foreach(config('monitoring.domains') as $k => $label)
        @php($dc = $domains[$k] ?? null)
        <tr>
          <td>{{ $domIndex++ }}</td>
          <td><strong>{{ $label }}</strong></td>
          <td>
            @if($dc)
              @if($dc->status === 'tb')
                <span class="tag info">Tidak Berkaitan (TB)</span>
              @elseif($dc->status === 'memuaskan' || $dc->met)
                <span class="tag ok">Memuaskan</span>
              @else
                <span class="tag err">Perlu Tindakan</span>
              @endif
            @else
              <span class="tag info">Belum Disemak</span>
            @endif
          </td>
          <td>{{ $dc->finding ?? '–' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="page-break"></div>

  {{-- C6. Isu, Analisis Punca dan Tahap Keutamaan --}}
  <div class="sec-title">C6. Isu, Analisis Punca dan Tahap Keutamaan</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:5%">Bil.</th>
        <th style="width:25%">Isu / Jurang</th>
        <th style="width:25%">Evidens</th>
        <th style="width:25%">Punca Utama</th>
        <th style="width:10%">Impak</th>
        <th style="width:10%">Keutamaan</th>
      </tr>
    </thead>
    <tbody>
      @forelse($issues as $idx => $i)
        <tr>
          <td>{{ $idx + 1 }}</td>
          <td><strong>{{ $i->title ?? $i->evidence }}</strong></td>
          <td>{{ $i->evidence }}</td>
          <td>{{ $i->root_cause }}</td>
          <td>{{ $i->impact }}</td>
          <td>
            <span class="tag {{ $i->priority === 'high' ? 'err' : ($i->priority === 'medium' ? 'warn' : 'info') }}">
              {{ ucfirst($i->priority) }}
            </span>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;color:#475569">Tiada isu didaftarkan.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- C7. Pelan Tindakan CQI --}}
  <div class="sec-title">C7. Pelan Tindakan CQI</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:5%">Bil.</th>
        <th style="width:35%">Tindakan Penambahbaikan</th>
        <th style="width:20%">Pemilik Tindakan</th>
        <th style="width:12%">Tarikh Siap</th>
        <th style="width:18%">Indikator Kejayaan</th>
        <th style="width:10%">Status</th>
      </tr>
    </thead>
    <tbody>
      @php($actionCounter = 1)
      @forelse($issues as $i)
        @foreach($actions[$i->id] ?? [] as $a)
          <tr>
            <td>{{ $actionCounter++ }}</td>
            <td>
              <strong>{{ $a->description }}</strong>
              @if($a->affects)
                <div style="font-size:9.5px;color:#ba1b23;margin-top:2px">Perubahan Komponen: {{ config('proposals.change_triggers')[$a->affects] ?? $a->affects }}</div>
              @endif
            </td>
            <td>{{ $a->responsible }}</td>
            <td>{{ \Carbon\Carbon::parse($a->due_on)->format('d/m/Y') }}</td>
            <td>{{ $a->success_indicator }}</td>
            <td>
              <span class="tag {{ in_array($a->status, ['done', 'closed']) ? 'ok' : ($a->status === 'in_progress' ? 'warn' : 'info') }}">
                {{ __(config('monitoring.action_status')[$a->status] ?? $a->status) }}
              </span>
            </td>
          </tr>
        @endforeach
      @empty
        <tr><td colspan="6" style="text-align:center;color:#475569">Tiada tindakan CQI direkodkan.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- C8. Penilaian Keberkesanan CQI --}}
  <div class="sec-title">C8. Penilaian Keberkesanan CQI</div>
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:5%">Bil.</th>
        <th style="width:25%">Tindakan CQI</th>
        <th style="width:20%">Bukti Selepas Pelaksanaan</th>
        <th style="width:20%">Perbandingan Sebelum / Selepas</th>
        <th style="width:15%">Keputusan</th>
        <th style="width:15%">Tindakan Seterusnya</th>
      </tr>
    </thead>
    <tbody>
      @php($evalCounter = 1)
      @forelse($issues as $i)
        @foreach($actions[$i->id] ?? [] as $a)
          @if($a->effectiveness || $a->status === 'closed' || $a->status === 'carried')
            <tr>
              <td>{{ $evalCounter++ }}</td>
              <td><strong>{{ $a->description }}</strong></td>
              <td>{{ $a->after_evidence ?? '–' }}</td>
              <td>Sebelum: {{ $a->before_evidence ?? '–' }}<br>Selepas: {{ $a->after_evidence ?? '–' }}</td>
              <td>
                <span class="tag {{ $a->effectiveness === 'effective' ? 'ok' : ($a->effectiveness === 'partly' ? 'warn' : 'err') }}">
                  {{ __(config('monitoring.effectiveness')[$a->effectiveness] ?? $a->effectiveness ?? 'Dinilai') }}
                </span>
              </td>
              <td>{{ $a->effectiveness === 'not_effective' ? 'Dibawa ke kitaran CQI seterusnya' : 'Tindakan selesai' }}</td>
            </tr>
          @endif
        @endforeach
      @empty
        <tr><td colspan="6" style="text-align:center;color:#475569">Tiada penilaian keberkesanan tindakan direkodkan lagi.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- C9. Keputusan Semakan Penawaran --}}
  <div class="sec-title">C9. Keputusan Semakan Penawaran</div>
  @php($c9Decision = $r->c9_decision ?? 'continue_no_change')
  @php($affectedComps = json_decode($r->c9_affected_components ?? '[]', true) ?: [])
  <table class="grid-form">
    <tr>
      <td class="label">Keputusan Semakan</td>
      <td colspan="3">
        @foreach(config('monitoring.c9_decisions') as $dKey => $dLabel)
          <div style="margin-bottom:3px">
            <span class="check-box {{ $c9Decision === $dKey ? 'checked' : '' }}">{{ $c9Decision === $dKey ? '✓' : '' }}</span>
            <span style="{{ $c9Decision === $dKey ? 'font-weight:bold' : '' }}">{{ $dLabel }}</span>
          </div>
        @endforeach
      </td>
    </tr>
    <tr>
      <td class="label">Perubahan Signifikan?</td>
      <td colspan="3">
        <span class="check-box {{ $r->c9_is_material_change ? 'checked' : '' }}">{{ $r->c9_is_material_change ? '✓' : '' }}</span> <strong>Ya</strong> &nbsp;&nbsp;&nbsp;&nbsp;
        <span class="check-box {{ ! $r->c9_is_material_change ? 'checked' : '' }}">{{ ! $r->c9_is_material_change ? '✓' : '' }}</span> <strong>Tidak</strong>
      </td>
    </tr>
    @if($r->c9_is_material_change)
    <tr>
      <td class="label">Komponen Terjejas</td>
      <td colspan="3">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px">
          @foreach(config('monitoring.c9_affected_components') as $cKey => $cLabel)
            <div>
              <span class="check-box {{ in_array($cKey, $affectedComps, true) ? 'checked' : '' }}">{{ in_array($cKey, $affectedComps, true) ? '✓' : '' }}</span>
              <span>{{ $cLabel }}</span>
            </div>
          @endforeach
        </div>
      </td>
    </tr>
    <tr>
      <td class="label">Implikasi Peserta Sedia Ada</td>
      <td colspan="3">{{ $r->c9_learner_implications ?? 'Pelan peralihan kohort akan disediakan sekiranya berlaku pindaan kurikulum teras.' }}</td>
    </tr>
    @endif
    <tr>
      <td class="label">Tindakan Susulan</td>
      <td colspan="3">{{ $r->c9_follow_up_actions ?? 'Laksanakan pelan tindakan CQI mengikut jadual.' }}</td>
    </tr>
  </table>

  {{-- C10. Pengesahan dan Rekod CQI --}}
  <div class="sec-title">C10. Pengesahan dan Rekod CQI (3 Peranan)</div>
  @php($c10Signoffs = json_decode($r->c10_signoffs ?? '[]', true) ?: [])
  <table class="data-table">
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
      {{-- Role 1: Penyelaras Micro-Credential (P1) --}}
      @php($s1 = $c10Signoffs['coordinator'] ?? null)
      <tr>
        <td><strong>Penyelaras Micro-Credential</strong><div style="font-size:9.5px;color:#475569">(P1 Coordinator)</div></td>
        <td>
          <strong>{{ $s1['name'] ?? $offering->owner_name ?? '–' }}</strong><br>
          <span style="font-size:9.5px;color:#475569">{{ $s1['position'] ?? 'Penyelaras Kursus' }}</span>
        </td>
        <td>
          @if($r->status === 'submitted' || $r->status === 'confirmed' || !empty($s1['signed_at']))
            <span class="tag ok">Disahkan</span>
          @else
            <span class="tag info">Draf</span>
          @endif
        </td>
        <td>{{ !empty($s1['signed_at']) ? \Carbon\Carbon::parse($s1['signed_at'])->format('d/m/Y') : ($r->submitted_at ? \Carbon\Carbon::parse($r->submitted_at)->format('d/m/Y') : '–') }}</td>
        <td>{{ $s1['remarks'] ?? 'Laporan disediakan dari evidens kohort' }}</td>
      </tr>

      {{-- Role 2: Wakil PTj / Fakulti (P2) --}}
      @php($s2 = $c10Signoffs['ptj_rep'] ?? null)
      @php($confirmer = $r->confirmed_by ? \App\Models\User::find($r->confirmed_by) : null)
      <tr>
        <td><strong>Wakil PTj / Fakulti</strong><div style="font-size:9.5px;color:#475569">(P2 Dean / Deputy Dean)</div></td>
        <td>
          <strong>{{ $s2['name'] ?? ($confirmer ? $confirmer->name : '–') }}</strong><br>
          <span style="font-size:9.5px;color:#475569">{{ $s2['position'] ?? ($confirmer ? ($confirmer->position ?? 'Timbalan Dekan (Akademik)') : 'Timbalan Dekan (Akademik)') }}</span>
        </td>
        <td>
          @if($r->status === 'confirmed' || !empty($s2['signed_at']))
            <span class="tag ok">Disahkan</span>
          @else
            <span class="tag info">Menunggu Pengesahan</span>
          @endif
        </td>
        <td>{{ !empty($s2['signed_at']) ? \Carbon\Carbon::parse($s2['signed_at'])->format('d/m/Y') : ($r->confirmed_at ? \Carbon\Carbon::parse($r->confirmed_at)->format('d/m/Y') : '–') }}</td>
        <td>{{ $s2['remarks'] ?? 'Semakan dan kelulusan peringkat fakulti' }}</td>
      </tr>

      {{-- Role 3: Pihak Jaminan Kualiti / Berkuasa jika berkenaan (P4) --}}
      @php($s3 = $c10Signoffs['qa_authority'] ?? null)
      <tr>
        <td><strong>Pihak Jaminan Kualiti / Berkuasa</strong><div style="font-size:9.5px;color:#475569">(P4 QA Committee / CODL)</div></td>
        <td>
          <strong>{{ $s3['name'] ?? ($r->status === 'confirmed' ? 'Jawatankuasa Jaminan Kualiti' : '–') }}</strong><br>
          <span style="font-size:9.5px;color:#475569">{{ $s3['position'] ?? 'Pegawai Jaminan Kualiti' }}</span>
        </td>
        <td>
          @if(!empty($s3['status']) && $s3['status'] === 'tb')
            <span class="tag info">TB</span>
          @elseif($r->status === 'confirmed' || !empty($s3['signed_at']))
            <span class="tag ok">Disahkan</span>
          @else
            <span class="tag info">{{ $offering->category === 'SAMC' ? 'Menunggu QA' : 'TB' }}</span>
          @endif
        </td>
        <td>{{ !empty($s3['signed_at']) ? \Carbon\Carbon::parse($s3['signed_at'])->format('d/m/Y') : ($r->confirmed_at ? \Carbon\Carbon::parse($r->confirmed_at)->format('d/m/Y') : '–') }}</td>
        <td>{{ $s3['remarks'] ?? ($offering->category === 'SAMC' ? 'Pematuhan audit dan QV' : 'Tidak berkaitan bagi kohort standard') }}</td>
      </tr>
    </tbody>
  </table>

  {{-- NOTA PENTING LAMPIRAN C --}}
  <div class="nota-box">
    <b>NOTA PENTING (Lampiran C):</b><br>
    CQI hendaklah berasaskan evidens dan tidak terhad kepada skor kepuasan peserta. Analisis hendaklah mengambil kira pencapaian hasil pembelajaran, prestasi pentaksiran, data penyertaan/penyempurnaan, isu pelaksanaan dan maklum balas pihak berkepentingan. Perubahan signifikan yang terhasil daripada CQI hendaklah melalui mekanisme semakan, kelulusan atau Quality Verification semula yang berkaitan sebelum dilaksanakan.
  </div>

</body>
</html>
