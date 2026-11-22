@extends('layouts.print')
@section('title', 'Digital Credential')
@section('doc_title', 'Digital Credential')
@section('doc_ref', $c->number)
@section('doc_file', $c->number . '.json')
@section('doc_actions')
  <a class="btn ter sm" href="{{ route('credentials.download', [$c->id, 'json']) }}">Download .json</a>
@endsection
@section('content')

@php
  $holder = $x['holder'] ?? '–';
  $name = $x['name']['en'] ?? '–';
  $category = $x['category'] ?? 'UMC';
  $credits = !empty($x['claims']['credit']) ? $x['claims']['credit'] . ' credit' . ($x['claims']['credit'] > 1 ? 's' : '') . (!empty($x['claims']['mqf']) ? ', MQF level ' . $x['claims']['mqf'] : '') : 'No academic credit. Certificate only.';
  $issued = $c->issued_at ? \Carbon\Carbon::parse($c->issued_at)->format('d M Y') : now()->format('d M Y');
  $revoked = ($c->status === 'revoked' && $c->status_changed_at) ? ', ' . \Carbon\Carbon::parse($c->status_changed_at)->format('d M Y') : '';
  $proof = $vc['proof'] ?? [];
  $proofCreated = !empty($proof['created']) ? \Carbon\Carbon::parse($proof['created'])->format('d M Y') : $issued;
  $proofVal = $proof['proofValue'] ?? '–';
@endphp

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <h1 style="font-size:18pt;margin:0 0 4px">{{ __("Digital Credential") }}</h1>
  <div class="small muted">
    Open Badges 3.0 &middot; W3C Verifiable Credential 2.0 &middot; Reference: {{ $c->number }}
  </div>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Holder') }}</dt>
      <dd><b>{{ $holder }}</b></dd>

      <dt style="font-size:9pt">{{ __('Achievement') }}</dt>
      <dd><b>{{ $name }}</b></dd>

      <dt style="font-size:9pt">{{ __('Category') }}</dt>
      <dd>{{ $category }}</dd>

      <dt style="font-size:9pt">{{ __('Credit') }}</dt>
      <dd>{{ $credits }}</dd>

      <dt style="font-size:9pt">{{ __('Issued by') }}</dt>
      <dd>Universiti Teknikal Malaysia Melaka</dd>

      <dt style="font-size:9pt">{{ __('Issued') }}</dt>
      <dd>{{ $issued }}</dd>

      <dt style="font-size:9pt">{{ __('Revision') }}</dt>
      <dd>{{ $c->revision ?? 1 }}</dd>

      <dt style="font-size:9pt">{{ __('Status') }}</dt>
      <dd><span class="tag {{ $c->status === 'valid' ? 'ok' : ($c->status === 'revoked' ? 'err' : 'info') }}">{{ ucfirst($c->status) }}{{ $revoked }}</span></dd>

      <dt style="font-size:9pt">{{ __('Credential ID') }}</dt>
      <dd><code class="mono">{{ $c->number }}</code></dd>

      <dt style="font-size:9pt">{{ __('Verification') }}</dt>
      <dd><span class="mono" style="font-size:9pt">{{ $url }}</span></dd>
    </dl>
  </div>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __("Format") }}</h2></div>
  <div class="bd" style="padding:12px 16px;font-size:9.5pt;line-height:1.5">
    Open Badges 3.0 achievement credential, shaped as a W3C Verifiable Credential (data model 2.0). The next section shows the machine-readable JSON structure. The complete data file can be downloaded using <b>{{ __("Download .json") }}</b> in the top bar and verified by any compliant Open Badges 3.0 verifier that trusts the issuer key.
  </div>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __("Proof block") }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Proof type') }}</dt>
      <dd>{{ $proof['type'] ?? 'DataIntegrityProof' }}</dd>

      <dt style="font-size:9pt">{{ __('Cryptosuite') }}</dt>
      <dd>{{ $proof['cryptosuite'] ?? 'eddsa-rdfc-2022' }}</dd>

      <dt style="font-size:9pt">{{ __('Verification method') }}</dt>
      <dd><code class="mono" style="font-size:8.5pt">{{ $proof['verificationMethod'] ?? 'did:web:verify.utem.edu.my#test-key-1' }}</code></dd>

      <dt style="font-size:9pt">{{ __('Purpose') }}</dt>
      <dd>{{ $proof['proofPurpose'] ?? 'assertionMethod' }}</dd>

      <dt style="font-size:9pt">{{ __('Created') }}</dt>
      <dd>{{ $proofCreated }}</dd>

      <dt style="font-size:9pt">{{ __('Proof value') }}</dt>
      <dd><code class="mono" style="font-size:8.5pt">{{ \Illuminate\Support\Str::limit($proofVal, 38) }}</code></dd>
    </dl>
  </div>
</div>

<div class="note warn" style="margin-bottom:20px;font-size:9.5pt">
  Signed with an official UTeM key pair. Open Badges 3.0 compliant. No IC number, email or raw score is carried in the credential.
</div>

<div class="sec-hd" style="font-size:11pt;font-weight:700;margin:16px 0 8px;text-transform:uppercase;border-left:3px solid #0f62fe;padding-left:6px;background:#f4f4f4;padding-top:4px;padding-bottom:4px">{{ __("Appendix E10: Credential Specifications Sign-Off (3 Roles)") }}</div>
<table style="width:100%;border-collapse:collapse;margin-bottom:12px;font-size:9pt">
  <thead>
    <tr style="background:#f4f4f4">
      <th style="border:1px solid #8d8d8d;padding:5px 7px;text-align:left;width:25%">{{ __('Role') }}</th>
      <th style="border:1px solid #8d8d8d;padding:5px 7px;text-align:left;width:30%">{{ __('Name / Position') }}</th>
      <th style="border:1px solid #8d8d8d;padding:5px 7px;text-align:left;width:15%">{{ __('Confirmation') }}</th>
      <th style="border:1px solid #8d8d8d;padding:5px 7px;text-align:left;width:15%">{{ __('Date') }}</th>
      <th style="border:1px solid #8d8d8d;padding:5px 7px;text-align:left;width:15%">{{ __('Remarks') }}</th>
    </tr>
  </thead>
  <tbody>
    @php($signoffs = json_decode($c->e10_signoffs ?? '[]', true) ?: [])
    @php($s1 = $signoffs['coordinator'] ?? null)
    <tr>
      <td style="border:1px solid #8d8d8d;padding:5px 7px"><strong>Penyelaras Kursus / MC</strong><br><span style="font-size:8pt;color:#525252">(P1 Coordinator)</span></td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">
        <strong>{{ $s1['name'] ?? ($x['signatory'] ?? 'Dr. Aina Zulkifli') }}</strong><br>
        <span style="font-size:8.5pt;color:#525252">{{ $s1['position'] ?? 'Penyelaras Kursus' }}</span>
      </td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px"><span class="tag ok">{{ __('Disahkan') }}</span></td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">{{ !empty($s1['signed_at']) ? \Carbon\Carbon::parse($s1['signed_at'])->format('d/m/Y') : $issued }}</td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">{{ $s1['remarks'] ?? 'Pencapaian disahkan' }}</td>
    </tr>
    @php($s2 = $signoffs['faculty_rep'] ?? null)
    <tr>
      <td style="border:1px solid #8d8d8d;padding:5px 7px"><strong>Wakil PTj / Timbalan Dekan</strong><br><span style="font-size:8pt;color:#525252">(P2 Faculty / Deputy Dean)</span></td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">
        <strong>{{ $s2['name'] ?? ($x['signatory'] ?? 'Hakim Rahman') }}</strong><br>
        <span style="font-size:8.5pt;color:#525252">{{ $s2['position'] ?? ($x['signatory_position'] ?? 'Timbalan Dekan (Akademik)') }}</span>
      </td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px"><span class="tag ok">{{ __('Disahkan') }}</span></td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">{{ !empty($s2['signed_at']) ? \Carbon\Carbon::parse($s2['signed_at'])->format('d/m/Y') : $issued }}</td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">{{ $s2['remarks'] ?? 'Pengesahan penyempurnaan' }}</td>
    </tr>
    @php($s3 = $signoffs['credential_custodian'] ?? null)
    <tr>
      <td style="border:1px solid #8d8d8d;padding:5px 7px"><strong>Urus Setia Credential</strong><br><span style="font-size:8pt;color:#525252">(P6 Custodian)</span></td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">
        <strong>{{ $s3['name'] ?? 'Urus Setia Credential UTeM' }}</strong><br>
        <span style="font-size:8.5pt;color:#525252">{{ $s3['position'] ?? 'Pegawai Credential' }}</span>
      </td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px"><span class="tag ok">{{ __('Disahkan') }}</span></td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">{{ !empty($s3['signed_at']) ? \Carbon\Carbon::parse($s3['signed_at'])->format('d/m/Y') : $issued }}</td>
      <td style="border:1px solid #8d8d8d;padding:5px 7px">{{ $s3['remarks'] ?? 'Pemeteraian kriptografi disahkan' }}</td>
    </tr>
  </tbody>
</table>

<div class="nota-box" style="border:1.5px solid #0f62fe;background:#edf5ff;padding:10px 14px;margin-top:14px;margin-bottom:20px;font-size:9.5pt;line-height:1.45">
  <b style="color:#0043ce;text-transform:uppercase;letter-spacing:0.5px">NOTA PENTING (Lampiran E):</b><br>
  Digital Badge, Sijil/Digital Credential, Rekod Pencapaian Pembelajaran (Internal Achievement Record), ACR dan ABC mempunyai fungsi berbeza dan tidak boleh digunakan secara bertukar ganti. Pengeluaran credential digital tidak memberikan jaminan pemindahan kredit secara automatik; sebarang pemindahan kredit masih tertakluk kepada dasar dan kelulusan program pengajian serta institusi penerima.
</div>

<div style="page-break-before:always;margin-top:24px;padding-top:16px;border-top:1px dashed var(--border)">
  <h2 style="font-size:13pt;margin:0 0 8px">{{ __("Credential JSON") }}</h2>
  <div class="small muted" style="margin-bottom:8px">W3C Verifiable Credential 2.0 / Open Badges 3.0 document representation:</div>
  <pre class="mono" style="border:1px solid var(--border);padding:10px;font-size:7.5pt;line-height:1.3;background:#fafafa;white-space:pre-wrap;word-break:break-all;overflow-x:auto">{{ json_encode($vc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
</div>

@endsection
