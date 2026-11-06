@extends('layouts.print')
@section('title', 'Certificate')
@section('land', '1')
@section('doc_title', $lang === 'ms' ? 'Sijil' : 'Certificate')
@section('doc_ref', $x['number'])
@section('doc_file', 'certificate-'.$x['number'].'.pdf')
@section('doc_actions')<a class="btn ter sm" href="{{ route('credentials.certificate', [$c->id, 'lang' => $lang === 'ms' ? 'en' : 'ms']) }}">{{ $lang === 'ms' ? 'English' : 'Bahasa Melayu' }}</a>@endsection
@section('content')
<div id="certificate" style="display:flex;flex-direction:column;gap:6px;border:2px solid var(--ink);padding:8mm 14mm 10px;flex:1;text-align:center;position:relative">
  <p style="letter-spacing:2px;font-size:9pt">{{ strtoupper($x['issuer']) }}</p>
  <h1>{{ $lang === 'ms' ? 'Sijil' : 'Certificate' }}</h1>
  @if($c->status === 'revoked')<div style="border:3px solid var(--err);color:var(--err-ink);font-size:22pt;font-weight:600;letter-spacing:6px;padding:2px 0">REVOKED</div>@endif
  <div style="font-size:10pt">{{ $lang === 'ms' ? 'Dengan ini disahkan bahawa' : 'This certifies that' }}</div>
  <div style="font-size:24pt;font-weight:300;line-height:1.1"><b>{{ $x['holder'] }}</b></div>
  <div style="font-size:10pt">{{ $lang === 'ms' ? 'telah menamatkan' : 'has completed' }}</div>
  <div style="font-size:16pt"><b>{{ $x['name'][$lang] }}</b> <span class="muted">({{ $x['category'] }})</span></div>
  <div style="white-space:pre-line;font-size:10pt">{{ $x['statement'][$lang] }}</div>
  @if($x['stages'])<div class="muted" style="font-size:9pt">{{ $x['stages'][$lang] }}</div>@endif
  @if($x['claims']['credit'] !== null)<div>{{ $lang === 'ms' ? 'Kredit' : 'Credit' }} {{ $x['claims']['credit'] }}@if($x['claims']['mqf']) &middot; MQF {{ $x['claims']['mqf'] }}@endif</div>@endif
  <div style="font-size:10pt">{{ $lang === 'ms' ? 'Tarikh anugerah' : 'Date awarded' }}: {{ \Carbon\Carbon::parse($x['issued'])->format('d/m/Y') }} &middot; {{ $lang === 'ms' ? 'Nombor' : 'Number' }}: {{ $x['number'] }}</div>
  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-top:auto;text-align:left;font-size:9pt">
    <div style="width:70mm;border-top:1px solid var(--ink);padding-top:4px"><b>{{ $x['signatory'] ?? '-' }}</b><div>{{ $lang === 'ms' ? 'Penandatangan' : 'Signed by' }}</div></div>
    <div style="display:flex;gap:8px;align-items:flex-end"><div role="img" aria-label="QR code to verify this certificate" style="width:24mm">{!! $qr !!}</div><div style="max-width:60mm;word-break:break-all"><b>{{ $lang === 'ms' ? 'Kod pengesahan' : 'Verification code' }}</b><div>{{ $url }}</div></div></div>
  </div>
  @if($c->status === 'corrected')<div class="muted" style="font-size:9pt">Revision {{ $c->revision }} (corrected)</div>@endif
</div>
@endsection
