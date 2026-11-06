@extends('layouts.app')
@section('title', $t('home'))
@section('content')
@guest @include('public._nav') @endguest

<!-- Hero Section with uLearn Navy theme and UTeM Campus Background with blue hue -->
<div class="pub-hero" style="position:relative;background:linear-gradient(135deg, rgba(30, 42, 110, 0.90) 0%, rgba(17, 26, 71, 0.94) 100%), url('{{ asset('images/utem.jpeg') }}') center center / cover no-repeat;color:#ffffff;padding:56px 40px;margin:0 -40px 0 -40px;border-bottom:3px solid #0f62fe;display:flex;justify-content:space-between;align-items:center;gap:40px;flex-wrap:wrap">
  <div style="flex:1;max-width:640px">
    <div style="display:inline-block;background:rgba(255,255,255,0.15);padding:4px 12px;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:16px;color:#ffffff;backdrop-filter:blur(4px)">Universiti Teknikal Malaysia Melaka</div>
    
    <h1 style="font-size:38px;line-height:46px;font-weight:700;margin:0 0 16px;color:#ffffff;letter-spacing:-0.5px">
      {{ $lang === 'ms' ? 'Micro-credential di UTeM' : 'Micro-credentials at UTeM' }}
    </h1>
    
    <p style="font-size:16px;line-height:26px;color:#e0e7ff;margin:0 0 24px">
      {{ $lang === 'ms' 
          ? 'Program pembelajaran fleksibel, berasaskan standard MQF dan berorientasikan industri teknikal. Dapatkan lencana digital terverifikasi, rekod kredit akademik (ACR), dan laluan pemindahan kredit ke ijazah UTeM.' 
          : 'High-impact, flexible modular credentials accredited under MQF standards. Gain verified digital badges, academic credit records (ACR), and stackable pathways toward recognized UTeM degrees.' }}
    </p>

    <!-- Active Cohorts Badge -->
    <div style="display:inline-flex;align-items:center;gap:12px;padding:8px 16px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);backdrop-filter:blur(4px)">
      <div style="font-size:20px;font-weight:700;color:#ffffff">{{ $count }}</div>
      <div style="font-size:13px;color:#c9d8ff">{{ $lang === 'ms' ? 'Kohort Aktif Dibuka' : 'Active Cohorts Open' }}</div>
    </div>
  </div>

  <!-- Search Card pushed to more right -->
  <form method="get" action="{{ route('public.catalogue') }}" role="search" class="card" style="width:380px;margin-left:auto;background:#ffffff;padding:24px;color:#161616;box-shadow:0 12px 32px rgba(0,0,0,0.25);border-radius:0;border:none;flex-shrink:0">
    <div style="font-size:16px;font-weight:700;color:#1e2a6e;margin-bottom:12px;display:flex;align-items:center;gap:8px">
      <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
      </svg>
      {{ $lang === 'ms' ? 'Cari Tawaran' : 'Search Catalogue' }}
    </div>

    <div style="margin-bottom:14px">
      <label for="q" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">{{ $t('search') }}</label>
      <input type="search" id="q" name="q" placeholder="{{ $lang === 'ms' ? 'Tajuk, bidang atau kod...' : 'Keyword, field or code...' }}" style="width:100%;font-size:14px;padding:8px 10px;border:1px solid #8d8d8d">
    </div>

    <div style="display:flex;flex-direction:column;gap:8px">
      <button type="submit" class="btn primary" style="width:100%;justify-content:center;padding:10px;font-weight:600;font-size:14px">
        {{ $lang === 'ms' ? 'Cari Tawaran' : 'Search Offerings' }}
      </button>
      <a class="btn ghost sm" href="{{ route('public.catalogue') }}" style="width:100%;text-align:center;justify-content:center;font-size:13px">
        {{ $lang === 'ms' ? 'Semak semua '.$count.' tawaran' : 'Browse all '.$count.' offerings' }} &rarr;
      </a>
      <p class="muted small" style="margin:4px 0 0;text-align:center">{{ $count }} {{ $lang === 'ms' ? 'tawaran dibuka atau akan datang' : 'offerings open or upcoming' }} &middot; {{ $lang === 'ms' ? 'dibuka untuk pendaftaran' : 'offerings open for registration' }}</p>
    </div>
  </form>
</div>

@php
  $catNames = [
    'umc' => 'Unbundled Micro-Credential',
    'samc' => 'Standalone Micro-Credential',
    'mce' => 'Micro-Credential Eksekutif',
  ];
@endphp

<!-- Three Kinds of Micro-Credentials -->
<div class="pub-sec" style="margin-top:24px">
  <div style="margin-bottom:8px">
    <div style="font-size:12px;font-weight:700;color:#0f62fe;text-transform:uppercase;letter-spacing:0.5px">
      {{ $lang === 'ms' ? 'Kerangka Kelayakan' : 'Credential Framework' }}
    </div>
    <h2 style="font-weight:700;font-size:24px;line-height:32px;margin:4px 0 6px;color:var(--text)">
      {{ $lang === 'ms' ? 'Tiga Kategori Micro-Credential UTeM' : 'Three kinds of micro-credential' }}
    </h2>
    <div class="small muted">
      {{ $lang === 'ms' ? 'Setiap jenis micro-credential direka khas mengikut keperluan industri dan pemindahan kredit' : 'Each category adheres to specific regulatory pathways for professional and academic credit recognition' }}
    </div>
  </div>

  <div class="grid3">
    <!-- UMC Card -->
    <div class="card cat-umc" style="border:1px solid #e0e0e0;border-top:4px solid #1e2a6e;padding:20px;display:flex;flex-direction:column;gap:10px;background:#ffffff">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <span class="tag" style="background:#1e2a6e;color:#fff;font-weight:700;font-size:12px;padding:3px 8px">UMC</span>
        <span style="font-size:12px;color:#24a148;font-weight:600;display:flex;align-items:center;gap:4px">
          &#10003; {{ $lang === 'ms' ? 'Berkredit Kursus' : 'Direct Credit' }}
        </span>
      </div>
      <h3 style="font-size:18px;font-weight:700;margin:0;color:#161616">UMC &ndash; Unbundled Micro-Credential</h3>
      <p style="font-size:13px;line-height:1.6;color:#525252;margin:0">
        {{ $lang === 'ms' 
            ? 'Dipecahkan terus daripada kursus berkredit dalam program akademik UTeM sedia ada yang telah diakreditasi. Lengkapkan semua modul untuk dianugerahkan kredit kursus induk melalui ACR.' 
            : 'Unbundled directly from a parent credit course of an accredited UTeM programme. Completing all modules grants the academic credit of the parent course via an ACR.' }}
      </p>
      <div style="margin-top:auto;padding-top:12px;border-top:1px solid #f4f4f4;font-size:12px;color:#6f6f6f">
        <b>{{ $lang === 'ms' ? 'Status Kredit:' : 'Credit Status:' }}</b> {{ $lang === 'ms' ? 'Ya, melalui rekod ACR rasmi' : 'Yes, via official ACR record' }}
      </div>
    </div>

    <!-- SAMC Card -->
    <div class="card cat-samc" style="border:1px solid #e0e0e0;border-top:4px solid #24a148;padding:20px;display:flex;flex-direction:column;gap:10px;background:#ffffff">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <span class="tag" style="background:#24a148;color:#fff;font-weight:700;font-size:12px;padding:3px 8px">SAMC</span>
        <span style="font-size:12px;color:#0f62fe;font-weight:600;display:flex;align-items:center;gap:4px">
          &#9873; {{ $lang === 'ms' ? 'Tertakluk QV' : 'QV Verified' }}
        </span>
      </div>
      <h3 style="font-size:18px;font-weight:700;margin:0;color:#161616">SAMC &ndash; Standalone Micro-Credential</h3>
      <p style="font-size:13px;line-height:1.6;color:#525252;margin:0">
        {{ $lang === 'ms' 
            ? 'Kursus bebas sekurang-kurangnya 40 jam pembelajaran nosional (SLT). Membawa kredit pemindahan hanya selepas lulus audit Quality Verification (QV) oleh Jawatankuasa Kurikulum.' 
            : 'An independent course with minimum 40 Student Learning Hours. Articulates credit only after formal Quality Verification (QV) audit approval by curriculum authorities.' }}
      </p>
      <div style="margin-top:auto;padding-top:12px;border-top:1px solid #f4f4f4;font-size:12px;color:#6f6f6f">
        <b>{{ $lang === 'ms' ? 'Status Kredit:' : 'Credit Status:' }}</b> {{ $lang === 'ms' ? 'Hanya dengan kelulusan QV yang sah' : 'Only with valid Quality Verification' }}
      </div>
    </div>

    <!-- MCE Card -->
    <div class="card cat-mce" style="border:1px solid #e0e0e0;border-top:4px solid #f1c21b;padding:20px;display:flex;flex-direction:column;gap:10px;background:#ffffff">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <span class="tag" style="background:#f1c21b;color:#161616;font-weight:700;font-size:12px;padding:3px 8px">MCE</span>
        <span style="font-size:12px;color:#8d8d8d;font-weight:600">
          {{ $lang === 'ms' ? 'Bukan Kredit' : 'Non-Credit' }}
        </span>
      </div>
      <h3 style="font-size:18px;font-weight:700;margin:0;color:#161616">MCE &ndash; Micro-Credential Eksekutif</h3>
      <p style="font-size:13px;line-height:1.6;color:#525252;margin:0">
        {{ $lang === 'ms' 
            ? 'Kursus pendek pantas tanpa kredit eksekutif yang direka untuk peningkatan kemahiran kepimpinan, latihan profesional, dan pensijilan industri berterusan.' 
            : 'Executive, non-credit learning designed for workforce upskilling, executive leadership, and verifiable competence badges.' }}
      </p>
      <div style="margin-top:auto;padding-top:12px;border-top:1px solid #f4f4f4;font-size:12px;color:#6f6f6f">
        <b>{{ $lang === 'ms' ? 'Status Kredit:' : 'Credit Status:' }}</b> {{ $lang === 'ms' ? 'Tiada (Kredensial Sijil Kemahiran)' : 'None (Certificate of Competency)' }}
      </div>
    </div>
  </div>
</div>



<!-- Five Steps Track / How It Works -->
<div class="pub-sec">
  <div style="margin-bottom:8px">
    <div style="font-size:12px;font-weight:700;color:#0f62fe;text-transform:uppercase;letter-spacing:0.5px">
      {{ $lang === 'ms' ? 'Laluan Kemajuan Pembelajaran' : 'Academic Progression Model' }}
    </div>
    <h2 style="font-weight:700;font-size:24px;line-height:32px;margin:4px 0 4px;color:var(--text)">
      {{ $t('how') }}
    </h2>
    <p style="max-width:760px;font-size:14px;color:#525252;margin:0">
      {{ $lang === 'ms' 
          ? 'Setiap peringkat pembelajaran diuruskan secara telus dengan rekod rasmi tersendiri di bawah kerangka tadbir urus UTeM.' 
          : 'Each stage is a separate decision with its own record. Finishing one does not promise the next.' }}
    </p>
  </div>

  <div class="track" style="margin:16px 0;background:#ffffff;border:1px solid #e0e0e0;padding:20px">
    <div class="stp">
      <div style="font-size:12px;font-weight:700;color:#0f62fe;margin-bottom:2px">01</div>
      <b>{{ $lang === 'ms' ? 'Lencana Modul' : 'Module badge' }}</b>
      <span>{{ $lang === 'ms' ? 'Dianugerahkan bagi setiap modul yang diselesaikan' : 'Awarded for each module passed' }}</span>
    </div>
    <div class="stp">
      <div style="font-size:12px;font-weight:700;color:#0f62fe;margin-bottom:2px">02</div>
      <b>{{ $lang === 'ms' ? 'Sijil Pencapaian' : 'Certificate' }}</b>
      <span>{{ $lang === 'ms' ? 'Dikeluarkan rasmi apabila gred disahkan fakulti' : 'Issued when results are verified' }}</span>
    </div>
    <div class="stp">
      <div style="font-size:12px;font-weight:700;color:#0f62fe;margin-bottom:2px">03</div>
      <b>{{ $lang === 'ms' ? 'Rekod ACR' : 'Academic Credit Record' }}</b>
      <span>{{ $lang === 'ms' ? 'Penyata kredit akademik bagi kursus berkredit' : 'Academic credit record for credit offerings' }}</span>
    </div>
    <div class="stp">
      <div style="font-size:12px;font-weight:700;color:#0f62fe;margin-bottom:2px">04</div>
      <b>{{ $lang === 'ms' ? 'Deposit ABC' : 'ABC Deposit' }}</b>
      <span>{{ $lang === 'ms' ? 'Penyimpanan kredit kebangsaan dengan persetujuan' : 'National storage with your consent' }}</span>
    </div>
    <div class="stp">
      <div style="font-size:12px;font-weight:700;color:#0f62fe;margin-bottom:2px">05</div>
      <b>{{ $lang === 'ms' ? 'Pindahan Kredit' : 'Credit Transfer' }}</b>
      <span>{{ $lang === 'ms' ? 'Penilaian rasmi oleh program ijazah sasaran' : 'Decided by receiving university' }}</span>
    </div>
  </div>

  <div>
    <a class="btn ter sm" href="{{ route('public.how') }}">
      {{ $lang === 'ms' ? 'Ketahui setiap langkah secara terperinci' : 'Read each step in detail' }} &rarr;
    </a>
  </div>
</div>

<!-- Faculties Grid -->
<div class="pub-sec alt" style="background:#f4f4f4;margin:24px -40px;padding:36px 40px">
  <div style="margin-bottom:16px">
    <div style="font-size:12px;font-weight:700;color:#0f62fe;text-transform:uppercase;letter-spacing:0.5px">
      {{ $lang === 'ms' ? 'Pusat Tanggungjawab' : 'Academic Centres' }}
    </div>
    <h2 style="font-weight:700;font-size:24px;line-height:32px;margin:4px 0 0;color:var(--text)">
      {{ $lang === 'ms' ? 'Fakulti yang Menawarkan Micro-Credential' : 'Faculties offering micro-credentials' }}
    </h2>
  </div>

  <div class="grid4">
    @foreach($faculties as $fac)
      @php $facCount = $cohorts->where('ptj', $fac->code)->count(); @endphp
      <div class="card" style="cursor:pointer;background:#ffffff;border:1px solid #e0e0e0;padding:16px;transition:border-color .15s ease" onclick="location.href='{{ route('public.catalogue', ['ptj' => $fac->code]) }}'">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
          <b style="font-size:16px;color:#1e2a6e">{{ $fac->code }}</b>
          <span class="tag" style="font-size:10.5px;padding:1px 6px">{{ $facCount }} {{ $lang === 'ms' ? 'kohort' : 'cohorts' }}</span>
        </div>
        <span class="small muted" style="display:block;line-height:1.4">{{ $fac->name }}</span>
      </div>
    @endforeach
  </div>
</div>

<!-- Three Key Action Pillars (Pathways, Verification, FAQ) -->
<div class="pub-sec" style="margin-bottom:24px">
  <div class="grid3">
    <div class="card" style="border:1px solid #e0e0e0;padding:24px;display:flex;flex-direction:column;gap:12px;background:#ffffff">
      <div style="font-size:24px;color:#1e2a6e">&#9776;</div>
      <h3 style="font-size:18px;font-weight:700;margin:0;color:var(--text)">{{ $t('pathways') }}</h3>
      <p style="font-size:13.5px;line-height:1.5;color:#525252;margin:0">
        {{ $lang === 'ms' 
            ? 'Ketahui pelan artikulasi rasmi yang menghubungkan himpunan micro-credential ke peringkat ijazah sarjana muda. Muat turun spesifikasi pemetaan yang diluluskan Senat.' 
            : 'See which micro-credentials map to courses in a UTeM programme, and download Senate-approved degree pathway specifications.' }}
      </p>
      <a class="btn sm" href="{{ route('public.pathways') }}" style="margin-top:auto;width:fit-content">
        {{ $lang === 'ms' ? 'Lihat Laluan Ijazah' : 'View Pathways' }} &rarr;
      </a>
    </div>

    <div class="card" style="border:1px solid #e0e0e0;padding:24px;display:flex;flex-direction:column;gap:12px;background:#ffffff">
      <div style="font-size:24px;color:#0f62fe">&#10003;</div>
      <h3 style="font-size:18px;font-weight:700;margin:0;color:var(--text)">
        {{ $lang === 'ms' ? 'Semak Kredensial' : 'Verify a credential' }}
      </h3>
      <p style="font-size:13.5px;line-height:1.5;color:#525252;margin:0">
        {{ $lang === 'ms' 
            ? 'Pusat semakan terbuka untuk majikan dan institusi pendidikan mengesahkan kesahihan sijil dan lencana UTeM menggunakan kod pengesahan unik.' 
            : 'Employers and institutions can check a credential with the unique cryptographic code printed on it. Instant verification without login.' }}
      </p>
      <a class="btn sm" href="{{ route('public.verify') }}" style="margin-top:auto;width:fit-content">
        {{ $lang === 'ms' ? 'Sahkan Sijil' : 'Verify Credential' }} &rarr;
      </a>
    </div>

    <div class="card" style="border:1px solid #e0e0e0;padding:24px;display:flex;flex-direction:column;gap:12px;background:#ffffff">
      <div style="font-size:24px;color:#24a148">&#63;</div>
      <h3 style="font-size:18px;font-weight:700;margin:0;color:var(--text)">
        {{ $lang === 'ms' ? 'Soalan Lazim & Panduan' : 'Questions & FAQ' }}
      </h3>
      <p style="font-size:13.5px;line-height:1.5;color:#525252;margin:0">
        {{ $lang === 'ms' 
            ? 'Dapatkan jawapan lengkap mengenai perbezaan lencana vs sijil, syarat ACR, pendaftaran pelajar luar, yuran pembelajaran, dan deposit ABC.' 
            : 'Explore clear answers on badge vs certificate, ACR requirements, external learner enrolment, fees, and national ABC credit deposits.' }}
      </p>
      <a class="btn sm" href="{{ route('public.faq') }}" style="margin-top:auto;width:fit-content">
        {{ $lang === 'ms' ? 'Baca Soalan Lazim' : 'Read FAQ' }} &rarr;
      </a>
    </div>
  </div>

  <div class="card" style="margin-top:16px;background:#edf5ff;border:1px solid #d0e2ff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between">
    <span style="font-size:13px;color:#0043ce">
      <b>{{ __('UTeM Academic & Administrative Staff:') }}</b> {{ __('Access the full management portal to manage offerings, grades, and quality reviews.') }}
    </span>
    <a class="btn primary sm" href="{{ route('signin') }}">
      {{ __('Staff Sign In') }} &rarr;
    </a>
  </div>
</div>

@include('public._foot')
@endsection
