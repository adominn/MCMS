@extends('layouts.app')
@section('title', __('Credentials'))
@section('content')
<div class="title">
  <div>
    <h1>{{ __('Credentials and Badges') }}</h1>
    <div class="sub">{{ __('Certificates and Digital Credentials issued to you. Academic credit is recorded separately on the credit ledger.') }}</div>
  </div>
</div>

<!-- Carbon Tabs for Clean Organization -->
<div class="tabs" style="margin-bottom:20px;border-bottom:1px solid var(--rule);display:flex;gap:24px" role="tablist">
  <button type="button" class="tab-btn active" data-tab="tab-credentials" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid var(--accent, #1e2a6e);color:var(--accent, #1e2a6e);cursor:pointer">
    {{ __('Micro-Credential Certificates') }} ({{ $rows->count() }})
  </button>
  <button type="button" class="tab-btn" data-tab="tab-badges" style="padding:10px 4px;font-weight:600;font-size:14px;background:none;border:none;border-bottom:2px solid transparent;color:var(--ink-muted);cursor:pointer">
    {{ __('Module Badges') }} ({{ $badges->count() }})
  </button>
</div>

<!-- Tab 1: Full Micro-Credential Certificates -->
<div id="tab-credentials" class="tab-pane">
  <div class="panel">
    <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <h2 style="margin:0">{{ __('Micro-Credential Certificates') }}</h2>
        <div class="s muted" style="margin-top:2px">{{ __('Full-programme credentials and certificates awarded upon confirmed completion.') }}</div>
      </div>
      <span class="r">
        <span class="s muted">{{ __(':count certificates', ['count' => $rows->count()]) }}</span>
      </span>
    </div>
    <table id="credentials-table">
      <thead>
        <tr>
          <th style="min-width:240px">{{ __('Credential / Programme') }}</th>
          <th>{{ __('Issued Date') }}</th>
          <th>{{ __('Completion Result') }}</th>
          <th>{{ __('Public Verification') }}</th>
          <th>{{ __('Status') }}</th>
          <th class="num" style="min-width:130px">{{ __('Actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        @php($x = json_decode($r->content, true))
        <tr data-href="{{ route('credentials.show', $r->id) }}">
          <td>
            <div style="display:flex;align-items:center;gap:12px">
              <x-badge :category="$x['category'] ?? ''" :size="36" />
              <div>
                <div style="display:flex;align-items:center;gap:6px">
                  <x-cat :c="$x['category'] ?? ''" />
                  <a href="{{ route('credentials.show', $r->id) }}" style="font-weight:700;color:var(--ink)">{{ $x['name']['en'] ?? 'Credential' }}</a>
                </div>
                <span class="s muted" style="margin-top:2px;display:block"><code>{{ $r->number }}</code></span>
              </div>
            </div>
          </td>
          <td class="s">
            <div>{{ \Carbon\Carbon::parse($r->issued_at)->format('d/m/Y') }}</div>
            <div class="muted">{{ \Carbon\Carbon::parse($r->issued_at)->diffForHumans() }}</div>
          </td>
          <td>
            <span class="tag ok" style="font-size:11px">{{ __($x['result'] ?? 'Complete') }}</span>
          </td>
          <td>
            <a href="{{ route('verify.show', $r->public_code) }}" target="_blank" title="{{ __('Verify certificate on public portal') }}">
              <code class="vc">{{ $r->public_code }}</code> <span class="s" style="margin-left:2px">&nearr;</span>
            </a>
          </td>
          <td>
            <x-pill :s="$r->status" :label="['valid' => 'Issued', 'corrected' => 'Corrected', 'replaced' => 'Replaced', 'revoked' => 'Revoked'][$r->status] ?? $r->status" />
          </td>
          <td class="num">
            <a href="{{ route('credentials.show', $r->id) }}" class="btn ter sm" title="{{ __('View credential certificate and details') }}">
              <span class="btn-lbl">{{ __('View details') }} &rarr;</span>
            </a>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted" style="text-align:center;padding:36px">{{ __('No credential yet. A certificate and Digital Credential are issued after the deputy dean confirms completion.') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Tab 2: Module Digital Badges (Searchable, Sortable Data Table) -->
<div id="tab-badges" class="tab-pane" style="display:none">
  <div class="panel" style="margin-bottom:16px;background:var(--bg-subtle, #f4f4f4)">
    <div class="bd" style="padding:14px 18px;font-size:13px;line-height:1.5;color:var(--ink-muted)">
      <i class="bi bi-info-circle" style="margin-right:6px;color:var(--accent, #1e2a6e)"></i>
      {{ __('A Digital Badge certifies modular achievement upon passing individual assessments. It allows you to share verified skills on professional networks even before completing the full course.') }}
    </div>
  </div>

  <div class="panel">
    <div class="hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <h2 style="margin:0">{{ __('Earned Module Badges') }}</h2>
        <div class="s muted" style="margin-top:2px">{{ __('Digital badges conforming to 1EdTech Open Badges 3.0 standard.') }}</div>
      </div>
      <span class="r">
        <span class="s muted">{{ __(':count badges earned', ['count' => $badges->count()]) }}</span>
      </span>
    </div>
    <table id="module-badges-table">
      <thead>
        <tr>
          <th style="min-width:280px">{{ __('Badge & Module') }}</th>
          <th>{{ __('Micro-credential Course') }}</th>
          <th>{{ __('Achievement Date') }}</th>
          <th>{{ __('Standards & SLT') }}</th>
          <th class="num" style="min-width:180px">{{ __('Actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($badges as $b)
        <tr data-href="{{ route('verify.show', $b->code) }}">
          <td>
            <div style="display:flex;align-items:center;gap:12px">
              <x-badge :category="$b->mc_category ?? 'umc'" :size="38" />
              <div>
                <a href="{{ route('verify.show', $b->code) }}" target="_blank" style="font-weight:700;color:var(--ink)">
                  {{ __('Module :seq: :name', ['seq' => $b->seq, 'name' => $b->name_en]) }}
                </a>
                <div class="s muted" style="margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                  <code class="vc" style="font-size:11px">{{ $b->code }}</code>
                  @if(!empty($b->external_id))
                    &middot; <span>UCreds {{ $b->external_id }}</span>
                  @endif
                </div>
              </div>
            </div>
          </td>
          <td>
            <div style="font-weight:600;color:var(--ink)">{{ $b->course_name }}</div>
            <div class="s muted">
              <span class="tag neutral" style="font-size:10px">{{ strtoupper($b->mc_category ?? 'UMC') }}</span>
            </div>
          </td>
          <td class="s">
            @if(!empty($b->badge_issued_at) || !empty($b->achieved_at))
              <div>{{ \Carbon\Carbon::parse($b->badge_issued_at ?? $b->achieved_at)->format('d M Y') }}</div>
              <div class="muted">{{ \Carbon\Carbon::parse($b->badge_issued_at ?? $b->achieved_at)->diffForHumans() }}</div>
            @else
              <span class="muted">-</span>
            @endif
          </td>
          <td class="s">
            <div style="font-weight:600;font-size:12px;color:var(--ink)">Open Badges 3.0</div>
            <div class="muted" style="margin-top:2px">{{ !empty($b->slt_hours) ? $b->slt_hours.' SLT hours' : '40 SLT hours' }}</div>
          </td>
          <td class="num">
            <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end">
              @if(!empty($b->external_url))
                <a class="btn sec sm" href="{{ $b->external_url }}" target="_blank" title="{{ __('Open Badges / UCreds Backpack') }}">
                  <span class="btn-lbl">{{ __('Backpack') }} &nearr;</span>
                </a>
              @endif
              <a class="btn ter sm" href="{{ route('verify.show', $b->code) }}" target="_blank" title="{{ __('Verify badge authenticity') }}">
                <span class="btn-lbl">{{ __('Verify') }} &rarr;</span>
              </a>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="muted" style="text-align:center;padding:36px">
            {{ __('No module badges earned yet. When you achieve a module and results are verified, your badge will appear here.') }}
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tabs = document.querySelectorAll('.tab-btn');
  tabs.forEach(btn => {
    btn.addEventListener('click', function() {
      tabs.forEach(t => {
        t.classList.remove('active');
        t.style.borderBottomColor = 'transparent';
        t.style.color = 'var(--ink-muted)';
      });
      document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
      
      this.classList.add('active');
      this.style.borderBottomColor = 'var(--accent, #1e2a6e)';
      this.style.color = 'var(--accent, #1e2a6e)';
      const target = document.getElementById(this.getAttribute('data-tab'));
      if (target) target.style.display = 'block';
    });
  });
});
</script>
@endsection
