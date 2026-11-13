<nav class="tabs pub-nav-bar" aria-label="Public site" style="background:var(--top-bg);border-bottom:1px solid var(--hairline);margin:-24px -40px 0 -40px;padding:0 40px;display:flex;align-items:center;flex-wrap:wrap">
  <div style="display:flex;align-items:center;gap:2px;flex-wrap:wrap">
    <a href="{{ route('public.home') }}" @if(request()->routeIs('public.home')) aria-current="page" @endif style="padding:14px 18px;font-size:14px;font-weight:{{ request()->routeIs('public.home') ? '700' : '500' }}">{{ $t('home') }}</a>
    <a href="{{ route('public.catalogue') }}" @if(request()->routeIs('public.catalogue', 'public.offering')) aria-current="page" @endif style="padding:14px 18px;font-size:14px;font-weight:{{ request()->routeIs('public.catalogue', 'public.offering') ? '700' : '500' }}">{{ $t('catalogue') }}</a>
    <a href="{{ route('public.pathways') }}" @if(request()->routeIs('public.pathways')) aria-current="page" @endif style="padding:14px 18px;font-size:14px;font-weight:{{ request()->routeIs('public.pathways') ? '700' : '500' }}">{{ $t('pathways') }}</a>
    <a href="{{ route('public.how') }}" @if(request()->routeIs('public.how')) aria-current="page" @endif style="padding:14px 18px;font-size:14px;font-weight:{{ request()->routeIs('public.how') ? '700' : '500' }}">{{ $t('how') }}</a>
    <a href="{{ route('public.faq') }}" @if(request()->routeIs('public.faq')) aria-current="page" @endif style="padding:14px 18px;font-size:14px;font-weight:{{ request()->routeIs('public.faq') ? '700' : '500' }}">FAQ</a>
    <a href="{{ route('public.verify') }}" @if(request()->routeIs('public.verify*')) aria-current="page" @endif style="padding:14px 18px;font-size:14px;font-weight:{{ request()->routeIs('public.verify*') ? '700' : '500' }}">{{ $lang === 'ms' ? 'Semak kredensial' : 'Verify a credential' }}</a>
  </div>
</nav>
