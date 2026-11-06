@extends('layouts.app')
@section('title', $t('pathways'))
@section('content')
@guest @include('public._nav') @endguest

<div class="pub-sec">
  <div class="crumb" style="margin-bottom:12px">
    @auth
      <a href="{{ route('tasks') }}">{{ auth()->user()->hasAnyRole('student') && ! auth()->user()->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin') ? __('My learning') : __('Dashboard') }}</a> <span>/</span> <a href="{{ route('plan.mine') }}">{{ __('My degree plan') }}</a> <span>/</span> <span>{{ $t('pathways') }}</span>
    @else
      <a href="{{ route('public.home') }}">{{ $t('home') }}</a> <span>/</span> <span>{{ $t('pathways') }}</span>
    @endauth
  </div>

  <div class="title" style="margin-bottom:20px">
    <div>
      <h1 style="font-size:28px;font-weight:700;color:var(--text);margin:0 0 6px">{{ $t('pathways') }}</h1>
      <div class="sub" style="font-size:15px;color:var(--muted)">
        {{ $lang === 'ms' ? 'Pelan artikulasi rasmi yang diluluskan oleh Senat untuk pemindahan kredit ke program ijazah UTeM' : 'Senate-approved official degree articulation roadmaps for credit transfer into UTeM degree programmes' }}
      </div>
    </div>
  </div>

  <div class="card" id="pathway-notes" style="background:#edf5ff;border-left:4px solid #0f62fe;padding:16px 20px;margin-bottom:24px">
    <div style="font-weight:600;font-size:14px;color:#0043ce;margin-bottom:4px">
      {{ $lang === 'ms' ? 'Pemberitahuan Peraturan Akademik' : 'Academic Regulatory Notice' }}
    </div>
    <div style="font-size:13px;line-height:1.6;color:#161616">
      {{ $lang === 'ms' 
          ? 'Laluan artikulasi yang diluluskan tidak memindahkan kredit (transfers no credit) secara automatik: setiap kredit dinilai secara individu melalui permohonan pemindahan kredit rasmi selepas kemasukan. Tiada micro-credential membawa kepada ijazah secara automatik (no micro-credential leads to a degree automatically), walau berapa banyak yang dikumpul. Sekurang-kurangnya '.$min.'% kredit pengijazahan perlu diperoleh terus di UTeM di bawah syarat residensi.' 
          : 'An approved pathway transfers no credit: each credit goes through credit transfer evaluation by the receiving programme upon formal admission. No micro-credential leads to a degree automatically, however many are collected. At least '.$min.'% of the graduating credit must be earned directly at UTeM under residency regulations.' }}
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;margin-bottom:32px">
    @forelse($paths as $w)
      @php
        $totCredits = $w->items->sum('credits');
      @endphp
      <div class="panel pathway" style="border:1px solid #e0e0e0;border-radius:0;box-shadow:none;transition:border-color .15s ease">
        <div style="padding:20px;display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:16px">
          <div style="flex:1;min-width:280px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
              <span class="tag" style="background:#0f62fe;color:#fff;font-weight:600;padding:2px 8px;font-size:11px">
                {{ $w->code }}
              </span>
              <span class="muted" style="font-size:12px">
                {{ $lang === 'ms' ? 'Tahap MQF' : 'MQF Level' }} {{ $w->mqf_level }} &middot; {{ $w->total_credits }} {{ $lang === 'ms' ? 'Kredit Pengijazahan' : 'Graduating Credits' }} &middot; {{ ucfirst($w->mode) }}
              </span>
            </div>
            <h2 style="font-size:20px;font-weight:600;color:var(--text);margin:0 0 8px">
              {{ $w->name }}
            </h2>
            <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:13px;color:var(--muted)">
              <span>
                <b>{{ $w->items->count() }}</b> {{ $lang === 'ms' ? 'Micro-credential dipetakan' : 'Mapped micro-credentials' }}
              </span>
              <span>&bull;</span>
              <span>
                <b>{{ rtrim(rtrim((string) $totCredits, '0'), '.') }}</b> {{ $lang === 'ms' ? 'Maksimum potensi kredit artikulasi' : 'Max articulable credits' }}
              </span>
              @if(!empty($w->approval_ref))
                <span>&bull;</span>
                <span>{{ $lang === 'ms' ? 'Rujukan Senat' : 'Senate Ref' }}: <b>{{ $w->approval_ref }}</b></span>
              @endif
            </div>
          </div>

          <div style="display:flex;align-items:center;gap:10px">
            <a class="btn primary" href="{{ route('public.pathways.print', $w->id) }}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:600">
              <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                <path d="M4 1h8v3h-1V2H5v2H4V1zm8 11v3H4v-3h8zm1-1H3v5h10v-5zM3 5h10a2 2 0 0 1 2 2v4h-2V8H3v3H1V7a2 2 0 0 1 2-2zm10 2a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
              </svg>
              {{ $lang === 'ms' ? 'Muat Turun Pelan Laluan (PDF)' : 'Download Pathway Specification (PDF)' }}
            </a>
          </div>
        </div>
      </div>
    @empty
      <div class="card muted" style="padding:32px;text-align:center">
        <p style="margin:0;font-size:14px">
          {{ $lang === 'ms' ? 'Tiada laluan yang diterbitkan lagi. Laluan hanya dipaparkan selepas diluluskan oleh Senat.' : 'No pathway is published yet. Pathways appear here after Senate approval.' }}
        </p>
      </div>
    @endforelse
  </div>

  <div class="pub-sec alt" style="padding:28px 24px;border:1px solid #e0e0e0;background:#f4f4f4">
    <h2 style="font-size:20px;font-weight:600;margin:0 0 16px;color:var(--text)">
      {{ $lang === 'ms' ? 'Prinsip & Syarat Artikulasi Akademik' : 'Academic Articulation Principles & Regulations' }}
    </h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px">
      <div class="card" style="padding:16px;background:#ffffff;border:1px solid #e0e0e0">
        <div style="font-weight:600;font-size:14px;color:var(--primary);margin-bottom:6px">
          {{ $lang === 'ms' ? 'Sekurang-kurangnya '.$min.'% kredit di UTeM' : 'At least '.$min.'% of degree credits at UTeM' }}
        </div>
        <p class="muted small" style="margin:0;font-size:12.5px;line-height:1.5">
          {{ $lang === 'ms' ? 'Sekurang-kurangnya '.$min.'% daripada keseluruhan kredit pengijazahan mesti diselesaikan terus di UTeM. Kredit pindahan tidak boleh melebihi had baki pengajian.' : 'At least '.$min.'% of graduating credits must be completed directly as a registered UTeM student; transferred credits cannot exceed this threshold.' }}
        </p>
      </div>

      <div class="card" style="padding:16px;background:#ffffff;border:1px solid #e0e0e0">
        <div style="font-weight:600;font-size:14px;color:var(--primary);margin-bottom:6px">
          {{ $lang === 'ms' ? 'Syarat Residensi Kampus' : 'Campus Residency Requirement' }}
        </div>
        <p class="muted small" style="margin:0;font-size:12.5px;line-height:1.5">
          {{ $lang === 'ms' ? 'Program konvensional memerlukan sekurang-kurangnya 1 semester pengajian berdaftar di kampus fizikal UTeM sebelum penganugerahan ijazah.' : 'Conventional degree programmes require at least one registered semester in physical campus residency before degree conferment.' }}
        </p>
      </div>

      <div class="card" style="padding:16px;background:#ffffff;border:1px solid #e0e0e0">
        <div style="font-weight:600;font-size:14px;color:var(--primary);margin-bottom:6px">
          {{ $lang === 'ms' ? 'Kemasukan & Pendaftaran Rasmi' : 'Admission & Matriculation Separate' }}
        </div>
        <p class="muted small" style="margin:0;font-size:12.5px;line-height:1.5">
          {{ $lang === 'ms' ? 'Pemindahan kredit hanya diproses untuk pelajar yang telah diterima masuk secara rasmi oleh Pejabat Pendaftar UTeM memenuhi syarat kelayakan minimum.' : 'Credit transfer requires an admitted student. Admission is an independent evaluation conducted by the Admissions Office (Pejabat Pendaftar).' }}
        </p>
      </div>

      <div class="card" style="padding:16px;background:#ffffff;border:1px solid #e0e0e0">
        <div style="font-weight:600;font-size:14px;color:var(--primary);margin-bottom:6px">
          {{ $lang === 'ms' ? 'Tempoh Hayat Kredit 10 Tahun' : '10-Year Credit Expiry' }}
        </div>
        <p class="muted small" style="margin:0;font-size:12.5px;line-height:1.5">
          {{ $lang === 'ms' ? 'Kredit mikro-kredential sah digunakan untuk pemindahan sehingga 10 tahun dari tarikh deposit di Bank Kredit Akademik (ABC).' : 'Earned micro-credential credits are valid for transfer consideration up to 10 years from their deposit date in the Academic Bank of Credit (ABC).' }}
        </p>
      </div>
    </div>
  </div>
</div>

@include('public._foot')
@endsection
