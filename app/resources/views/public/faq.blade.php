@extends('layouts.app')
@section('title', 'FAQ')
@section('content')
@guest @include('public._nav') @endguest
@php
$faqs_en = [
  'Badges & credentials' => [
    ['What is the difference between a badge and a certificate?', 'A badge is awarded for each module you pass. The certificate, called a credential in the system, is issued when you complete the whole micro-credential and results are verified.'],
    ['Does completing a micro-credential give me academic credit?', 'Only a UMC and a SAMC with valid quality verification (QV) can lead to credit. An MCE gives a certificate and no credit.'],
    ['What is QV and why does it matter?', 'Quality verification confirms a standalone micro-credential meets national standards. If the QV has lapsed for your cohort, the offering says "Credit after QV renewal" and you receive the certificate only.'],
  ],
  'Credit & ACR' => [
    ['What is an ACR?', 'An academic credit record. UTeM issues one after it confirms you achieved the credit-bearing micro-credential. It is a record, not a transfer.'],
    ['What is ABC and do I have to deposit?', 'The Academic Bank of Credit (ABC) stores your credit for 10 years. Deposit needs your consent; you can give, decline or postpone it.'],
    ['Can I use my credit in a degree?', 'You may apply for credit transfer into an admitted programme. The faculty decides course by course, at least 30% of the degree credits must be earned at UTeM, and the same credit cannot be redeemed twice.'],
    ['Will collecting micro-credentials give me a degree?', 'No. Pathways show what may be mapped. You still complete the programme requirements, including residency.'],
  ],
  'Registering & fees' => [
    ['How much does it cost and what about refunds?', 'The fee is shown on each offering. The refund policy is still to be confirmed; do not rely on a refund until it is published.'],
    ['I am not a UTeM student. Can I register?', 'Yes. External learners first register at Pejabat Pendaftar or HEPA to receive a learner ID, then sign in and register for a cohort.'],
    ['Which language is the course taught in?', 'Each offering lists its language. The portal and credentials are available in English and Bahasa Melayu.'],
  ],
  'Verification & privacy' => [
    ['How do I check someone\'s credential?', 'Use "Verify a credential" and enter the code printed on it, or scan its QR code. The page shows name, credential, issuer, date and status only.'],
    ['What happens if a credential is corrected or revoked?', 'A corrected credential stays valid and shows the correction date. A revoked credential shows as revoked with its date. Replaced credentials point to the new one.'],
    ['Who sees my data, and do I have to consent to ABC?', 'Public verification shows only the fields above, never results or credit. Deposit in ABC happens only if you consent, and you may withdraw consent before deposit.'],
  ],
];

$faqs_ms = [
  'Lencana & sijil' => [
    ['Apakah perbezaan antara lencana dan sijil?', 'Lencana dianugerahkan bagi setiap modul yang anda lulus. Sijil (kredensial) dikeluarkan apabila anda menamatkan keseluruhan micro-credential dan keputusan disahkan.'],
    ['Adakah menamatkan micro-credential memberi kredit akademik?', 'Hanya UMC dan SAMC dengan Quality Verification (QV) sah boleh membawa kredit. MCE memberikan sijil penamatan tanpa kredit.'],
    ['Apakah itu QV dan mengapa ia penting?', 'Quality Verification mengesahkan micro-credential memenuhi standard kebangsaan. Jika tempoh QV luput, tawaran menyatakan "Kredit selepas pembaharuan QV" dan anda hanya menerima sijil.'],
  ],
  'Kredit & ACR' => [
    ['Apakah itu ACR?', 'Academic Credit Record (Rekod Kredit Akademik). UTeM mengeluarkannya selepas mengesahkan pencapaian micro-credential berkredit. Ia adalah rekod, bukan pemindahan.'],
    ['Apakah itu ABC dan adakah saya wajib deposit?', 'Academic Bank of Credit (ABC) menyimpan kredit anda selama 10 tahun. Deposit memerlukan kebenaran anda.'],
    ['Bolehkah saya guna kredit untuk ijazah?', 'Anda boleh memohon pemindahan kredit ke program pengajian yang diterima masuk. Fakulti menilai kursus demi kursus, sekurang-kurangnya 30% kredit mesti diperoleh di UTeM.'],
    ['Adakah mengumpul micro-credential memberi saya ijazah secara automatik?', 'Tidak. Laluan menunjukkan apa yang boleh dipetakan. Anda tetap perlu memenuhi syarat pengijazahan dan residensi.'],
  ],
  'Pendaftaran & yuran' => [
    ['Berapakah yuran dan bagaimana dengan bayaran balik?', 'Yuran dipaparkan pada setiap tawaran. Dasar bayaran balik tertakluk kepada pengesahan CODL.'],
    ['Saya bukan pelajar UTeM. Bolehkah saya daftar?', 'Boleh. Pelajar luar perlu mendaftar di Pejabat Pendaftar atau HEPA terlebih dahulu untuk mendapatkan ID pelajar.'],
    ['Bahasa apakah yang digunakan?', 'Setiap tawaran menyenaraikan bahasa penyampaian. Portal dan kredensial menyokong Bahasa Inggeris dan Bahasa Melayu.'],
  ],
  'Pengesahan & data peribadi' => [
    ['Bagaimana cara menyemak kredensial seseorang?', 'Gunakan "Semak kredensial" dan masukkan kod pada sijil atau imbas kod QR. Paparan hanya menunjukkan nama, kredensial, pengeluar, tarikh dan status.'],
    ['Apa berlaku jika kredensial dibetulkan atau dibatalkan?', 'Kredensial yang dibetulkan kekal sah dan memaparkan tarikh pembetulan. Kredensial yang dibatalkan ditandakan sebagai terbatal.'],
    ['Siapa yang dapat melihat data saya?', 'Semakan awam hanya memaparkan maklumat asas, tidak pernah memaparkan gred, markah atau kredit.'],
  ],
];

$groups = $lang === 'ms' ? $faqs_ms : $faqs_en;
@endphp

<div class="pub-sec">
  <div class="crumb" style="margin-bottom:8px">
    <a href="{{ auth()->check() ? route('tasks') : route('public.home') }}">{{ auth()->check() ? (auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard')) : $t('home') }}</a> <span>/</span> <span>FAQ</span>
  </div>
  <div class="title">
    <div>
      <h1>{{ $lang === 'ms' ? 'Soalan Lazim (FAQ)' : 'Frequently asked questions' }}</h1>
      <div class="sub">{{ $lang === 'ms' ? 'Panduan rasmi mengenai kredensial, kredit akademik, pendaftaran dan pengiktirafan' : 'Answers about credentials, academic credit, registration and recognition' }}</div>
    </div>
  </div>

  <div data-tabs data-auto style="margin-top:20px">
    <nav class="tabs" aria-label="FAQ categories" style="margin-bottom:24px">
      @foreach($groups as $grp => $items)
        @php $tabSlug = \Illuminate\Support\Str::slug($grp); @endphp
        <button data-tab="{{ $tabSlug }}">{{ $grp }} <span class="muted">{{ count($items) }}</span></button>
      @endforeach
    </nav>

    @foreach($groups as $grp => $items)
      @php $tabSlug = \Illuminate\Support\Str::slug($grp); @endphp
      <div data-pane="{{ $tabSlug }}" class="col" style="gap:12px">
        @foreach($items as [$q, $a])
          <details class="card" style="padding:16px 20px;cursor:pointer">
            <summary style="font-weight:600;font-size:16px;color:var(--ink)">{{ $q }}</summary>
            <p style="margin-top:12px;margin-bottom:0;line-height:1.6;color:var(--ink-light, #393939);font-size:14px">{{ $a }}</p>
          </details>
        @endforeach
      </div>
    @endforeach
  </div>

  <div class="card" style="margin-top:28px;padding:20px;background:var(--s1);display:flex;align-items:center;justify-content:space-between">
    <div>
      <b style="font-size:15px;color:var(--ink)">{{ $lang === 'ms' ? 'Ada soalan yang tidak terjawab?' : 'Have a question that is not covered here?' }}</b>
      <div class="small muted" style="margin-top:2px">{{ $lang === 'ms' ? 'Hubungi Pusat Pembelajaran Terbuka & Digital (CODL) di codl@utem.edu.my' : 'Contact the Centre for Open & Digital Learning (CODL) at codl@utem.edu.my' }}</div>
    </div>
    <a href="mailto:codl@utem.edu.my" class="btn sm">{{ $lang === 'ms' ? 'Hubungi CODL' : 'Contact CODL' }}</a>
  </div>
</div>

@include('public._foot')
@endsection
