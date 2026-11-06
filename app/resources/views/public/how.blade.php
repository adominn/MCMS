@extends('layouts.app')
@section('title', $t('how'))
@section('content')
@guest @include('public._nav') @endguest

<div class="pub-sec">
  <div class="crumb" style="margin-bottom:8px">
    <a href="{{ auth()->check() ? route('tasks') : route('public.home') }}">{{ auth()->check() ? (auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard')) : $t('home') }}</a> <span>/</span> <span>{{ $t('how') }}</span>
  </div>

  <div class="title">
    <div>
      <h1>{{ $t('how') }}</h1>
      <div class="sub">{{ $lang === 'ms' ? 'Lima peringkat. Setiap peringkat diputuskan secara berasingan dan direkodkan.' : 'Five stages. Each one is decided separately and recorded.' }}</div>
    </div>
  </div>

  <div class="track" style="margin:20px 0 28px">
    <div class="stp"><b>1. {{ $lang === 'ms' ? 'Lencana modul' : 'Module badge' }}</b><span>{{ $lang === 'ms' ? 'Lulus modul' : 'Passed module' }}</span></div>
    <div class="stp"><b>2. {{ $lang === 'ms' ? 'Kredensial' : 'Credential' }}</b><span>{{ $lang === 'ms' ? 'Penamatan lengkap' : 'Completion' }}</span></div>
    <div class="stp"><b>3. {{ $lang === 'ms' ? 'ACR' : 'ACR' }}</b><span>{{ $lang === 'ms' ? 'Rekod kredit UTeM' : 'Academic credit record' }}</span></div>
    <div class="stp"><b>4. {{ $lang === 'ms' ? 'Deposit ABC' : 'ABC deposit' }}</b><span>{{ $lang === 'ms' ? 'Penyimpanan kebangsaan' : 'National storage' }}</span></div>
    <div class="stp"><b>5. {{ $lang === 'ms' ? 'Pindahan kredit' : 'Credit transfer' }}</b><span>{{ $lang === 'ms' ? 'Keputusan fakulti' : 'Faculty decision' }}</span></div>
  </div>

  <p style="font-size:16px;line-height:24px;color:var(--ink-light);margin-bottom:24px">
    {{ $lang === 'ms' ? 'Setiap peringkat ialah keputusan yang berasingan. Satu peringkat tidak menjanjikan peringkat seterusnya secara automatik.' : 'Each stage is a separate decision. One does not give you the next.' }}
  </p>

  @php
    $stages_en = [
      ['Module badge', 'A badge for each module you pass, issued after the deputy dean verifies results.', 'It is not a qualification and carries no credit by itself.'],
      ['Certificate or credential', 'A signed, verifiable record that you completed the whole micro-credential. Anyone can check it with its code.', 'It is not proof that credit has been recorded or accepted anywhere.'],
      ['ACR (Academic Credit Record)', 'UTeM confirms the credit you earned. Only UMC and quality-verified SAMC offerings have one.', 'It is not a transfer, and an MCE never has one. Storing is not transferring.'],
      ['ABC deposit', 'With your consent, the credit is deposited in the national Academic Bank of Credit and stays valid for 10 years.', 'You may decline or postpone. Storing is not transferring, and deposit is not an admission or an exemption.'],
      ['Credit transfer', 'You apply to an admitted programme; the faculty compares outcomes and decides course by course.', 'It is not guaranteed, and the credit can be used only once.'],
    ];

    $stages_ms = [
      ['Lencana modul', 'Lencana untuk setiap modul yang anda lulus, dikeluarkan selepas timbalan dekan mengesahkan keputusan.', 'Ia bukan kelayakan dan tidak membawa kredit dengan sendirinya.'],
      ['Sijil atau kredensial', 'Rekod yang ditandatangani dan boleh disahkan bahawa anda menamatkan keseluruhan micro-credential.', 'Ia bukan bukti bahawa kredit telah direkodkan atau diterima di mana-mana.'],
      ['ACR (Rekod Kredit Akademik)', 'UTeM mengesahkan kredit yang diperoleh bagi pembelajaran yang layak. MCE tidak mempunyai ACR.', 'Menyimpan bukan memindahkan kredit.'],
      ['Deposit ABC', 'Dengan kebenaran anda, kredit disimpan di Academic Bank of Credit kebangsaan selama 10 tahun.', 'Menyimpan bukan memindahkan (Storing is not transferring). Anda boleh menolak atau menangguhkan.'],
      ['Pemindahan kredit', 'Program memutuskan berdasarkan hasil pembelajaran sama ada kredit diambil kira.', 'Ia tidak dijamin dan tertakluk kepada kelulusan fakulti.'],
    ];

    $items = $lang === 'ms' ? $stages_ms : $stages_en;
  @endphp

  <div class="grid3" style="margin-bottom:28px">
    @foreach($items as $idx => [$name, $whatIs, $whatNot])
      <div class="card" style="padding:22px;gap:12px;display:flex;flex-direction:column;justify-content:space-between">
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <span class="tag info">Stage {{ $idx + 1 }}</span>
          </div>
          <b style="font-size:17px;color:var(--ink);display:block;margin-bottom:10px">{{ $idx + 1 }}. {{ $name }}</b>
          
          <div style="margin-bottom:12px;font-size:14px;line-height:1.5">
            <div style="font-size:12px;font-weight:600;color:var(--primary, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">{{ $lang === 'ms' ? 'Apa yang diberi' : 'What it is' }}</div>
            <div style="color:var(--ink);margin-top:4px">{{ $whatIs }}</div>
          </div>
        </div>

        <div style="border-top:1px solid var(--hairline);padding-top:10px;font-size:13px;line-height:1.5;color:var(--muted)">
          <b>{{ $lang === 'ms' ? 'Bukan jaminan:' : 'What it is not:' }}</b> {{ $whatNot }}
        </div>
      </div>
    @endforeach

    <div class="card" style="padding:22px;gap:12px;background:var(--s1);display:flex;flex-direction:column;justify-content:space-between">
      <div>
        <div style="font-size:12px;font-weight:600;color:var(--primary, #1e2a6e);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">{{ __('Summary') }}</div>
        <b style="font-size:17px;color:var(--ink);display:block;margin:8px 0 6px">{{ $lang === 'ms' ? 'Keputusan Bebas' : 'Independent Decisions' }}</b>
        <p class="muted small" style="margin:0;line-height:1.5">
          {{ $lang === 'ms' ? 'Setiap langkah memerlukan kelulusan dan pengesahan berasingan. Anda bebas berhenti pada bila-bila masa.' : 'Each step is an independent decision with its own governance. You may pause or stop at any milestone.' }}
        </p>
      </div>
      <div class="row" style="gap:8px;margin-top:12px">
        <a href="{{ route('public.catalogue') }}" class="btn sm">{{ $lang === 'ms' ? 'Cari tawaran' : 'Browse offerings' }}</a>
        <a href="{{ route('public.pathways') }}" class="btn ter sm">{{ $lang === 'ms' ? 'Laluan' : 'Pathways' }} &rarr;</a>
      </div>
    </div>
  </div>

  <div class="note info" style="margin-bottom:24px">
    <b>{{ $lang === 'ms' ? 'Penamatan, ACR, deposit ABC dan pemindahan kredit ialah keputusan yang berasingan.' : 'Completion, ACR, ABC deposit and credit transfer are separate decisions.' }}</b>
    <span style="display:block;margin-top:4px">{{ $lang === 'ms' ? 'Menamatkan tawaran ini tidak menjamin ACR, deposit dalam ABC, atau mana-mana universiti akan menerima kredit tersebut. Anda boleh berhenti pada bila-bila langkah.' : 'Completing this offering does not guarantee an ACR, a deposit in ABC, or that any university will accept the credit. Each is decided on its own, and you can stop at any step.' }}</span>
  </div>
</div>

@include('public._foot')
@endsection
