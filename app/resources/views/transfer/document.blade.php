@extends('layouts.print')
@section('title', $filename.' - '.__('Submitted Evidence'))
@section('doc_title', __('Credit Transfer Evidence Summary'))
@section('doc_bm', __('Ringkasan Bukti Pemindahan Kredit'))
@section('doc_ref', $a->reference)
@section('doc_file', $filename)

@section('multi_sheet')
@php
  $fmtNum = fn ($v) => mcms_num($v);
  $lower = strtolower($filename);
  $docChecklist = json_decode((string) ($a->document_checklist ?? ''), true) ?: [];
  $checkedItems = $docChecklist['items'] ?? [];
  $hasOther = !empty($docChecklist['other']) || !empty($docChecklist['notes']) || in_array('other', $checkedItems, true);

  $isAcr = str_contains($lower, 'acr') || str_contains($lower, 'credit record');
  $isCred = str_contains($lower, 'credential') || str_contains($lower, 'certificate');
  $isLo = str_contains($lower, 'outcome');
  $isContent = str_contains($lower, 'content') || str_contains($lower, 'outline') || str_contains($lower, 'syllabus');
  $isSlt = str_contains($lower, 'slt') || str_contains($lower, 'learning time');
  $isAssess = str_contains($lower, 'assessment');
  $isQv = str_contains($lower, 'qv') || str_contains($lower, 'quality') || str_contains($lower, 'accreditation');
  $isOther = str_contains($lower, 'other') || str_contains($lower, 'supporting link') || str_contains($lower, 'notes');
  $isAll = ! ($isAcr || $isCred || $isLo || $isContent || $isSlt || $isAssess || $isQv || $isOther);

  $totalRequested = (float) $lines->sum('credits_requested');

  // Build ordered list of documents to render.
  // For Complete Evidence Pack ($isAll), combine every ticked document as-is with its own header & footer.
  $docsToRender = [];
  if ($isAll) {
      $allKeys = !empty($checkedItems)
          ? $checkedItems
          : array_values(array_filter(['acr', 'credential', 'learning_outcomes', 'content', 'slt', 'assessment', 'qv', $hasOther ? 'other' : null]));

      $orderedCatalog = [
          'acr' => __('Academic Credit Record (ACR)'),
          'credential' => __('Micro-Credential Certificate'),
          'learning_outcomes' => __('Learning Outcomes Statement'),
          'content' => __('Module Content Outline'),
          'slt' => __('Student Learning Time (SLT) Breakdown'),
          'assessment' => __('Assessment Method and Evidence'),
          'qv' => __('Quality Verification Proof'),
          'other' => __('Other Supporting Documents'),
      ];
      foreach ($orderedCatalog as $key => $title) {
          if (in_array($key, $allKeys, true)) {
              $docsToRender[] = ['type' => $key, 'title' => $title];
          }
      }
  } else {
      if ($isAcr) { $docsToRender[] = ['type' => 'acr', 'title' => $filename]; }
      elseif ($isCred) { $docsToRender[] = ['type' => 'credential', 'title' => $filename]; }
      elseif ($isLo) { $docsToRender[] = ['type' => 'learning_outcomes', 'title' => $filename]; }
      elseif ($isContent) { $docsToRender[] = ['type' => 'content', 'title' => $filename]; }
      elseif ($isSlt) { $docsToRender[] = ['type' => 'slt', 'title' => $filename]; }
      elseif ($isAssess) { $docsToRender[] = ['type' => 'assessment', 'title' => $filename]; }
      elseif ($isQv) { $docsToRender[] = ['type' => 'qv', 'title' => $filename]; }
      elseif ($isOther) { $docsToRender[] = ['type' => 'other', 'title' => $filename]; }
  }

  // Expand documents into individual A4 page sheets so each sheet is cleanly cut on screen & in print.
  $sheets = [];
  foreach ($docsToRender as $doc) {
      if ($doc['type'] === 'credential') {
          $chunks = $credentials->isNotEmpty() ? $credentials->chunk(2) : collect([collect()]);
          foreach ($chunks as $pageIdx => $chunk) {
              $sheets[] = [
                  'type' => 'credential',
                  'title' => $doc['title'],
                  'first_page' => $pageIdx === 0,
                  'page_no' => $pageIdx + 1,
                  'page_total' => $chunks->count(),
                  'items' => $chunk,
              ];
          }
      } elseif (in_array($doc['type'], ['learning_outcomes', 'content', 'slt', 'assessment'], true)) {
          $chunks = $lines->isNotEmpty() ? $lines->chunk(4) : collect([collect()]);
          foreach ($chunks as $pageIdx => $chunk) {
              $sheets[] = [
                  'type' => $doc['type'],
                  'title' => $doc['title'],
                  'first_page' => $pageIdx === 0,
                  'page_no' => $pageIdx + 1,
                  'page_total' => $chunks->count(),
                  'items' => $chunk,
              ];
          }
      } else {
          $sheets[] = [
              'type' => $doc['type'],
              'title' => $doc['title'],
              'first_page' => true,
              'page_no' => 1,
              'page_total' => 1,
              'items' => collect(),
          ];
      }
  }
@endphp

<style>
  @page { size: A4; margin: 14mm 14mm; }
  .a4-sheet {
    position: relative;
    background: #ffffff !important;
    color: #090a0f !important;
    width: 210mm;
    max-width: 100%;
    min-height: 270mm;
    margin: 0 auto 24px !important;
    padding: 20px 26px 26px !important;
    border: 1px solid #cbd5e1 !important;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.12) !important;
    box-sizing: border-box !important;
    display: flex;
    flex-direction: column;
  }
  .a4-sheet:last-child { margin-bottom: 0 !important; }
  .a4-sheet-body { flex: 1; }
  .a4-sheet .doc-foot {
    position: static !important;
    margin-top: 18px !important;
    padding-top: 8px !important;
  }
  @media print {
    html, body, .doc-wrap {
      background: #ffffff !important;
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      overflow: visible !important;
    }
    .a4.a4-sheet {
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      min-height: 0 !important;
      margin: 0 !important;
      padding: 0 !important;
      border: 0 !important;
      box-shadow: none !important;
      box-sizing: border-box !important;
      overflow: visible !important;
      display: block !important;
    }
    .a4.a4-sheet + .a4.a4-sheet {
      page-break-before: always !important;
      break-before: page !important;
    }
    .mc-subcard {
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .a4-sheet .doc-foot {
      position: static !important;
      margin-top: 16px !important;
    }
  }
  .applicant-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    border: 1px solid #090a0f;
    margin-bottom: 14px;
    background: #ffffff;
  }
  .applicant-cell {
    padding: 8px 11px;
    border-bottom: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
  }
  .applicant-cell:nth-child(2n) { border-right: 0; }
  .applicant-cell:nth-last-child(-n+2) { border-bottom: 0; }
  .applicant-lbl {
    font-size: 7.5pt;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #475569;
    margin-bottom: 2px;
  }
  .applicant-val {
    font-size: 9.5pt;
    font-weight: 700;
    color: #090a0f;
  }
  .applicant-sub {
    font-size: 8pt;
    color: #475569;
    margin-top: 2px;
  }
  .mc-subcard {
    border: 1px solid #090a0f;
    margin-bottom: 11px;
    background: #ffffff;
  }
  .mc-subcard-hd {
    padding: 6px 10px;
    background: #f8fafc;
    border-bottom: 1px solid #090a0f;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    font-size: 9pt;
    font-weight: 700;
    color: #090a0f;
  }
</style>

@foreach($sheets as $sheetIdx => $sheet)
  <div class="a4 a4-sheet">
    {{-- Standard UTeM Letterhead (Identical on every document) --}}
    <div class="doc-head">
      <div>
        <b style="font-size:16pt">UTeM</b>
        <div style="font-size:9pt">Universiti Teknikal Malaysia Melaka</div>
        <div class="muted" style="font-size:8pt">{{ __('Centre for Open and Distance Learning (CODL)') }}</div>
      </div>
      <div style="margin-left:auto;text-align:right">
        <b>{{ app()->getLocale() === 'ms' ? __('Ringkasan Bukti Pemindahan Kredit') : __('Credit Transfer Evidence Summary') }}</b>
        <div class="muted" style="font-size:9pt">{{ $a->reference }} &middot; {{ now()->format('d/m/Y') }}</div>
      </div>
    </div>

    <div class="a4-sheet-body">
      {{-- Document Title Header --}}
      <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <h1 style="font-size:14pt;font-weight:700;margin:0;color:#090a0f">
          {{ $sheet['title'] }}
          @if($sheet['page_total'] > 1)
            <span style="font-size:9pt;font-weight:500;color:#475569">({{ $sheet['page_no'] }} / {{ $sheet['page_total'] }})</span>
          @endif
        </h1>
        <span class="tag" style="background:#0052ff;color:#fff;font-size:9px;font-weight:700;padding:3px 8px;border-radius:3px">{{ __('VERIFIED MCMS RECORD') }}</span>
      </div>

      {{-- Structured Applicant & Application Metadata Grid (First page of each document) --}}
      @if($sheet['first_page'])
        <div class="applicant-grid">
          <div class="applicant-cell">
            <div class="applicant-lbl">{{ __('Applicant') }}</div>
            <div class="applicant-val">{{ $a->user_name }}</div>
            <div class="applicant-sub">{{ __('Matric / ID:') }} <span style="font-family:var(--mono)">{{ $a->institution_id ?? $a->user_email }}</span></div>
          </div>
          <div class="applicant-cell">
            <div class="applicant-lbl">{{ __('Target Programme') }}</div>
            <div class="applicant-val">{{ $a->programme }} &ndash; {{ $a->programme_name }}</div>
            <div class="applicant-sub">{{ $ptj?->name ?? 'Universiti Teknikal Malaysia Melaka' }}</div>
          </div>
          <div class="applicant-cell">
            <div class="applicant-lbl">{{ __('Application Reference') }}</div>
            <div class="applicant-val" style="font-family:var(--mono)">{{ $a->reference }}</div>
            <div class="applicant-sub">{{ __('Status:') }} {{ ucfirst(str_replace('_', ' ', (string) $a->status)) }}</div>
          </div>
          <div class="applicant-cell">
            <div class="applicant-lbl">{{ __('Credits Requested') }}</div>
            <div class="applicant-val">{{ $fmtNum($totalRequested) }} {{ __('credits') }} ({{ $lines->count() }} {{ $lines->count() === 1 ? __('course') : __('courses') }})</div>
            <div class="applicant-sub">{{ __('Generated:') }} {{ now()->format('d/m/Y') }}</div>
          </div>
        </div>
      @endif

      {{-- 1. Academic Credit Record (ACR) --}}
      @if($sheet['type'] === 'acr')
        <div class="print-sec" style="margin-bottom:14px">
          <div class="print-sec-title"><span>{{ __('Academic Credit Records (ACR) & ABC Deposits') }}</span></div>
          <table data-plain>
            <thead>
              <tr>
                <th>{{ __('ACR Ref') }}</th>
                <th>{{ __('Micro-Credential') }}</th>
                <th>{{ __('ABC Ref') }}</th>
                <th class="num">{{ __('Credits') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Valid Until') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($acrs as $ac)
                <tr>
                  <td style="font-family:var(--mono);white-space:nowrap">{{ $ac->reference }}</td>
                  <td><strong>{{ $ac->name_en }}</strong></td>
                  <td style="font-family:var(--mono);white-space:nowrap">{{ $ac->abc_reference ?? '—' }}</td>
                  <td class="num">{{ $fmtNum($ac->credits) }}</td>
                  <td>{{ ucfirst((string) ($ac->credit_status ?? $ac->status)) }}</td>
                  <td style="white-space:nowrap">{{ $ac->credit_expiry ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="muted">{{ __('No ACR records attached to this application.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

      {{-- 2. Issued Micro-Credential Certificates (Rendered Directly Inside Document, 2 per page) --}}
      @elseif($sheet['type'] === 'credential')
        <div class="print-sec" style="margin-bottom:10px">
          <div class="print-sec-title"><span>{{ __('Issued Micro-Credential Certificates') }}</span></div>
          @forelse($sheet['items'] as $cr)
            @php
              $cData = json_decode((string) ($cr->content ?? ''), true) ?: [];
              $lang = app()->getLocale() === 'ms' ? 'ms' : 'en';
              $issuer = $cData['issuer'] ?? 'Universiti Teknikal Malaysia Melaka';
              $holder = $cData['holder'] ?? strtoupper((string) $a->user_name);
              $mcTitle = $cData['name'][$lang] ?? $cr->name_en;
              $cat = $cData['category'] ?? 'UMC';
              $crVal = $cData['claims']['credit'] ?? null;
              $mqfVal = $cData['claims']['mqf'] ?? null;
              $sltVal = $cData['slt_hours'] ?? null;
              $signatory = $cData['signatory'] ?? ($coordinator->name ?? 'Deputy Dean (Academic)');
              $sigPos = $cData['signatory_position'] ?? 'Deputy Dean (Academic)';
              $issuedDate = $cr->issued_at ? \Carbon\Carbon::parse($cr->issued_at)->format('d/m/Y') : now()->format('d/m/Y');
            @endphp
            <div class="mc-subcard" style="border:2px solid #090a0f;padding:15px 20px;margin-bottom:14px;text-align:center;background:#ffffff">
              <div style="letter-spacing:1.8px;font-size:8pt;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:4px">
                {{ strtoupper($issuer) }} &middot; {{ strtoupper($ptj->code ?? 'UTeM') }}
              </div>
              <div style="font-size:13pt;font-weight:700;color:#090a0f;margin-bottom:8px;letter-spacing:.5px">
                {{ $lang === 'ms' ? 'SIJIL MIKRO-KREDENSIAL' : 'MICRO-CREDENTIAL CERTIFICATE' }}
              </div>

              @if(!empty($cr->badge_image_path))
                <div style="margin:6px auto 10px">
                  <img src="{{ asset($cr->badge_image_path) }}" alt="Digital Badge" style="width:52px;height:52px;object-fit:contain;border:1px solid #e2e8f0;padding:3px;border-radius:4px">
                </div>
              @endif

              <div style="font-size:8.5pt;color:#475569;margin-bottom:4px">
                {{ $lang === 'ms' ? 'Dengan ini disahkan bahawa' : 'This certifies that' }}
              </div>
              <div style="font-size:15pt;font-weight:700;color:#090a0f;margin-bottom:4px">
                {{ $holder }}
              </div>
              <div style="font-size:8.5pt;color:#475569;margin-bottom:4px">
                {{ $lang === 'ms' ? 'telah menamatkan dengan jayanya' : 'has successfully completed' }}
              </div>
              <div style="font-size:12pt;font-weight:700;color:#0b1a3d;margin-bottom:6px">
                {{ $mcTitle }} <span style="font-weight:500;color:#475569">({{ $cat }})</span>
              </div>

              <div style="font-size:8.5pt;color:#090a0f;margin-bottom:10px">
                @if($crVal !== null) <strong>{{ __('Credit:') }}</strong> {{ $crVal }} @endif
                @if($mqfVal) &middot; <strong>MQF {{ $mqfVal }}</strong> @endif
                @if($sltVal) &middot; <strong>{{ __('SLT:') }}</strong> {{ $sltVal }} {{ __('hours') }} @endif
                &middot; <strong>{{ __('Date Awarded:') }}</strong> {{ $issuedDate }}
              </div>

              <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:12px;padding-top:10px;border-top:1px solid #e2e8f0;text-align:left;font-size:8pt;gap:16px">
                <div>
                  <div style="font-weight:700;color:#090a0f">{{ $signatory }}</div>
                  <div style="color:#475569">{{ $sigPos }} &middot; {{ $ptj->name ?? 'UTeM' }}</div>
                </div>
                <div style="text-align:right;font-family:var(--mono);font-size:7.8pt;color:#090a0f">
                  <div><strong>{{ __('Cert No:') }}</strong> {{ $cr->number }}</div>
                  <div><strong>{{ __('Verify Code:') }}</strong> {{ $cr->public_code }} &middot; <strong>{{ __('Status:') }}</strong> {{ ucfirst((string) ($cr->status ?? 'issued')) }}</div>
                </div>
              </div>
            </div>
          @empty
            <div style="border:1px solid #d0d4dc;padding:12px;font-size:8.5pt;color:#475569">
              {{ __('Verified directly via completed MCMS achievement records and ACRs.') }}
            </div>
          @endforelse
        </div>

      {{-- 3. Learning Outcomes Statement --}}
      @elseif($sheet['type'] === 'learning_outcomes')
        <div class="print-sec" style="margin-bottom:12px">
          <div class="print-sec-title"><span>{{ __('Micro-Credential Learning Outcomes & Target Courses') }}</span></div>
          @foreach($sheet['items'] as $l)
            @php($loList = $outcomes->get($l->mc_version_id, collect()))
            <div class="mc-subcard">
              <div class="mc-subcard-hd">
                <span>{{ $l->source_name }}</span>
                <span style="font-family:var(--mono);font-size:8pt;color:#475569">&rarr; {{ $l->course_code }} {{ $l->course_name }} ({{ $fmtNum($l->credits_requested) }} cr)</span>
              </div>
              <div style="padding:8px 12px;font-size:8.5pt">
                @if($loList->isNotEmpty())
                  <ul style="margin:0;padding-left:16px">
                    @foreach($loList as $lo)
                      <li style="margin-bottom:3px"><b>{{ $lo->code }}:</b> {{ $lo->text_en }}</li>
                    @endforeach
                  </ul>
                @else
                  <span class="muted">{{ __('Learning outcomes verified against course curriculum record.') }}</span>
                @endif
              </div>
            </div>
          @endforeach
        </div>

      {{-- 4. Module Content Outline (Distinct from SLT Breakdown) --}}
      @elseif($sheet['type'] === 'content')
        <div class="print-sec" style="margin-bottom:12px">
          <div class="print-sec-title"><span>{{ __('Course & Module Content Outline') }}</span></div>
          @foreach($sheet['items'] as $l)
            @php($mcMods = $modules->get($l->mc_version_id, collect()))
            <div class="mc-subcard">
              <div class="mc-subcard-hd">
                <span>{{ $l->source_name }}</span>
                <span style="font-family:var(--mono);font-size:8pt;color:#475569">{{ $l->course_code }} {{ $l->course_name }}</span>
              </div>
              <table data-plain style="margin:0">
                <thead>
                  <tr>
                    <th style="width:55px">{{ __('Unit') }}</th>
                    <th>{{ __('Module / Syllabus Topic') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($mcMods as $idx => $m)
                    <tr>
                      <td>{{ __('Module') }} {{ $idx + 1 }}</td>
                      <td>{{ $m->name_en }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td>{{ __('Module') }} 1</td>
                      <td>{{ $l->source_name }} &ndash; {{ __('Core Curriculum Syllabus') }}</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          @endforeach
        </div>

      {{-- 5. Student Learning Time (SLT) Breakdown --}}
      @elseif($sheet['type'] === 'slt')
        <div class="print-sec" style="margin-bottom:12px">
          <div class="print-sec-title"><span>{{ __('Student Learning Time (SLT) & Credit Breakdown') }}</span></div>
          @foreach($sheet['items'] as $l)
            @php($mcMods = $modules->get($l->mc_version_id, collect()))
            @php($totSlt = $mcMods->sum('slt_hours'))
            <div class="mc-subcard">
              <div class="mc-subcard-hd">
                <span>{{ $l->source_name }}</span>
                <span style="font-size:8pt;color:#475569">{{ $l->course_code }} &middot; {{ $fmtNum($l->credits_requested) }} cr &middot; {{ $totSlt > 0 ? $totSlt.' hrs' : ($fmtNum($l->credits_requested * 40).' hrs') }}</span>
              </div>
              <table data-plain style="margin:0">
                <thead>
                  <tr>
                    <th style="width:45px">#</th>
                    <th>{{ __('Module / Learning Component') }}</th>
                    <th class="num" style="width:95px">{{ __('SLT Hours') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($mcMods as $idx => $m)
                    <tr>
                      <td>{{ $idx + 1 }}</td>
                      <td>{{ $m->name_en }}</td>
                      <td class="num">{{ $m->slt_hours }} hrs</td>
                    </tr>
                  @empty
                    <tr>
                      <td>1</td>
                      <td>{{ __('Total Planned Student Learning Time') }}</td>
                      <td class="num">{{ $fmtNum($l->credits_requested * 40) }} hrs</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          @endforeach
        </div>

      {{-- 6. Assessment Method and Evidence --}}
      @elseif($sheet['type'] === 'assessment')
        <div class="print-sec" style="margin-bottom:12px">
          <div class="print-sec-title"><span>{{ __('Assessment Components & Weighting') }}</span></div>
          @foreach($sheet['items'] as $l)
            @php($mcAssess = $assessments->get($l->mc_version_id, collect()))
            @php($totWeight = $mcAssess->sum('weight_percent'))
            <div class="mc-subcard">
              <div class="mc-subcard-hd">
                <span>{{ $l->source_name }}</span>
                <span style="font-size:8pt;color:#475569">{{ $l->course_code }} &middot; {{ __('Total:') }} {{ $totWeight > 0 ? $fmtNum($totWeight).'%' : '100%' }}</span>
              </div>
              <table data-plain style="margin:0">
                <thead>
                  <tr>
                    <th>{{ __('Assessment Component') }}</th>
                    <th class="num" style="width:100px">{{ __('Weight (%)') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($mcAssess as $ac)
                    <tr>
                      <td>{{ $ac->name_en }}</td>
                      <td class="num">{{ $fmtNum($ac->weight_percent) }}%</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="2" class="muted">{{ __('Continuous assessment and summative evaluation verified in MCMS achievement record.') }}</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          @endforeach
        </div>

      {{-- 7. Quality Verification (QV) Proof --}}
      @elseif($sheet['type'] === 'qv')
        <div class="print-sec" style="margin-bottom:12px">
          <div class="print-sec-title"><span>{{ __('Quality Verification (QV) & Accreditation Records') }}</span></div>
          <table data-plain>
            <thead>
              <tr>
                <th>{{ __('Micro-Credential') }}</th>
                <th>{{ __('QV Reference') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Valid Until') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($qvRecords as $q)
                <tr>
                  <td><strong>{{ $q->name_en }}</strong></td>
                  <td style="font-family:var(--mono)">{{ $q->qv_reference ?? $q->submitted_ref ?? '—' }}</td>
                  <td>{{ ucfirst((string) $q->status) }}</td>
                  <td>{{ $q->valid_until ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="muted">{{ __('UTeM accredited Unbundled Micro-Credential (UMC) — covered under programme accreditation.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

      {{-- 8. Other Supporting Documents & Notes --}}
      @elseif($sheet['type'] === 'other')
        <div class="print-sec" style="margin-bottom:12px">
          <div class="print-sec-title"><span>{{ __('External Supporting Documents & Applicant Notes') }}</span></div>
          <div style="border:1px solid #090a0f;padding:12px 14px;font-size:9pt;line-height:1.6">
            @if(!empty($docChecklist['other']))
              <div style="margin-bottom:8px">
                <strong>{{ __('Other Supporting Document:') }}</strong><br>
                <span style="font-family:var(--mono);word-break:break-all;color:#0b1a3d">{{ $docChecklist['other'] }}</span>
              </div>
            @endif
            @if(!empty($docChecklist['notes']))
              <div>
                <strong>{{ __('Applicant Notes:') }}</strong><br>
                <span>{{ $docChecklist['notes'] }}</span>
              </div>
            @endif
            @if(empty($docChecklist['other']) && empty($docChecklist['notes']))
              <span class="muted">{{ __('No additional external supporting links or notes recorded.') }}</span>
            @endif
          </div>
        </div>
      @endif
    </div>

    {{-- Official Document Footer (Identical on every page) --}}
    <div class="doc-foot">
      <span>{{ $a->reference }}</span>
      <span>{{ $sheet['title'] }}</span>
      <span>{{ __('Page') }} {{ $sheetIdx + 1 }} / {{ count($sheets) }}</span>
      <span>{{ __('Generated') }} {{ now()->format('d/m/Y H:i') }}</span>
    </div>
  </div>
@endforeach
@endsection
