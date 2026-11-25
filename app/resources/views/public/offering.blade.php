@extends('layouts.app')
@section('title', $pick($o->name_en, $o->name_ms))
@section('content')
@guest @include('public._nav') @endguest
@php
  $opens = \Carbon\Carbon::parse($o->reg_opens_on);
  $modeLabel = config('proposals.delivery_modes')[$o->delivery_mode] ?? $o->delivery_mode;
@endphp

<div class="pub-sec">
  @php $isPublic = request()->routeIs('public.*'); @endphp
  <x-breadcrumb :crumbs="[
    (!$isPublic && auth()->check() ? (auth()->user()->hasAnyRole('student') ? 'My learning' : 'Dashboard') : 'Home') => (!$isPublic && auth()->check() ? auth()->user()->homeUrl() : route('public.home')),
    $t('catalogue') => route($isPublic ? 'public.catalogue' : 'catalogue'),
    ($pick($o->name_en, $o->name_ms) . ' ' . $o->cohort_name) => null,
  ]" />

  <div class="title" style="margin-bottom:20px;display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap">
    <div style="display:flex;align-items:flex-start;gap:20px;flex:1;min-width:320px">
      <div style="flex-shrink:0;padding-top:2px">
        <x-badge :category="$o->category" :size="64" :image="$o->badge_image_path ?? null" />
      </div>
      <div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap">
          <span class="cat">{{ strtoupper($o->category) }}</span>
          <span class="tag muted" style="font-size:11px">{{ $o->cohort_name }}</span>
          @if($claims['credit'] !== null)
            <span class="tag ok" style="font-size:11px">{{ $claims['credit'] }} {{ __('credits') }}@if($claims['mqf']) &middot; MQF Level {{ $claims['mqf'] }}@endif</span>
          @endif
        </div>
        <h1 style="margin:0 0 6px;font-size:24px;font-weight:600;color:var(--ink)">{{ $pick($o->name_en, $o->name_ms) }}</h1>
        <div class="sub">{{ $o->ptj_name }} &middot; @if(!empty($o->lecturer_name)){{ $o->lecturer_name }} &middot; @endif{{ $modeLabel }} &middot; {{ $o->delivery_language }}</div>
      </div>
    </div>
    <div class="act" style="flex-shrink:0">
      @if(isset($mine[$o->id]) && auth()->check() && auth()->user()->hasAnyRole('student') && ! auth()->user()->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin'))
        <a class="btn ter" href="{{ route('enrolments.show', $mine[$o->id]) }}">View your registration</a>
      @elseif(auth()->check() && auth()->user()->hasAnyRole('lecturer', 'deputy_dean'))
        <a class="btn ter" href="{{ route('offerings.show', $o->id) }}">{{ __('View cohort') }}</a>
      @elseif($o->status === 'open' && (!auth()->check() || auth()->user()->hasAnyRole('student')))
        <a class="btn" href="{{ route('register', $o->id) }}">{{ $t('register') }}</a>
      @endif
    </div>
  </div>

  @if($o->description)
    <p style="margin-bottom:20px;font-size:15px;line-height:1.6;color:var(--ink-light)">{{ $o->description }}</p>
  @endif

  <div class="row" style="gap:32px;align-items:flex-start">
    <div class="col grow" style="gap:24px">
      <div class="note info" id="recognition" style="flex-direction:column;gap:8px;padding:16px 20px">
        <b style="font-size:15px">{{ $t('recognition') }}</b>
        <span style="line-height:1.5">{{ $statement }}</span>
        @if($claims['credit'] !== null)
          <span><b>{{ $lang === 'ms' ? 'Kredit' : 'Credit' }}:</b> {{ $claims['credit'] }}@if($claims['mqf']) &middot; <b>MQF</b> {{ $claims['mqf'] }}@endif @if($claims['qv_until']) &middot; Quality Verification {{ $lang === 'ms' ? 'sah hingga' : 'valid until' }} {{ \Carbon\Carbon::parse($claims['qv_until'])->format('d/m/Y') }}@endif</span>
        @elseif(!empty($claims['renewal']))
          <span class="muted">{{ $lang === 'ms' ? 'Kredit selepas pembaharuan QV.' : 'Credit after QV renewal.' }}</span>
        @endif
        @if($stages)
          <span style="margin-top:4px;line-height:1.4">{{ $stages }}</span>
        @endif
      </div>

      <div class="panel">
        <div class="hd"><h2>{{ $t('outcomes') }}</h2></div>
        <div class="bd">
          <ul style="padding-left:20px;margin:0;line-height:1.6">
            @foreach($los as $lo)
              <li style="margin-bottom:6px">{{ $pick($lo->text_en, $lo->text_ms) }}</li>
            @endforeach
          </ul>
        </div>
      </div>

      <div class="panel">
        <div class="hd">
          <h2>{{ $t('modules') }}</h2>
          <span class="r">{{ $t('slt') }}: {{ $modules->sum('slt_hours') }} {{ $lang === 'ms' ? 'Jam' : 'Hours' }}<span style="display:none">{{ $modules->sum('slt_hours') }} h</span></span>
        </div>
        <table data-plain>
          <thead>
            <tr>
              <th style="width:48px">#</th>
              <th>{{ $t('modules') }}</th>
              <th style="width:140px">{{ __('Digital Badge') }}</th>
              <th class="num" style="width:120px">{{ $lang === 'ms' ? 'SLT (Jam)' : 'SLT (Hours)' }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($modules as $m)
              <tr>
                <td style="vertical-align:middle">{{ $m->seq }}</td>
                <td style="vertical-align:middle">
                  <div style="font-weight:600">{{ $pick($m->name_en, $m->name_ms) }}</div>
                  @if($m->separately_recognisable)
                    <div class="s muted" style="font-size:11px">{{ __('Separately Recognisable Module') }}</div>
                  @endif
                </td>
                <td style="vertical-align:middle">
                  <div style="display:inline-flex;align-items:center;gap:6px">
                    <x-badge :category="$o->category" :size="24" :image="$o->badge_image_path ?? null" :seq="$m->seq" />
                    <span style="font-size:11px;font-weight:600;color:var(--ink-light)">M{{ $m->seq }} {{ __('Badge') }}</span>
                  </div>
                </td>
                <td class="num" style="vertical-align:middle">{{ $m->slt_hours }} {{ $lang === 'ms' ? 'jam' : 'hours' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
        @if($o->category === 'umc')
          <div class="bd small muted" style="border-top:1px solid var(--hairline);padding:12px 16px">
            {{ $lang === 'ms' ? 'Setiap modul ialah sebahagian daripada keseluruhan UMC. Menamatkan satu modul atau lencana bukan penamatan UMC dan bukan kredit akademik.' : 'Each module is part of the whole UMC. Completing a module or earning a badge is not completion of the UMC and is not academic credit.' }}
            <br><b>{{ $t('staged') }}:</b> {{ $o->staged_completion ? $t('yes') : $t('no') }}
          </div>
        @endif
      </div>

      <div class="panel">
        <div class="hd"><h2>{{ $t('assessment') }}</h2></div>
        <table data-plain>
          <thead>
            <tr>
              <th>{{ $lang === 'ms' ? 'Komponen Pentaksiran' : 'Assessment Component' }}</th>
              <th class="num" style="width:140px">{{ $lang === 'ms' ? 'Pemberat (%)' : 'Weighting (%)' }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($comps as $c)
              @php $weight = mcms_num($c->weight_percent, 1); @endphp
              <tr>
                <td>
                  {{ $c->name_en }}
                  <span style="display:none">{{ $c->name_en }} ({{ $weight }}%)</span>
                </td>
                <td class="num" style="font-weight:600">{{ $weight }}%</td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="bd col" style="border-top:1px solid var(--hairline);padding:14px 16px;gap:8px;font-size:13px">
          @if($o->assessment_standard)
            <div><b>{{ $t('standard') }}:</b> {{ $o->assessment_standard }}</div>
          @endif
          @if(!empty($o->integrity_mechanism))
            <div><b>{{ $lang === 'ms' ? 'Integriti Pentaksiran' : 'Assessment Integrity' }}:</b> {{ $o->integrity_mechanism }}</div>
          @endif
          @if(!empty($o->feedback_method))
            <div><b>{{ $lang === 'ms' ? 'Kaedah Maklum Balas' : 'Feedback Method' }}:</b> {{ $o->feedback_method }}</div>
          @endif
          @if(!empty($o->verification_method))
            <div><b>{{ $lang === 'ms' ? 'Kaedah Pengesahan Kredensial' : 'Credential Verification Method' }}:</b> {{ $o->verification_method }}</div>
          @endif
        </div>
      </div>

      {{-- Restructured "What you receive" Section strictly per credential_types --}}
      @php($credTypes = json_decode($o->credential_types ?? '[]', true) ?: ['badge', 'certificate'])
      <div class="panel">
        <div class="hd" style="display:flex;align-items:center;justify-content:space-between">
          <h2>{{ $lang === 'ms' ? 'Apa yang anda terima' : 'What you receive' }}</h2>
          <span class="tag ok" style="font-size:11px">{{ __('Verifiable Credentials') }}</span>
        </div>
        <div class="bd col" style="gap:20px;padding:20px">
          
          {{-- Digital Badge Showcase --}}
          @if(in_array('badge', $credTypes, true))
          <div style="background:var(--surface);border:1px solid var(--hairline);border-left:4px solid var(--accent);border-radius:2px;padding:20px">
            <div style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap">
              <div style="text-align:center;flex-shrink:0">
                <x-badge :category="$o->category" :size="76" :image="$o->badge_image_path ?? null" />
                <div style="font-size:11px;font-weight:600;margin-top:6px;color:var(--muted)">Open Badges 3.0</div>
              </div>
              <div style="flex:1;min-width:240px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap">
                  <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--ink)">
                    {{ $pick($o->name_en, $o->name_ms) }} {{ $lang === 'ms' ? 'Lencana Digital' : 'Digital Badge' }}
                  </h3>
                  <span class="tag ok" style="font-size:10px">{{ strtoupper($o->category) }}</span>
                </div>
                <div class="small muted" style="line-height:1.6;margin-bottom:12px;color:var(--muted)">
                  <b>{{ $lang === 'ms' ? 'Piawaian' : 'Standard' }}:</b> Open Badges 3.0 &middot; W3C Verifiable Credential (Ed25519 Cryptographic Proof)<br>
                  <b>{{ $lang === 'ms' ? 'Pengeluar' : 'Issuer' }}:</b> Universiti Teknikal Malaysia Melaka (UTeM) &middot; CODL<br>
                  <b>{{ $lang === 'ms' ? 'Kebolehgunaan' : 'Portability' }}:</b> {{ $lang === 'ms' ? 'Boleh dikongsi ke LinkedIn, resume digital, dan dompet kelayakan digital.' : 'Shareable to LinkedIn, digital CVs, and verifiable credential wallets.' }}
                </div>

                @if($modules->count() > 0)
                  <div style="border-top:1px dashed var(--hairline);padding-top:12px">
                    <div style="font-size:12px;font-weight:600;color:var(--ink);margin-bottom:8px">
                      {{ $lang === 'ms' ? 'Lencana Digital Modul yang Ditawarkan:' : 'Modular Digital Badges Awarded:' }}
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:12px">
                      @foreach($modules as $m)
                        <div style="display:inline-flex;align-items:center;gap:8px;background:var(--card-bg);border:1px solid var(--hairline);border-radius:2px;padding:4px 10px">
                          <x-badge :category="$o->category" :size="28" :image="$o->badge_image_path ?? null" :seq="$m->seq" />
                          <span style="font-size:12px;font-weight:500;color:var(--ink)">
                            M{{ $m->seq }}: {{ $pick($m->name_en, $m->name_ms) }}
                          </span>
                        </div>
                      @endforeach
                    </div>
                    <div class="s muted" style="font-size:11px;margin-top:6px;color:var(--muted)">
                      {{ $lang === 'ms' ? 'Setiap modul memberikan lencana digitalnya sendiri setelah pentaksiran disahkan.' : 'Each module earns its own digital badge upon verified assessment completion.' }}
                    </div>
                  </div>
                @endif
              </div>
            </div>
          </div>
          @endif

          {{-- Awarded Credentials Breakdown Grid --}}
          <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px">
            
            {{-- Certificate --}}
            @if(in_array('certificate', $credTypes, true))
            <div style="border:1px solid var(--hairline);border-radius:2px;padding:16px;background:var(--surface)">
              <div style="font-size:11px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
                {{ $lang === 'ms' ? 'Sijil Rasmi' : 'Official Certificate' }}
              </div>
              <div style="font-weight:600;font-size:14px;color:var(--ink);margin-bottom:6px">
                {{ $lang === 'ms' ? 'Sijil Micro-Credential UTeM' : 'UTeM Micro-Credential Certificate' }}
              </div>
              <div class="small muted" style="line-height:1.5;color:var(--muted)">
                {{ $lang === 'ms' ? 'Sijil digital beresolusi tinggi dengan kod pengesahan unik dan kod QR untuk semakan majikan.' : 'High-resolution digital certificate with a unique immutable verification code and QR code for employer verification.' }}
              </div>
            </div>
            @endif

            {{-- Digital Badge Card if not already top showcase --}}
            @if(in_array('badge', $credTypes, true) && !in_array('certificate', $credTypes, true))
            <div style="border:1px solid var(--hairline);border-radius:2px;padding:16px;background:var(--surface)">
              <div style="font-size:11px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
                {{ $lang === 'ms' ? 'Lencana Digital' : 'Digital Badge' }}
              </div>
              <div style="font-weight:600;font-size:14px;color:var(--ink);margin-bottom:6px">
                {{ $lang === 'ms' ? 'Lencana Open Badges 3.0' : 'Open Badges 3.0 Badge' }}
              </div>
              <div class="small muted" style="line-height:1.5;color:var(--muted)">
                {{ $lang === 'ms' ? 'Lencana boleh disahkan secara kriptografi dengan bukti W3C Verifiable Credentials.' : 'Cryptographically verifiable badge with W3C Verifiable Credentials proof.' }}
              </div>
            </div>
            @endif

            {{-- Academic Credit / ACR (non-MCE only) --}}
            @if($o->category !== 'mce')
            <div style="border:1px solid var(--hairline);border-radius:2px;padding:16px;background:var(--surface)">
              <div style="font-size:11px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">
                {{ $lang === 'ms' ? 'Pengiktirafan Akademik' : 'Academic Recognition' }}
              </div>
              @if($claims['credit'] !== null)
                <div style="font-weight:600;font-size:14px;color:var(--ink);margin-bottom:6px">
                  {{ $claims['credit'] }} {{ __('credits') }}@if($claims['mqf']) &middot; MQF Level {{ $claims['mqf'] }}@endif
                </div>
                <div class="small muted" style="line-height:1.5;color:var(--muted)">
                  {{ $lang === 'ms' ? 'Rekod Kredit Akademik (ACR) dikeluarkan dan layak didepositkan ke Bank Kredit Akademik (ABC) UTeM (sah 10 tahun).' : 'Academic Credit Record (ACR) issued and eligible for deposit into the UTeM Academic Bank of Credits (ABC) with 10-year validity.' }}
                </div>
              @else
                <div style="font-weight:600;font-size:14px;color:var(--ink);margin-bottom:6px">
                  {{ $lang === 'ms' ? 'Pembangunan Kemahiran Profesional' : 'Professional Skill Development' }}
                </div>
                <div class="small muted" style="line-height:1.5;color:var(--muted)">
                  {{ $lang === 'ms' ? 'Sijil penamatan kompetensi (tiada kredit akademik dan tiada rekod ACR).' : 'Competency certificate of completion (no academic credit and no ACR record).' }}
                </div>
              @endif
            </div>
            @endif

          </div>

        </div>
      </div>

      @if($o->prerequisites || $o->access_period || $o->completion_conditions || $o->learning_support || !empty($course))
        <div class="panel">
          <div class="hd"><h2>{{ $lang === 'ms' ? 'Syarat dan sokongan' : 'Requirements and support' }}</h2></div>
          <div class="bd col" style="gap:10px">
            @if($o->prerequisites)<div><b>{{ $t('prereq') }}:</b> {{ $o->prerequisites }}</div>@endif
            @if($o->access_period)<div><b>{{ $t('access') }}:</b> {{ $o->access_period }}</div>@endif
            @if($o->completion_conditions)<div><b>{{ $t('completion') }}:</b> {{ $o->completion_conditions }}</div>@endif
            @if($o->learning_support)<div><b>{{ $t('support') }}:</b> {{ $o->learning_support }}</div>@endif
            @if(!empty($course))
              <div><b>{{ $lang === 'ms' ? 'Kursus Induk Ijazah' : 'Parent Degree Course' }}:</b> {{ $course->code }} {{ $course->name }} ({{ $course->credit }} {{ __('credits') }})</div>
            @endif
          </div>
        </div>
      @endif
    </div>

    <div class="card col" style="width:320px;position:sticky;top:72px;gap:16px;box-shadow:var(--shadow, 0 2px 6px rgba(0,0,0,0.08))">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div>
          <div class="small muted">{{ $t('fee') }}</div>
          <b style="font-size:24px;font-weight:400;color:var(--ink)">{{ $o->fee > 0 ? 'RM '.number_format($o->fee, 2) : ($lang === 'ms' ? 'Percuma' : 'Free') }}</b>
        </div>
        <div style="flex-shrink:0">
          <x-badge :category="$o->category" :size="48" :image="$o->badge_image_path ?? null" />
        </div>
      </div>

      <dl class="dl" style="grid-template-columns:110px 1fr;gap:8px;font-size:13px;border-top:1px solid var(--hairline);border-bottom:1px solid var(--hairline);padding:12px 0;margin:0">
        <dt>{{ $lang === 'ms' ? 'Institusi' : 'Institution' }}</dt><dd>UTeM (CODL)</dd>
        @if(!empty($o->ptj_name))
          <dt>{{ $lang === 'ms' ? 'Fakulti' : 'Faculty' }}</dt><dd>{{ $o->ptj_code ?? $o->ptj_name }}</dd>
        @endif
        @if(!empty($o->lecturer_name))
          <dt>{{ $lang === 'ms' ? 'Pensyarah' : 'Lecturer' }}</dt><dd>{{ $o->lecturer_name }}</dd>
        @endif
        @if(!empty($o->facilitators) && trim((string) $o->facilitators) !== trim((string) ($o->lecturer_name ?? '')))
          <dt>{{ $lang === 'ms' ? 'Fasilitator' : 'Facilitators' }}</dt><dd>{{ $o->facilitators }}</dd>
        @endif
        <dt>{{ $t('mode') }}</dt><dd>{{ $modeLabel }}</dd>
        <dt>{{ $t('language') }}</dt><dd>{{ $o->delivery_language }}</dd>
        @if(!empty($o->offering_period))
          <dt>{{ $lang === 'ms' ? 'Tempoh' : 'Duration' }}</dt><dd>{{ $o->offering_period }}</dd>
        @endif
        <dt>{{ $t('start') }}</dt><dd>{{ \Carbon\Carbon::parse($o->start_date)->format('d/m/Y') }}</dd>
        <dt>{{ $t('end') }}</dt><dd>{{ \Carbon\Carbon::parse($o->end_date)->format('d/m/Y') }}</dd>
        <dt>{{ $t('capacity') }}</dt><dd>{{ $o->capacity }} {{ $lang === 'ms' ? 'tempat' : 'places' }}</dd>
        <dt>{{ $lang === 'ms' ? 'Kredit' : 'Credit' }}</dt><dd>{{ $claims['credit'] !== null ? $claims['credit'].' credits' : ($lang === 'ms' ? 'Tiada' : 'None') }}</dd>
        @if(!empty($claims['mqf']))
          <dt>MQF</dt><dd>Level {{ $claims['mqf'] }}</dd>
        @endif
        @if(!empty($course))
          <dt>{{ $lang === 'ms' ? 'Kursus' : 'Course' }}</dt><dd>{{ $course->code }}</dd>
        @endif
        <dt>{{ $t('credential') }}</dt><dd>{{ implode(', ', $credentials) ?: '-' }}</dd>
      </dl>

      <div>
        @if(isset($mine[$o->id]) && auth()->check() && auth()->user()->hasAnyRole('student') && ! auth()->user()->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin'))
          <a class="btn ter" href="{{ route('enrolments.show', $mine[$o->id]) }}" style="width:100%;text-align:center">View your registration</a>
        @elseif(auth()->check() && auth()->user()->hasAnyRole('lecturer', 'deputy_dean'))
          <a class="btn ter" href="{{ route('offerings.show', $o->id) }}" style="width:100%;text-align:center">{{ __('View cohort') }}</a>
        @elseif($o->status !== 'open')
          <span class="btn dis" style="width:100%;text-align:center">{{ $t('closed') }}</span>
        @endif
        <div class="small muted" style="margin-top:8px;line-height:1.4">
          {{ $lang === 'ms' ? 'Pendaftaran dibuka: '.$opens->format('d/m/Y').' hingga '.\Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y') : 'Registration open: '.$opens->format('d/m/Y').' to '.\Carbon\Carbon::parse($o->reg_closes_on)->format('d/m/Y') }}
        </div>
      </div>
    </div>
  </div>
</div>

@if($isPublic) @include('public._foot') @endif
@endsection
