@props([
  'a',
  'lines',
  'routeName' => 'transfers.document',
  'idPrefix' => 'ev',
])
@php
  $docChecklist = json_decode((string) ($a->document_checklist ?? ''), true) ?: null;
  $checkedDocs = $docChecklist['items'] ?? [];
  $otherText = trim((string) ($docChecklist['other'] ?? ''));
  $notesText = trim((string) ($docChecklist['notes'] ?? ''));
  $uploadedFiles = is_array($docChecklist['uploaded_files'] ?? null) ? array_values(array_filter($docChecklist['uploaded_files'])) : [];

  // Extract external URLs from other / notes / legacy documents
  $urls = [];
  $extractUrls = function (string $txt) use (&$urls) {
      if (preg_match_all('/https?:\/\/[^\s;]+/i', $txt, $m)) {
          foreach ($m[0] as $u) {
              $urls[] = rtrim($u, '.,;');
          }
      }
  };
  $extractUrls($otherText);
  $extractUrls($notesText);
  if (! $docChecklist && ! empty($a->documents)) {
      $extractUrls((string) $a->documents);
  }
  $urls = array_values(array_unique($urls));

  // Remaining non-URL text for Other / Notes
  $otherNonUrl = trim((string) preg_replace('/https?:\/\/[^\s;]+/i', '', $otherText));
  $notesNonUrl = trim((string) preg_replace('/https?:\/\/[^\s;]+/i', '', $notesText));
  if (! $docChecklist && ! empty($a->documents)) {
      // Legacy records without structured checklist: keep non-URL, non-PDF text as notes
      $legacyParts = array_filter(array_map('trim', preg_split('/[;\n\r]+/', (string) $a->documents)));
      $legacyPdfFiles = [];
      $legacyNotes = [];
      foreach ($legacyParts as $part) {
          if (preg_match('/^https?:\/\//i', $part)) { continue; }
          if (preg_match('/\.pdf$/i', $part)) {
              $legacyPdfFiles[] = $part;
          } else {
              $legacyNotes[] = $part;
          }
      }
      if ($legacyNotes !== [] && $notesNonUrl === '') {
          $notesNonUrl = implode('; ', $legacyNotes);
      }
  } else {
      $legacyPdfFiles = [];
  }

  // 1-to-1 system-backed MCMS evidence rows matching each ticked checklist item
  $mcmsPacks = [];
  if (in_array('acr', $checkedDocs, true) || (! $docChecklist && $lines->isNotEmpty())) {
      $mcmsPacks[] = [
          'key' => 'acr',
          'label' => __('Academic Credit Record (ACR)'),
          'sub' => $lines->count() === 1 ? __('1 credit record & ABC deposit') : __(':count credit records & ABC deposits', ['count' => $lines->count()]),
          'file' => 'Academic Credit Record (ACR)',
      ];
  }
  if (in_array('credential', $checkedDocs, true)) {
      $mcmsPacks[] = [
          'key' => 'credential',
          'label' => __('Micro-Credential Certificate or Credential'),
          'sub' => __('Issued digital credentials & achievement verification'),
          'file' => 'Micro-Credential Certificate',
      ];
  }
  if (in_array('learning_outcomes', $checkedDocs, true)) {
      $mcmsPacks[] = [
          'key' => 'lo',
          'label' => __('Learning Outcomes Statement'),
          'sub' => __('Mapped micro-credential learning outcomes'),
          'file' => 'Learning Outcomes Statement',
      ];
  }
  if (in_array('content', $checkedDocs, true)) {
      $mcmsPacks[] = [
          'key' => 'content',
          'label' => __('Course or Module Content Outline'),
          'sub' => __('Module syllabus & curriculum structure'),
          'file' => 'Module Content Outline',
      ];
  }
  if (in_array('slt', $checkedDocs, true)) {
      $mcmsPacks[] = [
          'key' => 'slt',
          'label' => __('Student Learning Time (SLT) & Credit Breakdown'),
          'sub' => __('Credit hours & SLT breakdown per micro-credential'),
          'file' => 'Student Learning Time (SLT) Breakdown',
      ];
  }
  if (in_array('assessment', $checkedDocs, true)) {
      $mcmsPacks[] = [
          'key' => 'assessment',
          'label' => __('Assessment Method & Evidence'),
          'sub' => __('Assessment components & weightings per micro-credential'),
          'file' => 'Assessment Method and Evidence',
      ];
  }
  if (in_array('qv', $checkedDocs, true)) {
      $mcmsPacks[] = [
          'key' => 'qv',
          'label' => __('Quality Verification (QV) or Accreditation Proof'),
          'sub' => __('Accreditation & quality verification records'),
          'file' => 'Quality Verification Proof',
      ];
  }
  foreach ($legacyPdfFiles as $pdfFile) {
      $mcmsPacks[] = [
          'key' => 'legacy-'.md5($pdfFile),
          'label' => $pdfFile,
          'sub' => __('Attached PDF document'),
          'file' => $pdfFile,
      ];
  }
@endphp

<div class="panel" style="margin-bottom:20px">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <h2 style="margin:0;font-size:15px">{{ __('Supporting Evidence Submitted') }}</h2>
    <div style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap">
      @if($docChecklist)
        <span class="tag info">{{ count($checkedDocs) }} / {{ count(config('transfer.documents_checklist')) }} {{ __('items included') }}</span>
      @endif
      @php
        $allPackUrl = route($routeName, [$a->id, 'Complete Evidence Pack']);
        $allPrintUrl = route($routeName, [$a->id, 'Complete Evidence Pack', 'print' => 1, 'autoprint' => 1]);
        $allModalId = $idPrefix.'-doc-modal-all';
      @endphp
      <button type="button" class="btn ter sm" data-modal="{{ $allModalId }}">{{ __('Preview All') }}</button>
      <a href="{{ $allPrintUrl }}" class="btn primary sm" target="_blank">{{ __('Print All (PDF)') }}</a>
    </div>
  </div>

  {{-- Complete Combined Evidence Pack Modal --}}
  <div class="scrim" id="modal-{{ $allModalId }}" hidden style="display:none;z-index:9999">
    <div class="modal wide" role="dialog" aria-modal="true" style="width:920px;max-width:95vw;height:86vh;max-height:86vh;display:flex;flex-direction:column;border-radius:8px;overflow:hidden">
      <div class="mh" style="padding:12px 18px;border-bottom:1px solid var(--color-line-hairline, #e2e8f0);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas-subtle, #f8fafc);gap:12px">
        <div style="display:flex;align-items:center;gap:8px;min-width:0">
          <span class="tag info" style="font-size:10.5px;font-weight:700">{{ __('ALL EVIDENCE') }}</span>
          <h3 style="margin:0;font-size:14px;font-weight:600">{{ __('Complete Evidence Pack') }} &middot; {{ $a->reference }}</h3>
        </div>
        <div style="display:inline-flex;align-items:center;gap:8px;flex-shrink:0">
          <a href="{{ $allPackUrl }}" class="btn ter sm" target="_blank">{{ __('Open in New Tab') }} &nearr;</a>
          <a href="{{ $allPrintUrl }}" class="btn primary sm" target="_blank">{{ __('Print / PDF') }}</a>
          <button type="button" class="btn ter sm" data-close style="padding:4px 10px" aria-label="{{ __('Close') }}">&times;</button>
        </div>
      </div>
      <div class="mb" style="flex:1;padding:0;overflow:hidden;background:#ffffff">
        <iframe src="{{ $allPackUrl }}?embed=1" style="width:100%;height:100%;border:0" title="{{ __('Complete Evidence Pack') }}"></iframe>
      </div>
      <div class="mf" style="padding:10px 18px;border-top:1px solid var(--color-line-hairline, #e2e8f0);display:flex;justify-content:space-between;align-items:center;background:var(--color-canvas-subtle, #f8fafc)">
        <span class="s muted">{{ __('All Verified MCMS Evidence') }} &middot; {{ $a->reference }}</span>
        <button type="button" class="btn ter sm" data-close style="flex:0 0 auto;width:auto;padding:6px 14px">{{ __('Close Preview') }}</button>
      </div>
    </div>
  </div>
  <div class="bd" style="padding:16px 20px;display:flex;flex-direction:column;gap:16px">

    {{-- 1. Verified MCMS System Records --}}
    @if(count($mcmsPacks) > 0)
      <div>
        <div class="s" style="font-weight:700;margin-bottom:8px;color:var(--color-ink, #090a0f)">{{ __('Verified MCMS Records') }}</div>
        <div style="display:flex;flex-direction:column;gap:8px">
          @foreach($mcmsPacks as $pack)
            @php
              $docUrl = route($routeName, [$a->id, $pack['file']]);
              $printUrl = route($routeName, [$a->id, $pack['file'], 'print' => 1, 'autoprint' => 1]);
              $modalId = $idPrefix.'-doc-modal-'.$loop->index;
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1px solid var(--color-line-hairline, #e2e8f0);background:var(--color-canvas-subtle, #f8fafc);border-radius:var(--radius-sm, 4px);gap:12px;flex-wrap:wrap">
              <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <span class="tag info" style="font-size:10.5px;font-weight:700">{{ __('MCMS') }}</span>
                <div>
                  <div style="font-size:13px;font-weight:600;color:var(--color-ink, #090a0f)">{{ $pack['label'] }}</div>
                  <div class="s muted" style="font-size:11.5px">{{ $pack['sub'] }}</div>
                </div>
              </div>
              <div style="display:inline-flex;align-items:center;gap:8px">
                <button type="button" class="btn ter sm" data-modal="{{ $modalId }}">{{ __('Preview') }}</button>
                <a href="{{ $printUrl }}" class="btn primary sm" target="_blank">{{ __('Print / PDF') }}</a>
              </div>
            </div>

            {{-- Clean Modal Viewer with Compact Close Preview Button --}}
            <div class="scrim" id="modal-{{ $modalId }}" hidden style="display:none;z-index:9999">
              <div class="modal wide" role="dialog" aria-modal="true" style="width:920px;max-width:95vw;height:86vh;max-height:86vh;display:flex;flex-direction:column;border-radius:8px;overflow:hidden">
                <div class="mh" style="padding:12px 18px;border-bottom:1px solid var(--color-line-hairline, #e2e8f0);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas-subtle, #f8fafc);gap:12px">
                  <div style="display:flex;align-items:center;gap:8px;min-width:0">
                    <span class="tag info" style="font-size:10.5px;font-weight:700">{{ __('MCMS') }}</span>
                    <h3 style="margin:0;font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $pack['label'] }}</h3>
                  </div>
                  <div style="display:inline-flex;align-items:center;gap:8px;flex-shrink:0">
                    <a href="{{ $docUrl }}" class="btn ter sm" target="_blank">{{ __('Open in New Tab') }} &nearr;</a>
                    <a href="{{ $printUrl }}" class="btn primary sm" target="_blank">{{ __('Print / PDF') }}</a>
                    <button type="button" class="btn ter sm" data-close style="padding:4px 10px" aria-label="{{ __('Close') }}">&times;</button>
                  </div>
                </div>
                <div class="mb" style="flex:1;padding:0;overflow:hidden;background:#ffffff">
                  <iframe src="{{ $docUrl }}?embed=1" style="width:100%;height:100%;border:0" title="{{ $pack['label'] }}"></iframe>
                </div>
                <div class="mf" style="padding:10px 18px;border-top:1px solid var(--color-line-hairline, #e2e8f0);display:flex;justify-content:space-between;align-items:center;background:var(--color-canvas-subtle, #f8fafc)">
                  <span class="s muted">{{ __('Verified MCMS Evidence') }} &middot; {{ $a->reference }}</span>
                  <button type="button" class="btn ter sm" data-close style="flex:0 0 auto;width:auto;padding:6px 14px">{{ __('Close Preview') }}</button>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- 2. Uploaded Applicant Files (Real Files Uploaded by Student) --}}
    @if(count($uploadedFiles) > 0)
      <div>
        <div class="s" style="font-weight:700;margin-bottom:8px;color:var(--color-ink, #090a0f)">{{ __('Uploaded Supporting Files') }}</div>
        <div style="display:flex;flex-direction:column;gap:8px">
          @foreach($uploadedFiles as $uFile)
            @php
              $uExt = strtoupper(pathinfo($uFile, PATHINFO_EXTENSION) ?: 'FILE');
              $uCanPreview = in_array($uExt, ['PDF', 'PNG', 'JPG', 'JPEG'], true);
              $uViewUrl = route($routeName, [$a->id, $uFile]);
              $uDlUrl = route($routeName, [$a->id, $uFile, 'download' => 1]);
              $uModalId = $idPrefix.'-upload-modal-'.$loop->index;
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1px solid var(--color-card-border, #090a0f);background:#ffffff;border-radius:var(--radius-sm, 4px);gap:12px;flex-wrap:wrap">
              <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1">
                <span class="tag ok" style="font-size:10.5px;font-weight:700;flex-shrink:0">{{ $uExt }}</span>
                <span style="font-family:var(--font-mono);font-size:12.5px;font-weight:600;color:var(--color-ink, #090a0f);word-break:break-all">{{ $uFile }}</span>
              </div>
              <div style="display:inline-flex;align-items:center;gap:8px;flex-shrink:0">
                @if($uCanPreview)
                  <button type="button" class="btn ter sm" data-modal="{{ $uModalId }}">{{ __('Preview') }}</button>
                @endif
                <a href="{{ $uDlUrl }}" class="btn primary sm">{{ __('Download') }}</a>
              </div>
            </div>
            @if($uCanPreview)
              <div class="scrim" id="modal-{{ $uModalId }}" hidden style="display:none;z-index:9999">
                <div class="modal wide" role="dialog" aria-modal="true" style="width:920px;max-width:95vw;height:86vh;max-height:86vh;display:flex;flex-direction:column;border-radius:8px;overflow:hidden">
                  <div class="mh" style="padding:12px 18px;border-bottom:1px solid var(--color-line-hairline, #e2e8f0);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas-subtle, #f8fafc);gap:12px">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0">
                      <span class="tag ok" style="font-size:10.5px;font-weight:700">{{ $uExt }}</span>
                      <h3 style="margin:0;font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $uFile }}</h3>
                    </div>
                    <div style="display:inline-flex;align-items:center;gap:8px;flex-shrink:0">
                      <a href="{{ $uViewUrl }}" class="btn ter sm" target="_blank">{{ __('Open in New Tab') }} &nearr;</a>
                      <a href="{{ $uDlUrl }}" class="btn primary sm">{{ __('Download') }}</a>
                      <button type="button" class="btn ter sm" data-close style="padding:4px 10px" aria-label="{{ __('Close') }}">&times;</button>
                    </div>
                  </div>
                  <div class="mb" style="flex:1;padding:0;overflow:auto;background:#f8fafc;display:flex;align-items:center;justify-content:center">
                    @if($uExt === 'PDF')
                      <iframe src="{{ $uViewUrl }}" style="width:100%;height:100%;border:0" title="{{ $uFile }}"></iframe>
                    @else
                      <img src="{{ $uViewUrl }}" alt="{{ $uFile }}" style="max-width:100%;max-height:100%;object-fit:contain;padding:16px">
                    @endif
                  </div>
                  <div class="mf" style="padding:10px 18px;border-top:1px solid var(--color-line-hairline, #e2e8f0);display:flex;justify-content:space-between;align-items:center;background:var(--color-canvas-subtle, #f8fafc)">
                    <span class="s muted">{{ __('Applicant Uploaded Evidence') }} &middot; {{ $a->reference }}</span>
                    <button type="button" class="btn ter sm" data-close style="flex:0 0 auto;width:auto;padding:6px 14px">{{ __('Close Preview') }}</button>
                  </div>
                </div>
              </div>
            @endif
          @endforeach
        </div>
      </div>
    @endif

    {{-- 3. External Supporting Links (Clickable URLs — Never Rendered as Fake PDFs) --}}
    @if(count($urls) > 0 || $otherNonUrl !== '')
      <div>
        <div class="s" style="font-weight:700;margin-bottom:8px;color:var(--color-ink, #090a0f)">{{ __('External Supporting Documents & Links') }}</div>
        <div style="display:flex;flex-direction:column;gap:8px">
          @foreach($urls as $url)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border:1px solid var(--color-card-border, #090a0f);background:#ffffff;border-radius:var(--radius-sm, 4px);gap:12px;flex-wrap:wrap">
              <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1">
                <span class="tag ok" style="font-size:10.5px;font-weight:700;flex-shrink:0">{{ __('LINK') }}</span>
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" style="font-family:var(--font-mono);font-size:12px;color:var(--color-primary, #0052ff);word-break:break-all;text-decoration:underline">{{ $url }}</a>
              </div>
              <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="btn primary sm" style="flex-shrink:0">{{ __('Open Link') }} &nearr;</a>
            </div>
          @endforeach
          @if($otherNonUrl !== '')
            <div style="padding:10px 14px;border:1px solid var(--color-line-hairline, #e2e8f0);background:var(--color-canvas-subtle, #f8fafc);border-radius:var(--radius-sm, 4px);font-size:13px">
              <strong>{{ __('Other specified:') }}</strong> {{ $otherNonUrl }}
            </div>
          @endif
        </div>
      </div>
    @endif

    {{-- 3. Applicant Notes (Plain Text — Never Rendered as Fake PDFs) --}}
    @if($notesNonUrl !== '')
      <div>
        <div class="s" style="font-weight:700;margin-bottom:6px;color:var(--color-ink, #090a0f)">{{ __('Applicant Notes') }}</div>
        <div style="padding:10px 14px;border:1px solid var(--color-line-hairline, #e2e8f0);background:var(--color-canvas-subtle, #f8fafc);border-radius:var(--radius-sm, 4px);font-size:13px;line-height:1.5;color:var(--color-ink, #090a0f)">
          {{ $notesNonUrl }}
        </div>
      </div>
    @endif

    {{-- 4. Collapsible Checklist Summary --}}
    @if($docChecklist)
      <details>
        <summary class="s" style="cursor:pointer;font-weight:600;color:var(--color-primary, #0052ff)">
          {{ __('View submitted document checklist (:ticked of :total)', ['ticked' => count($checkedDocs), 'total' => count(config('transfer.documents_checklist'))]) }}
        </summary>
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));gap:8px;margin-top:10px">
          @foreach(config('transfer.documents_checklist') as $k => $lbl)
            @php($ticked = in_array($k, $checkedDocs, true))
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:7px 10px;border:1px solid var(--color-line-hairline, #e2e8f0);background:#ffffff;border-radius:var(--radius-sm, 4px);font-size:12.5px">
              <span>{{ __($lbl) }}</span>
              <span class="tag {{ $ticked ? 'ok' : 'neutral' }}" style="font-size:10.5px">{{ $ticked ? __('Included') : __('Not ticked') }}</span>
            </div>
          @endforeach
        </div>
      </details>
    @endif

  </div>
</div>
