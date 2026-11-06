@extends('layouts.app')
@section('title', $t('catalogue'))
@section('content')
@guest @include('public._nav') @endguest

<div class="pub-sec">
  <div class="crumb" style="margin-bottom:8px">
    <a href="{{ auth()->check() ? route('tasks') : route('public.home') }}">{{ auth()->check() ? (auth()->user()->hasAnyRole('student') ? __('My learning') : __('Dashboard')) : $t('home') }}</a> <span>/</span> <span>{{ $t('catalogue') }}</span>
  </div>
  <div class="title">
    <div>
      <h1>{{ $t('catalogue') }}</h1>
      <div class="sub" id="catalogue-count">{{ $rows->total() }} {{ $rows->total() === 1 ? 'offering' : 'offerings' }} {{ $lang === 'ms' ? 'dibuka atau akan datang' : 'open for registration' }}</div>
    </div>
  </div>

  <div class="row" style="gap:32px;align-items:flex-start">
    <form method="get" action="{{ route('public.catalogue') }}" data-auto-filter data-target="#catalogue-results" data-count="#catalogue-count" role="search" aria-label="{{ $t('filters') }}" class="card col" style="width:280px;flex-shrink:0;gap:12px">
      <div style="font-weight:600;font-size:16px;border-bottom:1px solid var(--hairline);padding-bottom:8px">{{ $t('filters') }}</div>
      
      <div>
        <label for="q" style="font-weight:500;display:block;margin-bottom:4px">{{ $t('search') }}</label>
        <input type="search" id="q" name="q" value="{{ $f['q'] ?? '' }}" placeholder="{{ $lang === 'ms' ? 'Nama, kod atau fakulti' : 'Name, code or faculty' }}" style="width:100%">
      </div>

      <div>
        <label for="category" style="font-weight:500;display:block;margin-bottom:4px">{{ $t('category') }}</label>
        <select id="category" name="category" style="width:100%">
          <option value="">{{ $lang === 'ms' ? 'Semua kategori' : 'All categories' }}</option>
          @foreach(['umc' => 'UMC', 'samc' => 'SAMC', 'mce' => 'MCE'] as $k => $l)
            <option value="{{ $k }}" @selected(($f['category'] ?? '') === $k)>{{ $l }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="ptj" style="font-weight:500;display:block;margin-bottom:4px">{{ $t('ptj') }}</label>
        <select id="ptj" name="ptj" style="width:100%">
          <option value="">{{ $lang === 'ms' ? 'Semua fakulti' : 'All faculties' }}</option>
          @foreach($ptjs as $p)
            <option value="{{ $p->code }}" @selected(($f['ptj'] ?? '') === $p->code)>{{ $p->code }} - {{ $p->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="mode" style="font-weight:500;display:block;margin-bottom:4px">{{ $t('mode') }}</label>
        <select id="mode" name="mode" style="width:100%">
          <option value="">{{ $lang === 'ms' ? 'Semua mod' : 'All modes' }}</option>
          @foreach($modes as $k => $l)
            <option value="{{ $k }}" @selected(($f['mode'] ?? '') === $k)>{{ $l }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label for="field" style="font-weight:500;display:block;margin-bottom:4px">{{ $t('field') }}</label>
        <input type="text" id="field" name="field" value="{{ $f['field'] ?? '' }}" placeholder="{{ $lang === 'ms' ? 'Contoh: cloud, data' : 'e.g. cloud, data' }}" style="width:100%">
      </div>

      <div>
        <label for="language" style="font-weight:500;display:block;margin-bottom:4px">{{ $t('language') }}</label>
        <input type="text" id="language" name="language" value="{{ $f['language'] ?? '' }}" placeholder="{{ $lang === 'ms' ? 'Bahasa penyampaian' : 'Language' }}" style="width:100%">
      </div>

      <div>
        <label for="starts" style="font-weight:500;display:block;margin-bottom:4px">{{ $t('starts') }}</label>
        <input type="date" id="starts" name="starts" value="{{ $f['starts'] ?? '' }}" style="width:100%">
      </div>

      <div>
        <label for="fee_max" style="font-weight:500;display:block;margin-bottom:4px">{{ $t('max_fee') }}</label>
        <input type="number" id="fee_max" name="fee_max" value="{{ $f['fee_max'] ?? '' }}" placeholder="RM" style="width:100%">
      </div>

      <div>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="checkbox" name="free" value="1" @checked($f['free'] ?? false)>
          <span>{{ $t('free') }}</span>
        </label>
      </div>

      <div class="row" style="gap:8px;margin-top:4px">
        <button type="submit" class="btn sm">{{ $t('search') }}</button>
        <a href="{{ route('public.catalogue') }}" class="btn ter sm">{{ $lang === 'ms' ? 'Kosongkan' : 'Clear filters' }}</a>
      </div>
    </form>

    <div class="col grow" style="gap:16px" id="catalogue-results">
      <div class="small muted">{{ $rows->total() }} {{ $rows->total() === 1 ? 'offering' : 'offerings' }}{{ !empty($f['q']) ? ' for "'.e($f['q']).'"' : '' }}</div>

      @forelse($rows as $r)
        <div class="card" style="padding:20px;gap:12px;cursor:pointer" onclick="if(!event.target.closest('a')) location.href='{{ route('public.offering', $r->id) }}'">
          <div class="row" style="align-items:flex-start;justify-content:space-between;gap:16px">
            <div style="flex:1">
              <div style="margin-bottom:6px">
                <span class="cat">{{ strtoupper($r->category) }}</span>
                <a href="{{ route('public.offering', $r->id) }}" style="font-size:18px;font-weight:600;color:var(--ink)">{{ $pick($r->name_en, $r->name_ms) }} {{ $r->cohort_name }}</a>
              </div>
              <div class="small muted">{{ $r->ptj }} &middot; {{ $modes[$r->delivery_mode] ?? $r->delivery_mode }} &middot; {{ $r->delivery_language }}</div>
            </div>
            <div style="text-align:right">
              <b style="font-size:18px;display:block;color:var(--ink)">{{ $r->fee > 0 ? 'RM '.number_format($r->fee, 2) : ($lang === 'ms' ? 'Percuma' : 'Free') }}</b>
              <div style="margin-top:4px">
                @if(isset($mine[$r->id]))
                  <span class="tag ok">Registered</span>
                @else
                  <span class="tag ok">{{ $r->status === 'open' ? $t('open') : (\Carbon\Carbon::parse($r->reg_opens_on)->lte(today()) ? $t('closed') : $t('opens').' '.\Carbon\Carbon::parse($r->reg_opens_on)->format('d/m/Y')) }}</span>
                @endif
              </div>
            </div>
          </div>
          <div class="row" style="border-top:1px solid var(--hairline);padding-top:10px;margin-top:4px;font-size:13px;color:var(--muted);justify-content:space-between;align-items:center">
            <div>{{ $t('start') }}: {{ \Carbon\Carbon::parse($r->start_date)->format('d/m/Y') }} &middot; {{ $t('end') }}: {{ \Carbon\Carbon::parse($r->end_date)->format('d/m/Y') }}</div>
            <div><a class="btn ter sm" href="{{ route('public.offering', $r->id) }}">{{ $lang === 'ms' ? 'Lihat butiran' : 'View offering' }} &rarr;</a></div>
          </div>
        </div>
      @empty
        <div class="card muted" style="padding:24px;text-align:center">
          <p style="margin:0 0 8px">{{ $t('none') }}</p>
          <a href="{{ route('public.catalogue') }}" class="btn ter sm">{{ $lang === 'ms' ? 'Kosongkan penapis' : 'Clear all filters' }}</a>
        </div>
      @endforelse

      @if($rows->total() > 0)
        <div style="margin-top:8px">
          {{ $rows->links('pagination.simple') }}
        </div>
      @endif
    </div>
  </div>
</div>

@include('public._foot')
@endsection
