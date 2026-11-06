@extends('layouts.app')
@section('title', __('Documents'))
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('tasks') }}">{{ __('Home') }}</a>
  <span>/</span>
  <span>{{ __('Documents') }}</span>
</div>

<div class="title" style="margin-bottom:16px">
  <div>
    <h1>{{ __('Official Documents & Exports') }}</h1>
    <div class="sub">{{ __('Every official document opens as a standard A4 page ready to print or save as PDF. Machine-readable formats are provided alongside human-readable certificates. Documents leaving UTeM omit personal identity numbers, emails, and raw scores.') }}</div>
  </div>
</div>

@if($mine)
  <!-- Student Tabbed Document Hub -->
  <div data-tabs>
    <nav class="tabs" aria-label="Student document sections" style="margin-bottom:16px">
      <button data-tab="credentials" class="on">{{ __('Credentials & Transcripts') }} ({{ count($mine['credentials']) + 1 }})</button>
      <button data-tab="acrs">{{ __('Academic Credit Records') }} ({{ count($mine['acrs']) }})</button>
      <button data-tab="receipts">{{ __('Financial & Enrolment') }} ({{ count($mine['receipts']) }})</button>
    </nav>

    <!-- Pane 1: Credentials & Achievement Record -->
    <div data-pane="credentials">
      <!-- Master Achievement Transcript Feature Banner -->
      <div class="panel" style="margin-bottom:20px;border-left:4px solid var(--accent, #1e2a6e);background:#ffffff">
        <div class="bd" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;padding:20px">
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--primary, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
              {{ __('Official Transcript') }} &bull; {{ __('A4 Verifiable') }}
            </div>
            <h2 style="margin:0 0 4px;font-size:16px;color:var(--ink)">{{ __('Achievement record') }}</h2>
            <div class="s muted" style="max-width:680px;line-height:1.5">
              {{ __('Comprehensive student achievement transcript. Everything you have achieved in MCMS, broken down module by module with learning outcome attainments.') }}
            </div>
          </div>
          <div>
            <a href="{{ route('records.mine') }}" class="btn primary sm">
              <span class="btn-lbl">{{ __('View Achievement Record') }} &rarr;</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Verified Certificates & Digital Credentials Table -->
      <div class="panel">
        <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <h2 style="margin:0;font-size:15px">{{ __('Certificates and Digital Credentials') }}</h2>
          <span class="s muted">{{ count($mine['credentials']) }} {{ __('issued') }}</span>
        </div>
        <div class="table-wrap">
          <table id="student-credentials-table" style="margin:0">
            <thead>
              <tr>
                <th style="min-width:220px">{{ __('Micro-credential') }}</th>
                <th>{{ __('Credential Number') }}</th>
                <th>{{ __('Available Formats') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="num" style="min-width:280px">{{ __('Open & Download') }}</th>
              </tr>
            </thead>
            <tbody>
            @forelse($mine['credentials'] as $c)
              <tr>
                <td>
                  <b>{{ $c->name_en }}</b>
                  @if(!empty($c->issued_at))
                    <div class="s muted">{{ __('Issued on') }} {{ \Carbon\Carbon::parse($c->issued_at)->format('d/m/Y') }}</div>
                  @endif
                </td>
                <td><code>{{ $c->number }}</code></td>
                <td class="s muted">
                  PDF &middot; JSON &middot; JWT
                </td>
                <td>
                  <span class="tag {{ $c->status === 'issued' ? 'ok' : 'info' }}">
                    {{ ucfirst($c->status === 'issued' ? 'valid' : $c->status) }}
                  </span>
                </td>
                <td class="num">
                  <div style="display:inline-flex;gap:6px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
                    <a href="{{ route('credentials.certificate', $c->id) }}" target="_blank" class="btn sm ter" title="{{ __('Print official certificate') }}">
                      <span class="btn-lbl">{{ __('Certificate') }}</span> ↗
                    </a>
                    <a href="{{ route('credentials.sheet', $c->id) }}" target="_blank" class="btn sm ter" title="{{ __('Print specification sheet') }}">
                      <span class="btn-lbl">{{ __('Printable Sheet') }}</span> ↗
                    </a>
                    <a href="{{ route('credentials.download', [$c->id, 'json']) }}" class="btn sm sec" title="{{ __('Download W3C Verifiable Credential JSON with cryptographic proof') }}">
                      <span class="btn-lbl">{{ __('Digital Credential (JSON)') }}</span> ⤓
                    </a>
                    <a href="{{ route('credentials.download', [$c->id, 'jwt']) }}" class="btn sm ghost" title="{{ __('Download raw signed Ed25519 JWT token') }}">
                      <span class="btn-lbl">{{ __('JWT') }}</span> ⤓
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No credentials issued yet.') }}</td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;background:var(--bg-subtle, #f4f4f4);border-top:1px solid var(--rule);font-size:12px;color:var(--ink-muted);flex-wrap:wrap;gap:8px">
          <span>
            <strong style="color:var(--accent, #1e2a6e)">✓ {{ __('Verifiable Cryptographic Proofs') }}:</strong>
            {{ __('Digital Credential (.json) and Signed Token (.jwt) conform to W3C Verifiable Credentials and 1EdTech Open Badges 3.0.') }}
          </span>
          <a href="{{ route('verify') }}" target="_blank" style="font-weight:600;color:var(--accent, #1e2a6e)">
            {{ __('Open Public Cryptographic Verifier') }} &rarr;
          </a>
        </div>
      </div>
    </div>

    <!-- Pane 2: Academic Credit Records (ACR) -->
    <div data-pane="acrs">
      <div class="panel">
        <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <h2 style="margin:0;font-size:15px">{{ __('Academic Credit Records (ACR)') }}</h2>
          <span class="s muted">{{ count($mine['acrs']) }} {{ __('records') }}</span>
        </div>
        <div class="table-wrap">
          <table id="student-acrs-table" style="margin:0">
            <thead>
              <tr>
                <th>{{ __('Record reference') }}</th>
                <th>{{ __('Micro-credential') }}</th>
                <th class="num">{{ __('Credit Equivalent') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="num">{{ __('Confirmation date') }}</th>
                <th class="num">{{ __('Action') }}</th>
              </tr>
            </thead>
            <tbody>
            @forelse($mine['acrs'] as $a)
              <tr data-href="{{ route('credit.acr', $a->id) }}">
                <td><a href="{{ route('credit.acr', $a->id) }}"><b>{{ $a->reference }}</b></a></td>
                <td>{{ $a->name_en }}</td>
                <td class="num"><strong>{{ rtrim(rtrim((string) ($a->credits ?? 0), '0'), '.') }} {{ __('credits') }}</strong></td>
                <td><span class="tag ok">{{ ucfirst($a->status ?? 'confirmed') }}</span></td>
                <td class="num" data-v="{{ $a->created_at }}">{{ \Carbon\Carbon::parse($a->created_at)->format('d/m/Y') }}</td>
                <td class="num">
                  <a href="{{ route('credit.acr', $a->id) }}" class="btn sm ter">
                    <span class="btn-lbl">{{ __('View ACR (A4)') }}</span> ↗
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="muted" style="text-align:center;padding:24px">{{ __('No Academic Credit Records confirmed yet.') }}</td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Pane 3: Financial & Enrolment -->
    <div data-pane="receipts">
      <div class="panel">
        <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <h2 style="margin:0;font-size:15px">{{ __('Payment receipts') }}</h2>
          <span class="s muted">{{ count($mine['receipts']) }} {{ __('receipts') }}</span>
        </div>
        <div class="table-wrap">
          <table id="student-receipts-table" style="margin:0">
            <thead>
              <tr>
                <th style="min-width:240px">{{ __('Document') }}</th>
                <th>{{ __('Registration reference') }}</th>
                <th>{{ __('Amount Paid') }}</th>
                <th class="num">{{ __('Date') }}</th>
                <th class="num">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
            @forelse($mine['receipts'] as $r)
              <tr data-href="{{ route('enrolments.receipt', $r->id) }}">
                <td>
                  <b>Receipt for {{ $r->name_en }} ({{ $r->reference }})</b>
                  @if(!empty($r->payment_ref))
                    <div class="s muted">{{ __('Transaction Ref:') }} {{ $r->payment_ref }}</div>
                  @endif
                </td>
                <td><code>{{ $r->reference }}</code></td>
                <td>
                  <strong>RM {{ number_format((float) ($r->amount ?? 0), 2) }}</strong>
                </td>
                <td class="num" data-v="{{ $r->created_at }}">{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</td>
                <td class="num">
                  <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                    <a href="{{ route('enrolments.receipt', $r->id) }}" target="_blank" class="btn sm ter">
                      <span class="btn-lbl">{{ __('Receipt (A4)') }}</span> ↗
                    </a>
                    <a href="{{ route('enrolments.confirmation', $r->id) }}" target="_blank" class="btn sm ghost">
                      <span class="btn-lbl">{{ __('Confirmation letter') }}</span> ↗
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="muted" style="text-align:center;padding:24px">{{ __('No payments recorded yet.') }}</td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@else
  <div data-tabs>
    <nav class="tabs" aria-label="Staff document sections" style="margin-bottom:16px">
      @if($reports)
        <button data-tab="reports" class="on">{{ __('University Reports & Analytics') }} ({{ count($kinds) }})</button>
      @endif
      @if(isset($proposals) && $proposals->isNotEmpty())
        <button data-tab="governance">{{ __('Governance & Senate Packs') }} ({{ count($proposals) + count($suspensions) }})</button>
      @endif
      @if($dean)
        <button data-tab="exchange">{{ __('External Exchange & ABC') }} ({{ count($batches) + count($transfers) }})</button>
      @endif
      @if($codl)
        <button data-tab="ucreds">{{ __('UCreds Exchange') }} ({{ count($ucredsOfferings) + count($ucredsImports) }})</button>
      @endif
      @if($dean)
        <button data-tab="audit">{{ __('Audit & Compliance') }}</button>
      @endif
    </nav>

    <!-- Staff Pane 1: University Reports -->
    @if($reports)
      <div data-pane="reports">
        <div class="panel" id="report-documents">
          <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <h2 style="margin:0;font-size:15px">{{ __('Official university reports') }}</h2>
            <span class="s muted">{{ count($kinds) }} {{ __('report kinds') }}</span>
          </div>
          <div class="table-wrap">
            <table id="staff-reports-table" style="margin:0">
              <thead>
                <tr>
                  <th style="min-width:320px">{{ __('Report Title & Scope') }}</th>
                  <th>{{ __('Formats Available') }}</th>
                  <th>{{ __('Print / PDF') }}</th>
                  <th class="num">{{ __('Machine-Readable Data') }}</th>
                </tr>
              </thead>
              <tbody>
              @foreach($kinds as $k => $t)
                <tr>
                  <td>
                    <b>{{ $t }}</b>
                    <div class="s muted">{{ __('Standard institutional report under UTeM MCMS governance') }}</div>
                  </td>
                  <td class="s muted">
                    A4 &middot; CSV
                  </td>
                  <td>
                    <a href="{{ route('reports.print', $k) }}" target="_blank" class="btn sm ter">
                      <span class="btn-lbl">{{ __('Report') }} (A4)</span> ↗
                    </a>
                  </td>
                  <td class="num">
                    <a href="{{ route('reports.csv', $k) }}" class="btn sm sec">
                      <span class="btn-lbl">{{ __('CSV') }}</span> ⤓
                    </a>
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif

    <!-- Staff Pane 2: Governance & Senate Packs -->
    @if(isset($proposals) && $proposals->isNotEmpty())
      <div data-pane="governance">
        <div class="panel" id="proposal-documents" style="margin-bottom:20px">
          <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <h2 style="margin:0;font-size:15px">{{ __('Proposal prints & Senate decision packs') }}</h2>
            <span class="s muted">{{ count($proposals) }} {{ __('proposals') }}</span>
          </div>
          <div class="table-wrap">
            <table id="staff-proposals-table" style="margin:0">
              <thead>
                <tr>
                  <th style="min-width:240px">{{ __('Micro-credential') }}</th>
                  <th>{{ __('Version & Category') }}</th>
                  <th>{{ __('Status') }}</th>
                  <th class="num">{{ __('Printable Document') }}</th>
                </tr>
              </thead>
              <tbody>
              @foreach($proposals as $prop)
                <tr>
                  <td><b>{{ $prop->name_en }}</b></td>
                  <td>v{{ $prop->version_no }} &middot; <span class="tag neutral">{{ strtoupper($prop->category) }}</span></td>
                  <td><span class="tag info">{{ ucfirst($prop->status) }}</span></td>
                  <td class="num">
                    <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
                      <a href="{{ route('proposals.print', $prop->id) }}" target="_blank" class="btn sm ter">
                        <span class="btn-lbl">{{ __('Proposal (A4)') }}</span> ↗
                      </a>
                      <a href="{{ route('approvals.pack', $prop->id) }}?print=1" target="_blank" class="btn sm ter">
                        <span class="btn-lbl">{{ __('Senate pack (A4)') }}</span> ↗
                      </a>
                    </div>
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>

        @if($suspensions->isNotEmpty())
          <div class="panel" style="margin-bottom:20px">
            <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
              <h2 style="margin:0;font-size:15px">{{ __('Suspension or termination notices') }}</h2>
              <span class="s muted">{{ count($suspensions) }} {{ __('notices') }}</span>
            </div>
            <div class="table-wrap">
              <table id="staff-suspensions-table" style="margin:0">
                <thead>
                  <tr>
                    <th>{{ __('Micro-credential') }}</th>
                    <th>{{ __('Decision') }}</th>
                    <th class="num">{{ __('Print Notice') }}</th>
                  </tr>
                </thead>
                <tbody>
                @foreach($suspensions as $s)
                  <tr>
                    <td><b>{{ $s->name_en }}</b></td>
                    <td><span class="tag {{ $s->kind === 'termination' ? 'err' : 'warn' }}">{{ ucfirst($s->kind) }}</span></td>
                    <td class="num">
                      <a href="{{ route('suspensions.print', $s->id) }}" target="_blank" class="btn sm ter">
                        <span class="btn-lbl">{{ __('Notice (A4)') }}</span> ↗
                      </a>
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </div>
    @endif

    <!-- Staff Pane 3: External Exchange & ABC -->
    @if($dean)
      <div data-pane="exchange">
        <div class="panel" style="margin-bottom:20px">
          <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <h2 style="margin:0;font-size:15px">{{ __('Academic Bank of Credit (ABC) Deposit Batches') }}</h2>
            <span class="s muted">{{ count($batches) }} {{ __('batches') }}</span>
          </div>
          <div class="table-wrap">
            <table id="staff-batches-table" style="margin:0">
              <thead>
                <tr>
                  <th>{{ __('Batch Reference') }}</th>
                  <th>{{ __('Purpose & Scope') }}</th>
                  <th>{{ __('Format') }}</th>
                  <th class="num">{{ __('Action') }}</th>
                </tr>
              </thead>
              <tbody>
              @forelse($batches as $b)
                <tr>
                  <td><code>{{ $b->reference }}</code></td>
                  <td>
                    <b>ABC deposit export {{ $b->reference }}</b>
                    <div class="s muted">{{ __('Faculty batch export for Academic Bank of Credit (ABC) deposit') }}</div>
                  </td>
                  <td><span class="tag info">Batch A4 Print</span></td>
                  <td class="num">
                    <a href="{{ route('deposits.batch', $b->id) }}" target="_blank" class="btn sm ter">
                      <span class="btn-lbl">{{ __('View Batch (A4)') }}</span> ↗
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="muted" style="text-align:center;padding:24px">{{ __('No ABC export batches generated yet.') }}</td>
                </tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>

        @if(isset($transfers) && $transfers->isNotEmpty())
          <div class="panel">
            <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
              <h2 style="margin:0;font-size:15px">{{ __('Credit transfer decision letters') }}</h2>
              <span class="s muted">{{ count($transfers) }} {{ __('applications') }}</span>
            </div>
            <div class="table-wrap">
              <table id="staff-transfers-table" style="margin:0">
                <thead>
                  <tr>
                    <th>{{ __('Application') }}</th>
                    <th>{{ __('Applicant') }}</th>
                    <th>{{ __('Target Programme') }}</th>
                    <th>{{ __('Decision Status') }}</th>
                    <th class="num">{{ __('Print Letter') }}</th>
                  </tr>
                </thead>
                <tbody>
                @foreach($transfers as $t)
                  <tr>
                    <td><code>{{ $t->reference }}</code></td>
                    <td><b>{{ $t->student_name }}</b></td>
                    <td><span class="tag neutral">{{ $t->programme_code }}</span></td>
                    <td><span class="tag {{ $t->status === 'approved' ? 'ok' : ($t->status === 'partly' ? 'warn' : 'err') }}">{{ ucfirst($t->status) }}</span></td>
                    <td class="num">
                      <a href="{{ route('transfer.show', $t->id) }}?print=1" target="_blank" class="btn sm ter">
                        <span class="btn-lbl">{{ __('Letter (A4)') }}</span> ↗
                      </a>
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </div>
    @endif

    <!-- Staff Pane 4: UCreds Platform Exchange -->
    @if($codl)
      <div data-pane="ucreds">
        <div class="panel" id="codl-documents">
          <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <h2 style="margin:0;font-size:15px">{{ __('UCreds exchange documents') }}</h2>
            <span class="s muted">{{ count($ucredsOfferings) + count($ucredsImports) }} {{ __('files') }}</span>
          </div>
          <div class="table-wrap">
            <table id="staff-ucreds-table" style="margin:0">
              <thead>
                <tr>
                  <th>{{ __('Document Type') }}</th>
                  <th>{{ __('Cohort & Course') }}</th>
                  <th class="num">{{ __('Print / Export') }}</th>
                </tr>
              </thead>
              <tbody>
              @foreach($ucredsOfferings as $o)
                <tr>
                  <td><b>{{ __('Enrolment export manifest') }}</b></td>
                  <td>{{ $o->name_en }} {{ $o->cohort_name }}</td>
                  <td class="num">
                    <a href="{{ route('ucreds.export.manifest', $o->id) }}" target="_blank" class="btn sm ter">
                      <span class="btn-lbl">{{ __('Manifest (A4)') }}</span> ↗
                    </a>
                  </td>
                </tr>
              @endforeach
              @foreach($ucredsImports as $imp)
                <tr>
                  <td><b>{{ __('Import validation report') }}</b></td>
                  <td>{{ $imp->name_en }} {{ $imp->cohort_name }} ({{ $imp->original_name }})</td>
                  <td class="num">
                    <a href="{{ route('ucreds.report', $imp->id) }}?print=1" target="_blank" class="btn sm ter">
                      <span class="btn-lbl">{{ __('Report (A4)') }}</span> ↗
                    </a>
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif

    <!-- Staff Pane 5: Audit & Compliance -->
    @if($dean)
      <div data-pane="audit">
        <div class="panel">
          <div class="hd">
            <h2 style="margin:0;font-size:15px">{{ __('Audit & external exchange exports') }}</h2>
          </div>
          <div class="bd" style="padding:20px">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;background:var(--bg-subtle, #f4f4f4);padding:18px;border:1px solid var(--rule);margin-bottom:16px">
              <div>
                <h3 style="margin:0 0 4px;font-size:16px"><a href="{{ route('records.audit.print') }}"><b>{{ __('Audit trail export') }}</b></a></h3>
                <p class="small muted" style="margin:0;line-height:1.5;max-width:580px">
                  {{ __('Includes SHA-256 cryptographic integrity verification statement, verifying that no audit trail records have been modified, deleted, or backdated.') }}
                </p>
              </div>
              <div style="display:flex;gap:8px;align-items:center">
                <a href="{{ route('records.audit.print') }}" target="_blank" class="btn primary sm">
                  <span class="btn-lbl">{{ __('Print Audit Trail (A4)') }}</span> ↗
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
@endif

<!-- Document Authenticity & PDPA Privacy Card (D-38) -->
<div class="panel" style="margin-top:24px;background:#ffffff;border-left:4px solid var(--accent, #1e2a6e)">
  <div class="bd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;padding:18px 24px">
    <div style="display:flex;align-items:center;gap:14px;min-width:280px;flex:1">
      <div style="width:40px;height:40px;background:var(--bg-subtle, #f4f4f4);border:1px solid var(--rule);display:flex;align-items:center;justify-content:center;color:var(--accent, #1e2a6e);font-size:20px;flex-shrink:0">
        🛡
      </div>
      <div>
        <div style="font-weight:700;font-size:13.5px;color:var(--ink)">
          {{ __('Document privacy and authenticity') }}
        </div>
        <div class="s muted" style="margin-top:2px;line-height:1.4">
          {{ __('All documents printed or exported through MCMS carry cryptographic or reference watermarks. Documents distributed externally to learners or employers do not expose national ID numbers, emails, or provisional scores.') }}
        </div>
      </div>
    </div>
    <div style="flex-shrink:0">
      <a href="{{ route('verify') }}" target="_blank" class="btn sec sm">
        <span class="btn-lbl">{{ __('Open Public Cryptographic Verifier') }}</span> &rarr;
      </a>
    </div>
  </div>
</div>
@endsection
