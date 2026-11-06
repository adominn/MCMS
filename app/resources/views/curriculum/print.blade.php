@extends('layouts.print')
@section('title', 'Proposal: ' . ($v->name_en ?: 'Micro-Credential'))
@section('doc_title', 'Proposal for a Micro-Credential')
@section('doc_bm', 'Lampiran A')
@section('doc_ref', 'APPENDIX A · MC-' . $v->micro_credential_id . ' v' . $v->version_no)
@section('doc_file', 'proposal-mc-' . $v->micro_credential_id . '-v' . $v->version_no . '.pdf')
@section('content')

@php
  $sltSum = $modules->sum('slt_hours');
  $weightsSum = $comps->sum('weight_percent');
  $cat = strtoupper($v->category ?? 'UMC');
  $ptjName = $ptj ? $ptj->name : ($v->ptj_code ?? 'FTMK');
  $deanName = $dean ?: 'Deputy Dean (Academic)';
@endphp

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <h1 style="font-size:18pt;margin:0 0 4px">Appendix A: {{ $v->name_en ?: 'Untitled micro-credential' }}</h1>
  <div class="small muted">
    {{ $cat }} &middot; Version {{ $v->version_no }} &middot; {{ $ptjName }} &middot; Proposer: {{ $v->owner_name }}
  </div>
</div>

{{-- 1. Basics --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('1. Basics') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Title (English)') }}</dt>
      <dd><b>{{ $v->name_en ?: '–' }}</b></dd>

      <dt style="font-size:9pt">{{ __('Title (Bahasa Melayu)') }}</dt>
      <dd>{{ $v->name_ms ?: 'Not filled in' }}</dd>

      <dt style="font-size:9pt">{{ __('Category') }}</dt>
      <dd>{{ $cat }}{{ $course ? ', parent course ' . $course->code . ' (' . $course->name . ')' : '' }}</dd>

      <dt style="font-size:9pt">{{ __('Proposing PTj') }}</dt>
      <dd>{{ $ptjName }}</dd>

      <dt style="font-size:9pt">{{ __('Proposer') }}</dt>
      <dd>{{ $v->owner_name }}</dd>

      <dt style="font-size:9pt">{{ __('Field / Discipline') }}</dt>
      <dd>{{ $v->discipline ?: 'Computing and ICT' }}</dd>

      <dt style="font-size:9pt">{{ __('Target group') }}</dt>
      <dd>{{ $v->target_group ?: 'Not filled in' }}</dd>

      <dt style="font-size:9pt">{{ __('Mode and language') }}</dt>
      <dd>{{ ucfirst($v->delivery_mode ?? 'Online, facilitated') }}, {{ ucfirst($v->delivery_language ?? 'English') }}</dd>

      <dt style="font-size:9pt">{{ __('Offering period') }}</dt>
      <dd>{{ $v->offering_period ?: ($v->category === 'mce' ? '2 days' : '14 weeks') }}</dd>
    </dl>
  </div>
</div>

{{-- 2. Justification --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('2. Justification') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Need') }}</dt>
      <dd>{{ $v->need ?: 'Not filled in' }}</dd>

      <dt style="font-size:9pt">{{ __('Evidence') }}</dt>
      <dd>
        @php
          $evTypes = is_string($v->evidence_types) ? json_decode($v->evidence_types, true) : ($v->evidence_types ?? []);
        @endphp
        @if(!empty($evTypes))
          {{ implode(', ', array_map(fn($k) => config('proposals.evidence_types')[$k] ?? $k, (array)$evTypes)) }}
        @else
          Industry advisory panel minutes, market survey, or department review.
        @endif
      </dd>

      <dt style="font-size:9pt">{{ __('What is new / Added value') }}</dt>
      <dd>{{ $v->added_value ?: 'Not filled in' }}</dd>
    </dl>
  </div>
</div>

{{-- 3. Learning outcomes --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('3. Learning outcomes') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    @if($los->isNotEmpty())
      <ol style="margin:0;padding-left:20px;font-size:10pt;line-height:1.6">
        @foreach($los as $lo)
          <li>{{ $lo->text_en ?: $lo->text_ms }}@if($lo->text_ms && $lo->text_en !== $lo->text_ms)<div class="small muted">{{ $lo->text_ms }}</div>@endif</li>
        @endforeach
      </ol>
    @else
      <div class="small muted">No learning outcomes entered yet.</div>
    @endif
  </div>
</div>

{{-- 4. Modules and learning time (SLT) --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('4. Modules and learning time (SLT)') }}</h2></div>
  @if($v->category === 'umc')
    @php
      $modelKey = $v->credit_model;
      $modelMeta = config("proposals.unbundling_models.$modelKey");
    @endphp
    <div style="padding:10px 16px;background:#f8f9fa;border-bottom:1px solid var(--border);font-size:9.5pt">
      <div style="margin-bottom:4px">
        <b>{{ __('Model Penyahhimpunan UMC (Unbundling Model):') }}</b>
        @if($modelMeta)
          <span style="font-weight:700;color:{{ $modelMeta['badge_color'] }}">
            ({{ $modelMeta['letter'] }}) {{ $modelMeta['name_ms'] }} &middot; {{ $modelMeta['name_en'] }}
          </span>
        @else
          <span class="muted">{{ $v->credit_model ?: '-' }}</span>
        @endif
      </div>
      @if($modelMeta)
        <div style="font-size:8.5pt;color:var(--muted);line-height:1.4">
          <i>{{ $modelMeta['desc_ms'] }}</i> &middot; {{ $modelMeta['rule_ms'] }}
        </div>
      @endif
    </div>
  @endif
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Module') }}</th>
          @if($v->credit_model === 'separate_assessment')<th>{{ __('Type') }}</th>@endif
          <th class="num">{{ __('Hours') }}</th>
          <th class="num">{{ __('Share') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($modules as $m)
          <tr>
            <td><b>{{ $m->name_en ?: ('Module ' . $m->seq) }}</b>@if($m->name_ms && $m->name_ms !== $m->name_en) <span class="muted" style="font-weight:normal;font-size:11px">({{ $m->name_ms }})</span>@endif</td>
            @if($v->credit_model === 'separate_assessment')
              <td>
                <span style="font-size:8.5pt;font-weight:600;color:{{ $m->is_assessment ? '#6929c4' : '#0043ce' }}">
                  {{ $m->is_assessment ? __('Assessment Module') : __('Learning Module') }}
                </span>
              </td>
            @endif
            <td class="num">{{ $m->slt_hours }} h</td>
            <td class="num">{{ $sltSum > 0 ? number_format(($m->slt_hours / $sltSum) * 100, 1) : 0 }}%</td>
          </tr>
        @empty
          <tr><td colspan="3" class="muted" style="text-align:center">No modules configured.</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr style="font-weight:600;border-top:2px solid var(--border)">
          <td>{{ __('Total Student Learning Time (SLT)') }}</td>
          @if($v->credit_model === 'separate_assessment')<td></td>@endif
          <td class="num">{{ $sltSum }} h</td>
          <td class="num">100.0%</td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div style="font-size:9pt;color:var(--muted);padding:8px 16px">
    @if($course && $sltSum === $course->slt_hours)
      Total equals the declared parent course learning time of {{ $course->slt_hours }} hours ({{ $course->credit }} credits at 40 hours per credit).
    @elseif($sltSum > 0)
      Equivalent to {{ number_format($sltSum / 40, 1) }} academic credit(s) based on 40 SLT hours per credit.
    @endif
  </div>
</div>

{{-- 5. Assessment weightings --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('5. Assessment weightings') }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ __('Component') }}</th>
          <th class="num">{{ __('Weight') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($comps as $c)
          <tr>
            <td><b>{{ $c->name_en }}</b></td>
            <td class="num">{{ rtrim(rtrim((string)$c->weight_percent, '0'), '.') }}%</td>
          </tr>
        @empty
          <tr><td colspan="2" class="muted" style="text-align:center">No assessment components configured.</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr style="font-weight:600;border-top:2px solid var(--border)">
          <td>{{ __('Total') }}</td>
          <td class="num">{{ rtrim(rtrim((string)$weightsSum, '0'), '.') }}%</td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div style="font-size:9pt;color:var(--muted);padding:8px 16px">
    Pass mark: <b>50%</b> &middot; Integrity: {{ $v->integrity_mechanism ?: 'Identity checked at UCreds sign-in.' }} &middot; Feedback: {{ $v->feedback_method ?: 'Written feedback within 10 working days of each component.' }}
  </div>
</div>

{{-- 6. Alignment of outcomes to assessment --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('6. Alignment of outcomes to assessment') }}</h2></div>
  @if($los->isNotEmpty() && $comps->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ __('Outcome') }}</th>
            @foreach($comps as $c)
              <th class="num">{{ $c->name_en }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($los as $idx => $lo)
            <tr>
              <td><b>LO{{ $idx + 1 }}</b>: {{ \Illuminate\Support\Str::limit($lo->text_en ?: $lo->text_ms, 60) }}</td>
              @foreach($comps as $c)
                @php
                  $linked = isset($links[$c->id]) && $links[$c->id]->contains('learning_outcome_id', $lo->id);
                @endphp
                <td class="num">
                  @if($linked)
                    <span class="tag sm ok">Assessed</span>
                  @else
                    <span class="muted">&ndash;</span>
                  @endif
                </td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="bd" style="padding:12px 16px;font-size:9.5pt;color:var(--muted)">No learning outcomes or assessment components to align.</div>
  @endif
</div>

{{-- 7. Credential design --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('7. Credential design') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Digital Credential') }}</dt>
      <dd>Yes (Open Badges 3.0 / W3C Verifiable Credential)</dd>

      <dt style="font-size:9pt">{{ __('Certificate') }}</dt>
      <dd>Yes (Official bilingual UTeM A4 landscape certificate)</dd>

      <dt style="font-size:9pt">{{ __('Credit (proposed)') }}</dt>
      <dd>{{ $v->proposed_credit ? $v->proposed_credit . ' credit(s)' : ($course ? $course->credit . ' credit(s)' : 'None') }}</dd>

      <dt style="font-size:9pt">{{ __('MQF level (proposed)') }}</dt>
      <dd>{{ $v->proposed_mqf_level ? 'MQF level ' . $v->proposed_mqf_level : ($cat === 'UMC' ? 'MQF level 6 (proposed)' : ($cat === 'SAMC' ? 'MQF level 4 (proposed)' : 'None')) }}</dd>

      <dt style="font-size:9pt">{{ __('Quality Verification') }}</dt>
      <dd>{{ $v->qv_planned ? 'Will apply for Quality Verification' : ($cat === 'SAMC' ? 'Will apply' : 'Not applicable') }}</dd>

      <dt style="font-size:9pt">{{ __('Public Verification') }}</dt>
      <dd>Public verification portal (https://verify.utem.edu.my/)</dd>
    </dl>
  </div>
</div>

{{-- 8. Delivery --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('8. Delivery') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Schedule') }}</dt>
      <dd>{{ $v->delivery_mode ?: 'Weekly facilitated session plus self-paced work on UCreds platform' }}</dd>

      <dt style="font-size:9pt">{{ __('Platform') }}</dt>
      <dd>UCreds learning platform</dd>
    </dl>
  </div>
</div>

{{-- 9. Co-developers --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('9. Co-developers') }}</h2></div>
  <div class="bd" style="padding:12px 16px;font-size:10pt">
    {{ __('None declared.') }}
  </div>
</div>

{{-- 10. Declarations and Signatures --}}
<div class="panel" style="margin-bottom:24px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('10. Declarations and Signatures') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <div style="font-size:10pt;margin-bottom:16px">
      <b>{{ __('Intellectual property declaration:') }}</b> The proposer confirms that material in this micro-credential is original to UTeM or used with written permission. Third-party material is identified.
    </div>

    <div style="display:flex;justify-content:space-between;margin-top:24px;padding-top:16px;border-top:1px dashed var(--border)">
      <div style="width:45%">
        <div style="height:36px;border-bottom:1px solid var(--ink, #000);margin-bottom:4px"></div>
        <div style="font-size:10pt"><b>{{ $v->owner_name }}</b></div>
        <div class="small muted">Proposer / Lecturer, {{ $ptjName }}</div>
        <div class="small muted">{{ now()->format('d/m/Y') }}</div>
      </div>
      <div style="width:45%">
        <div style="height:36px;border-bottom:1px solid var(--ink, #000);margin-bottom:4px"></div>
        <div style="font-size:10pt"><b>{{ $deanName }}</b></div>
        <div class="small muted">Deputy Dean (Academic), {{ $ptjName }}</div>
        <div class="small muted">Endorsement / Approval</div>
      </div>
    </div>
  </div>
</div>

@endsection
