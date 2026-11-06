<footer class="foot">
  <div>
    <div style="margin-bottom:12px">
      <img src="{{ asset('images/codl-logo.png') }}" alt="CODL Logo" style="height:48px;width:auto;display:block;margin-bottom:12px">
      <b>UTeM Micro-credentials</b>
      <div style="color:#d0d0d0;font-size:13px;line-height:1.4">Centre for Open and Distance Learning (CODL)</div>
    </div>
    <div style="color:#a8a8a8;font-size:12px;line-height:1.4">
      UTeM, Hang Tuah Jaya, 76100 Durian Tunggal, Melaka
    </div>
  </div>
  <div>
    <b>{{ $lang === 'ms' ? 'Pelajari' : 'Learn' }}</b>
    <a href="{{ route('public.catalogue') }}" style="display:block">{{ $lang === 'ms' ? 'Senarai penuh tawaran' : 'All micro-credentials' }}</a>
    <a href="{{ route('public.pathways') }}" style="display:block">{{ $lang === 'ms' ? 'Laluan ke ijazah (panduan)' : 'Degree pathways guide' }}</a>
    <a href="{{ route('public.how') }}" style="display:block">{{ $lang === 'ms' ? 'Peringkat pengiktirafan (panduan)' : 'Recognition stages guide' }}</a>
  </div>
  <div>
    <b>{{ $lang === 'ms' ? 'Bantuan' : 'Help' }}</b>
    <a href="{{ route('public.faq') }}" style="display:block">FAQ</a>
    <a href="{{ route('public.verify') }}" style="display:block">{{ $lang === 'ms' ? 'Semak kredensial' : 'Verify a credential' }}</a>
    <span style="display:block">codl@utem.edu.my</span>
  </div>
  <div>
    <b>{{ $lang === 'ms' ? 'Dasar' : 'Policies' }}</b>
    <a href="{{ route('public.privacy') }}" style="display:block">{{ $lang === 'ms' ? 'Notis Privasi (PDPA)' : 'Privacy notice (PDPA)' }}</a>
    <a href="{{ route('public.terms') }}" style="display:block">{{ $lang === 'ms' ? 'Syarat Penggunaan' : 'Terms of use' }}</a>
    <a href="{{ route('public.accessibility') }}" style="display:block">{{ $lang === 'ms' ? 'Kebolehcapaian' : 'Accessibility' }}</a>
  </div>
  <div style="margin-left:auto;max-width:360px">
    {{ $lang === 'ms' ? 'Sistem Pengurusan Micro-Credential UTeM (MCMS). Mematuhi Garis Panduan Amalan Baik MQA & KPT.' : 'UTeM Micro-Credential Management System (MCMS). Compliant with MQA & MoHE Good Practice Guidelines.' }}
  </div>
</footer>
