@extends('layouts.print')
@section('title', 'Proposal: ' . ($v->name_en ?: 'Micro-Credential'))
@section('doc_title', 'Proposal for a Micro-Credential')
@section('doc_bm', 'Borang Cadangan Micro-Credential')
@section('doc_ref', 'MC-' . $v->micro_credential_id . ' v' . $v->version_no)
@section('doc_file', 'proposal-mc-' . $v->micro_credential_id . '-v' . $v->version_no . '.pdf')
@section('content')

@php
  $isMs = app()->getLocale() === 'ms';
  $sltSum = (int) $modules->sum('slt_hours');
  $weightsSum = $comps->sum('weight_percent');
  $cat = strtoupper($v->category ?? 'UMC');
  $ptjName = $ptj ? $ptj->name : ($v->ptj_code ?? 'FTMK');
  $deanName = $dean ?: ($isMs ? 'Timbalan Dekan (Akademik)' : 'Deputy Dean (Academic)');
  $credTypes = is_string($v->credential_types) ? json_decode($v->credential_types, true) : ($v->credential_types ?? []);
  if (!is_array($credTypes)) { $credTypes = []; }
@endphp

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <div class="small muted" style="text-transform:uppercase;letter-spacing:0.5px;font-weight:600;color:var(--accent, #0052ff);margin-bottom:2px">
    {{ $isMs ? 'Dokumen Cadangan Rasmi Micro-Credential UTeM' : 'UTeM Official Micro-Credential Proposal' }}
  </div>
  <h1 style="font-size:18pt;margin:0 0 4px">{{ ($isMs && $v->name_ms) ? $v->name_ms : ($v->name_en ?: 'Untitled micro-credential') }}</h1>
  <div class="small muted">
    {{ $cat }} &middot; {{ $isMs ? 'Versi' : 'Version' }} {{ $v->version_no }} &middot; {{ $ptjName }} &middot; {{ $isMs ? 'Pemohon:' : 'Proposer:' }} {{ $v->owner_name }}
  </div>
</div>

{{-- 1. Basics --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '1. Maklumat Asas Penawaran' : '1. Basic Offering Information' }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Title (English)') }}</dt>
      <dd><b>{{ $v->name_en ?: '–' }}</b></dd>

      <dt style="font-size:9pt">{{ __('Title (Bahasa Melayu)') }}</dt>
      <dd>{{ $v->name_ms ?: '–' }}</dd>

      <dt style="font-size:9pt">{{ __('Category') }}</dt>
      <dd><b>{{ $cat }}</b> ({{ ['umc' => 'Unbundled Micro-Credential', 'samc' => 'Stand-Alone Micro-Credential', 'mce' => 'Micro-Credential Eksekutif'][$v->category] ?? $cat }}){{ $course ? ', parent course ' . $course->code . ' (' . $course->name . ')' : '' }}</dd>

      <dt style="font-size:9pt">{{ __('Proposal Status / Type') }}</dt>
      <dd>{{ $isMs ? (['new' => 'Baharu', 'revision' => 'Semakan', 'reoffer' => 'Penawaran Semula'][$v->proposal_type ?? 'new'] ?? ucfirst($v->proposal_type ?? 'new')) : (['new' => 'New', 'revision' => 'Revision', 'reoffer' => 'Reoffer'][$v->proposal_type ?? 'new'] ?? ucfirst($v->proposal_type ?? 'new')) }}</dd>

      <dt style="font-size:9pt">{{ __('Proposing PTj') }}</dt>
      <dd>{{ $ptjName }}</dd>

      <dt style="font-size:9pt">{{ __('Proposer / Coordinator') }}</dt>
      <dd>{{ $v->owner_name }}@if(!empty($proposer?->position)) <span class="muted">({{ $proposer->position }})</span>@endif</dd>

      <dt style="font-size:9pt">{{ __('Field / Discipline') }}</dt>
      <dd>{{ $v->discipline ?: 'Computing and ICT' }}</dd>

      <dt style="font-size:9pt">{{ __('Target group') }}</dt>
      <dd>{{ $v->target_group ?: 'Students, industry practitioners, and lifelong learners' }}</dd>

      <dt style="font-size:9pt">{{ __('Mode and language') }}</dt>
      <dd>{{ ucfirst($v->delivery_mode ?? 'Online, facilitated') }}, {{ ucfirst($v->delivery_language ?? 'English') }}</dd>

      <dt style="font-size:9pt">{{ __('Offering period') }}</dt>
      <dd>{{ $v->offering_period ?: ($v->category === 'mce' ? '2 days' : '14 weeks') }}</dd>
    </dl>
  </div>
</div>

{{-- 2. Justification --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '2. Asas dan Justifikasi Pembangunan' : '2. Rationale and Development Justification' }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Need') }}</dt>
      <dd>{{ $v->need ?: 'Addressed industry demand and academic skills development.' }}</dd>

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
      <dd>{{ $v->added_value ?: 'Flexible learning pathway and direct competency attainment.' }}</dd>

      <dt style="font-size:9pt">{{ __('Relationship with Existing Courses') }}</dt>
      <dd>{{ $v->existing_equivalents ?: ($isMs ? 'Tiada kursus bertindih secara langsung' : 'Standalone modular delivery without unnecessary overlap.') }}</dd>
    </dl>
  </div>
</div>

{{-- A3. Category Determination --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '3. Penentuan Kategori Micro-Credential' : '3. Category Determination' }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Proposed Category') }}</dt>
      <dd><b>{{ $cat }}</b> &middot; {{ $isMs ? (['umc' => 'Micro-Credential Terungkai (Pembelajaran Formal, terungkai daripada kursus ijazah terakreditasi)', 'samc' => 'Micro-Credential Berdiri Sendiri (Pembelajaran Tidak Formal, kemahiran industri, layak untuk Pengesahan Kualiti)', 'mce' => 'Micro-Credential Eksekutif (Pendidikan berterusan / peningkatan kemahiran eksekutif, tanpa kredit)'][$v->category] ?? $cat) : (['umc' => 'Unbundled Micro-Credential (Formal Learning, unbundled from accredited degree course)', 'samc' => 'Stand-Alone Micro-Credential (Non-Formal Learning, industry skills, eligible for Quality Verification)', 'mce' => 'Micro-Credential Eksekutif (Continuing education / executive upskilling, non-credit)'][$v->category] ?? $cat) }}</dd>

      <dt style="font-size:9pt">{{ __('Category Justification') }}</dt>
      <dd>{{ $v->category_justification ?: ($cat === 'UMC' ? 'Unbundled directly from accredited parent course ' . ($course?->code ?? '') . ' to provide stackable academic credit pathways.' : ($cat === 'SAMC' ? 'Stand-alone competency-focused micro-credential designed for immediate industry and lifelong learner upskilling.' : 'Executive continuous professional development without academic credit accumulation.')) }}</dd>
    </dl>
  </div>
</div>

{{-- A4. Additional Information for UMC --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '4. Maklumat Tambahan bagi UMC' : '4. Additional Information for UMC' }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    @if($v->category === 'umc')
      @php
        $modelKey = $v->credit_model;
        $modelMeta = config("proposals.unbundling_models.$modelKey");
      @endphp
      <dl class="dl" style="font-size:10pt;row-gap:6px">
        <dt style="font-size:9pt">{{ __('Parent Programme') }}</dt>
        <dd>{{ $course ? $course->programme : 'Bachelor of Computer Science (Software Development)' }}</dd>

        <dt style="font-size:9pt">{{ __('Parent Course') }}</dt>
        <dd>{{ $course ? $course->code . ' ' . $course->name : 'Parent Course' }}</dd>

        <dt style="font-size:9pt">{{ __('Programme Accreditation') }}</dt>
        <dd><span class="tag ok">{{ $isMs ? 'Akreditasi Penuh MQA' : 'MQA Full Accreditation' }}</span></dd>

        <dt style="font-size:9pt">{{ __('Parent Course SLT & Credits') }}</dt>
        <dd>{{ $course ? $course->credit . ' credits (' . $course->slt_hours . ' h SLT)' : '3 credits (120 h SLT)' }}</dd>

        <dt style="font-size:9pt">{{ __('Unbundling Model') }}</dt>
        <dd>
          @if($modelMeta)
            <b style="color:{{ $modelMeta['badge_color'] }}">({{ $modelMeta['letter'] }}) {{ $isMs ? $modelMeta['name_ms'] : $modelMeta['name_en'] }}</b>
            <div class="small muted">{{ $isMs ? ($modelMeta['desc_ms'] . ' · ' . $modelMeta['rule_ms']) : ($modelMeta['desc_en'] . ' · ' . $modelMeta['rule_en']) }}</div>
          @else
            {{ $v->credit_model ?: 'Credit-based unbundling' }}
          @endif
        </dd>

        <dt style="font-size:9pt">{{ __('Number of UMC Modules') }}</dt>
        <dd>{{ $modules->count() }} module(s)</dd>

        <dt style="font-size:9pt">{{ __('SLT Alignment Confirmation') }}</dt>
        <dd>
          @if($course && $sltSum === (int) $course->slt_hours)
            <span class="tag ok">Disahkan: Jumlah SLT modul ({{ $sltSum }} jam) menyamai kursus induk ({{ $course->slt_hours }} jam)</span>
          @else
            <span>Jumlah SLT modul: {{ $sltSum }} jam (Kursus induk: {{ $course?->slt_hours ?? 120 }} jam)</span>
          @endif
        </dd>
      </dl>
    @else
      <div class="small muted">{{ __('Not applicable (TB): This section is required for Unbundled Micro-Credentials (UMC) only.') }}</div>
    @endif
  </div>
</div>

{{-- 3. Learning outcomes --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? 'Hasil Pembelajaran' : '3. Learning Outcomes' }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    @if($los->isNotEmpty())
      <ol style="margin:0;padding-left:20px;font-size:10pt;line-height:1.6">
        @foreach($los as $idx => $lo)
          <li>
            <b>LO{{ $idx + 1 }}:</b> {{ ($isMs && $lo->text_ms) ? $lo->text_ms : ($lo->text_en ?: $lo->text_ms) }}
            @if($lo->parent_clo_id)<div class="small muted">{{ $isMs ? 'Penjajaran Kursus Induk:' : 'Parent Course Alignment:' }} CLO{{ $lo->parent_clo_id }}</div>@endif
          </li>
        @endforeach
      </ol>
    @else
      <div class="small muted">{{ $isMs ? 'Tiada hasil pembelajaran dimasukkan.' : 'No learning outcomes entered yet.' }}</div>
    @endif
  </div>
</div>

{{-- 4. Modules and learning time (SLT) --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '5. Reka Bentuk Pembelajaran & Modul' : '5. Modules and Student Learning Time (SLT)' }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:25%">{{ $isMs ? 'Modul' : 'Module' }}</th>
          <th style="width:30%">{{ $isMs ? 'Topik Utama' : 'Key Content' }}</th>
          <th style="width:25%">{{ $isMs ? 'Aktiviti Pembelajaran' : 'Learning Activities' }}</th>
          @if($v->credit_model === 'separate_assessment')<th style="width:10%">{{ $isMs ? 'Jenis' : 'Type' }}</th>@endif
          <th class="num" style="width:10%">{{ $isMs ? 'Jam' : 'Hours' }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($modules as $m)
          @php $modActs = $activities[$m->id] ?? collect(); @endphp
          <tr>
            <td>
              <b>{{ ($isMs && $m->name_ms) ? $m->name_ms : ($m->name_en ?: ('Module ' . $m->seq)) }}</b>
            </td>
            <td>
              <div style="font-size:9pt">{{ ($isMs && $m->key_content) ? $m->key_content : ($m->key_content ?: ($m->name_en . ' fundamental principles and applied practices.')) }}</div>
            </td>
            <td>
              @forelse($modActs as $act)
                <div style="font-size:8.5pt">&bull; {{ $act->name_en }} ({{ config('proposals.activity_types.' . $act->activity_type) ?: ucfirst($act->activity_type) }}, {{ $act->slt_hours }}h)</div>
              @empty
                <div style="font-size:8.5pt" class="muted">{{ $isMs ? 'Kuliah, pembelajaran kendiri, dan amali (' . $m->slt_hours . 'j)' : 'Lecture, self-study, and practical tasks (' . $m->slt_hours . 'h)' }}</div>
              @endforelse
            </td>
            @if($v->credit_model === 'separate_assessment')
              <td>
                <span style="font-size:8.5pt;font-weight:600;color:{{ $m->is_assessment ? '#6929c4' : '#0043ce' }}">
                  {{ $m->is_assessment ? ($isMs ? 'Pentaksiran' : 'Assessment') : ($isMs ? 'Pembelajaran' : 'Learning') }}
                </span>
              </td>
            @endif
            <td class="num">{{ $m->slt_hours }} h</td>
          </tr>
        @empty
          <tr><td colspan="{{ $v->credit_model === 'separate_assessment' ? 5 : 4 }}" class="muted" style="text-align:center">{{ $isMs ? 'Tiada modul dikonfigurasikan.' : 'No modules configured.' }}</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr style="font-weight:600;border-top:2px solid var(--border)">
          <td colspan="{{ $v->credit_model === 'separate_assessment' ? 4 : 3 }}">{{ $isMs ? 'Jumlah Masa Pembelajaran Pelajar (SLT)' : 'Total Student Learning Time (SLT)' }}</td>
          <td class="num">{{ $sltSum }} h</td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div style="font-size:9pt;color:var(--muted);padding:8px 16px">
    @if($course && $sltSum === (int) $course->slt_hours)
      {{ $isMs ? 'Jumlah menyamai masa pembelajaran kursus induk sebanyak ' . $course->slt_hours . ' jam (' . $course->credit . ' kredit pada 40 jam setiap kredit).' : 'Total equals the declared parent course learning time of ' . $course->slt_hours . ' hours (' . $course->credit . ' credits at 40 hours per credit).' }}
    @elseif($sltSum > 0)
      {{ $isMs ? 'Bersamaan ' . mcms_num($sltSum / 40) . ' kredit akademik berdasarkan 40 jam SLT setiap kredit. Cadangan kredit: ' . ($v->proposed_credit ? mcms_num($v->proposed_credit) : ($course ? mcms_num($course->credit) : mcms_num($sltSum / 40))) . ' kredit. Tahap MQF disahkan: ' . ($v->proposed_mqf_level ? 'Tahap ' . $v->proposed_mqf_level : ($cat === 'UMC' ? 'Tahap 6' : ($cat === 'SAMC' ? 'Tahap 4' : 'Tiada'))) . '.' : 'Equivalent to ' . mcms_num($sltSum / 40) . ' academic credit(s) based on 40 SLT hours per credit. Proposed credit: ' . ($v->proposed_credit ? mcms_num($v->proposed_credit) : ($course ? mcms_num($course->credit) : mcms_num($sltSum / 40))) . ' credit(s). Verified MQF Level: ' . ($v->proposed_mqf_level ? 'Level ' . $v->proposed_mqf_level : ($cat === 'UMC' ? 'Level 6' : ($cat === 'SAMC' ? 'Level 4' : 'None'))) . '.' }}
    @endif
  </div>
</div>

{{-- 5. Assessment weightings --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '6. Pentaksiran dan Standard Penyempurnaan' : '6. Assessment Weightings and Completion Standards' }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>{{ $isMs ? 'Komponen Pentaksiran' : 'Assessment Component' }}</th>
          <th class="num">{{ $isMs ? 'Pemberat' : 'Weight' }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($comps as $c)
          <tr>
            <td><b>{{ $c->name_en }}</b></td>
            <td class="num">{{ mcms_num($c->weight_percent) }}%</td>
          </tr>
        @empty
          <tr><td colspan="2" class="muted" style="text-align:center">{{ $isMs ? 'Tiada komponen pentaksiran dikonfigurasikan.' : 'No assessment components configured.' }}</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr style="font-weight:600;border-top:2px solid var(--border)">
          <td>{{ $isMs ? 'Jumlah' : 'Total' }}</td>
          <td class="num">{{ mcms_num($weightsSum) }}%</td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div style="font-size:9pt;color:var(--muted);padding:8px 16px">
    <b>{{ $isMs ? 'Standard lulus:' : 'Pass mark:' }}</b> {{ $v->assessment_standard ?: ($isMs ? '50% (Standard minimum lulus kursus mengikut peraturan akademik UTeM)' : '50% (Standard minimum pass mark per UTeM academic regulations)') }} &middot; <b>{{ $isMs ? 'Integriti:' : 'Integrity:' }}</b> {{ $v->integrity_mechanism ?: ($isMs ? 'Identiti disahkan melalui log masuk portal rasmi universiti.' : 'Identity verified via official university portal credentials.') }} &middot; <b>{{ $isMs ? 'Maklum balas:' : 'Feedback:' }}</b> {{ $v->feedback_method ?: ($isMs ? 'Maklum balas bertulis dalam masa 10 hari bekerja bagi setiap komponen.' : 'Written feedback within 10 working days of each component.') }}
  </div>
</div>

{{-- 6. Alignment of outcomes to assessment --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? 'Penjajaran Hasil Pembelajaran' : '6. Alignment of Outcomes to Assessment' }}</h2></div>
  @if($los->isNotEmpty() && $comps->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ $isMs ? 'Hasil Pembelajaran' : 'Outcome' }}</th>
            @foreach($comps as $c)
              <th class="num">{{ $c->name_en }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($los as $idx => $lo)
            <tr>
              <td><b>LO{{ $idx + 1 }}</b>: {{ \Illuminate\Support\Str::limit(($isMs && $lo->text_ms) ? $lo->text_ms : ($lo->text_en ?: $lo->text_ms), 60) }}</td>
              @foreach($comps as $c)
                @php
                  $linked = isset($links[$c->id]) && $links[$c->id]->contains('learning_outcome_id', $lo->id);
                @endphp
                <td class="num">
                  @if($linked)
                    <span class="tag sm ok">{{ $isMs ? 'Ditaksir' : 'Assessed' }}</span>
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
    <div class="bd" style="padding:12px 16px;font-size:9.5pt;color:var(--muted)">{{ $isMs ? 'Tiada hasil pembelajaran atau komponen pentaksiran untuk dijajarkan.' : 'No learning outcomes or assessment components to align.' }}</div>
  @endif
</div>

{{-- 7. Credential design --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '7. Kredensial dan Pengiktirafan' : '7. Credential Design and Recognition' }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Credential Awarded') }}</dt>
      <dd>
        @php
          $credDisplay = [];
          if (in_array('badge', $credTypes, true)) { $credDisplay[] = $isMs ? 'Digital Badge (Open Badges 3.0)' : 'Digital Badge (Open Badges 3.0)'; }
          if (in_array('certificate', $credTypes, true)) { $credDisplay[] = $isMs ? 'Sijil Rasmi UTeM (Sijil A4)' : 'Official UTeM Certificate (A4)'; }
          if (in_array('vc', $credTypes, true) || in_array('digital_credential', $credTypes, true)) { $credDisplay[] = $isMs ? 'Digital Credential (W3C Verifiable Credential)' : 'Digital Credential (W3C Verifiable Credential)'; }
          if ($credDisplay === []) { $credDisplay[] = 'Digital Badge (Open Badges 3.0)'; }
        @endphp
        {{ implode(' &middot; ', $credDisplay) }}
      </dd>

      <dt style="font-size:9pt">{{ __('Achievement Level') }}</dt>
      <dd>{{ $v->achievement_level === 'module' ? ($isMs ? 'Peringkat Modul / Unit Pembelajaran' : 'Module / Learning Unit Level') : ($isMs ? 'Keseluruhan Micro-Credential' : 'Overall Micro-Credential') }}</dd>

      <dt style="font-size:9pt">{{ __('Credit (proposed)') }}</dt>
      <dd>{{ $v->proposed_credit ? mcms_num($v->proposed_credit) . ' ' . ($isMs ? 'kredit' : 'credit(s)') : ($course ? mcms_num($course->credit) . ' ' . ($isMs ? 'kredit' : 'credit(s)') : ($isMs ? 'Tiada' : 'None')) }}</dd>

      <dt style="font-size:9pt">{{ __('MQF level (proposed)') }}</dt>
      <dd>{{ $v->proposed_mqf_level ? ($isMs ? 'Tahap MQF ' . $v->proposed_mqf_level : 'MQF level ' . $v->proposed_mqf_level) : ($cat === 'UMC' ? ($isMs ? 'Tahap MQF 6 (cadangan)' : 'MQF level 6 (proposed)') : ($cat === 'SAMC' ? ($isMs ? 'Tahap MQF 4 (cadangan)' : 'MQF level 4 (proposed)') : ($isMs ? 'Tiada' : 'None'))) }}</dd>

      <dt style="font-size:9pt">{{ __('Quality Verification') }}</dt>
      <dd>{{ $cat === 'SAMC' ? ($v->qv_status ? ucfirst($v->qv_status) : ($v->qv_planned ? ($isMs ? 'Akan dipohon' : 'Will apply for Quality Verification') : ($isMs ? 'Tidak dipohon' : 'Not applied'))) : ($isMs ? 'Tidak berkenaan (TB – UMC / MCE)' : 'Not applicable (TB – UMC / MCE)') }}</dd>

      <dt style="font-size:9pt">{{ __('Public Verification') }}</dt>
      <dd>{{ $v->verification_method ?: 'Public verification portal (https://verify.utem.edu.my/)' }}</dd>

      <dt style="font-size:9pt">{{ __('Recognition Potential') }}</dt>
      <dd>{{ $v->recognition_potential ?: ($isMs ? 'Kelayakan industri dan laluan ijazah bertumpuk (Stackable Degree) di UTeM.' : 'Industry recognition and stackable degree pathways at UTeM.') }}</dd>
    </dl>
  </div>
</div>

{{-- 8. Delivery --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? 'Mod & Jadual Pelaksanaan' : '8. Delivery Mode & Schedule' }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Schedule') }}</dt>
      <dd>{{ $v->delivery_mode ?: 'Weekly facilitated session plus self-paced work on UCreds platform' }}</dd>

      <dt style="font-size:9pt">{{ __('Platform') }}</dt>
      <dd>UCreds / uLearn platform</dd>
    </dl>
  </div>
</div>

{{-- A8. Delivery Readiness Checklist --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '8. Kesediaan Pelaksanaan' : '8. Delivery Readiness Checklist' }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:5%">#</th>
          <th style="width:40%">{{ $isMs ? 'Perkara Semakan' : 'Checklist Item' }}</th>
          <th style="width:15%">{{ $isMs ? 'Status' : 'Status' }}</th>
          <th style="width:40%">{{ $isMs ? 'Catatan / Evidens' : 'Notes / Evidence' }}</th>
        </tr>
      </thead>
      <tbody>
        @php
          $a8Items = [
            'materials' => $isMs ? 'Bahan pembelajaran telah siap dan disemak' : 'Learning materials ready & reviewed',
            'platform' => $isMs ? 'Platform / persekitaran pembelajaran sedia digunakan' : 'Learning platform ready',
            'assessment' => $isMs ? 'Kaedah pentaksiran dan rubrik telah disediakan' : 'Assessment & rubrics ready',
            'facilitators' => $isMs ? 'Tenaga pengajar / fasilitator telah dikenal pasti' : 'Facilitators identified',
            'participant_info' => $isMs ? 'Maklumat peserta dan terma penawaran jelas' : 'Participant info & terms clear',
            'records' => $isMs ? 'Mekanisme rekod dan pensijilan sedia dilaksanakan' : 'Records & credential mechanism ready',
            'accessibility' => $isMs ? 'Aspek kebolehcapaian dan sokongan peserta dipertimbangkan' : 'Accessibility & support considered',
          ];
          $idx = 1;
        @endphp
        @foreach($a8Items as $k => $label)
          @php
            $r = $ready[$k] ?? null;
            $st = $r?->status ?? 'yes';
            $note = $r?->note;
          @endphp
          <tr>
            <td>{{ $idx++ }}</td>
            <td><b>{{ $label }}</b></td>
            <td>
              @if($st === 'yes')
                <span class="tag ok">{{ $isMs ? 'Ya' : 'Ready' }}</span>
              @elseif($st === 'na')
                <span class="tag info">{{ $isMs ? 'TB' : 'N/A' }}</span>
              @else
                <span class="tag warn">{{ $isMs ? 'Belum' : 'Pending' }}</span>
              @endif
            </td>
            <td>{{ $note ?: ($st === 'yes' ? ($isMs ? 'Semakan kendiri lengkap' : 'Self-checked complete') : '—') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- 9. Co-developers --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? 'Rakan Kerjasama / Pembangun Bersama' : '9. Co-developers' }}</h2></div>
  <div class="bd" style="padding:12px 16px;font-size:10pt">
    {{ $isMs ? 'Tiada diisytiharkan. Micro-credential dibangunkan sepenuhnya oleh tenaga akademik UTeM.' : 'None declared. Micro-credential developed entirely in-house by UTeM faculty.' }}
  </div>
</div>

{{-- A9. Review and Approval Sign-Offs --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '9. Semakan dan Kelulusan' : '9. Review and Approval Sign-Offs' }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:25%">{{ $isMs ? 'Peringkat' : 'Stage' }}</th>
          <th style="width:25%">{{ $isMs ? 'Pegawai & Jawatan' : 'Officer & Position' }}</th>
          <th style="width:15%">{{ $isMs ? 'Keputusan' : 'Decision' }}</th>
          <th style="width:15%">{{ $isMs ? 'Tarikh' : 'Date' }}</th>
          <th style="width:20%">{{ $isMs ? 'Catatan / Rujukan' : 'Remarks / Reference' }}</th>
        </tr>
      </thead>
      <tbody>
        {{-- Stage 1 --}}
        <tr>
          <td><b>{{ $isMs ? '1. Semakan Akademik / Teknikal' : '1. Academic / Technical Review' }}</b></td>
          <td>
            @if($stage1)
              <b>{{ $stage1->reviewer_name }}</b><br><span class="muted small">{{ $stage1->reviewer_position ?: ($isMs ? 'Penyemak Kurikulum' : 'Curriculum Reviewer') }}</span>
            @else
              <span class="muted">{{ $isMs ? 'Belum selesai' : 'Not yet recorded' }}</span>
            @endif
          </td>
          <td>
            @if($stage1)
              <span class="tag ok">{{ ucfirst($stage1->recommendation) }}</span>
            @else
              <span class="muted">—</span>
            @endif
          </td>
          <td>{{ $stage1 ? \Carbon\Carbon::parse($stage1->submitted_at)->format('d/m/Y') : '—' }}</td>
          <td>{{ $stage1?->summary ?: '—' }}</td>
        </tr>
        {{-- Stage 2 --}}
        <tr>
          <td><b>{{ $isMs ? '2. Pengesahan PTj / Timbalan Dekan' : '2. Deputy Dean Endorsement' }}</b></td>
          <td>
            @if($stage2)
              <b>{{ $stage2->decider_name }}</b><br><span class="muted small">{{ $stage2->decider_position ?: ($isMs ? 'Timbalan Dekan (Akademik)' : 'Deputy Dean (Academic)') }}</span>
            @else
              <span class="muted">{{ $isMs ? 'Belum selesai' : 'Not yet recorded' }}</span>
            @endif
          </td>
          <td>
            @if($stage2)
              <span class="tag ok">{{ ucfirst($stage2->decision) }}</span>
            @else
              <span class="muted">—</span>
            @endif
          </td>
          <td>{{ $stage2 ? \Carbon\Carbon::parse($stage2->meeting_date ?: $stage2->created_at)->format('d/m/Y') : '—' }}</td>
          <td>{{ $stage2?->meeting_ref ?: ($stage2?->comments ?: '—') }}</td>
        </tr>
        {{-- Stage 3 --}}
        @php($codlV = $codlVerification ?? null)
        <tr>
          <td><b>{{ $isMs ? '3. Semakan CODL' : '3. CODL Verification' }}</b></td>
          <td>
            @if($codlV)
              <b>{{ $codlV->decider_name }}</b><br><span class="muted small">{{ $codlV->decider_position ?: ($isMs ? 'Urus Setia CODL' : 'CODL Secretariat') }}</span>
            @elseif($v->status === 'approved')
              <span class="muted">{{ $isMs ? 'Direkodkan di bawah tadbir urus terdahulu' : 'Recorded under prior governance' }}</span>
            @else
              <span class="muted">{{ $isMs ? 'Belum selesai' : 'Not yet recorded' }}</span>
            @endif
          </td>
          <td>
            @if($codlV)
              <span class="tag ok">{{ ucfirst($codlV->decision) }}</span>
            @elseif($v->status === 'approved')
              <span class="tag ok">{{ $isMs ? 'Disahkan' : 'Verified' }}</span>
            @else
              <span class="muted">—</span>
            @endif
          </td>
          <td>{{ $codlV ? \Carbon\Carbon::parse($codlV->created_at)->format('d/m/Y') : '—' }}</td>
          <td>{{ $codlV?->comments ?: '—' }}</td>
        </tr>
        {{-- Stage 4 --}}
        <tr>
          <td><b>{{ $isMs ? '4. Kelulusan Senat UTeM' : '4. UTeM Senate Approval' }}</b></td>
          <td>
            @if($stage3)
              <b>{{ $stage3->decider_name }}</b><br><span class="muted small">{{ $stage3->decider_position ?: ($isMs ? 'Pihak Berkuasa Akademik (Senat)' : 'Academic Authority (Senate)') }}</span>
            @else
              <span class="muted">{{ $isMs ? 'Belum selesai' : 'Not yet recorded' }}</span>
            @endif
          </td>
          <td>
            @if($stage3)
              <span class="tag ok">{{ ucfirst(str_replace('_', ' ', $stage3->decision)) }}</span>
            @else
              <span class="muted">—</span>
            @endif
          </td>
          <td>{{ $stage3 ? \Carbon\Carbon::parse($stage3->meeting_date ?: $stage3->created_at)->format('d/m/Y') : '—' }}</td>
          <td>{{ $stage3?->meeting_ref ?: ($stage3?->comments ?: '—') }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

{{-- 10. Declarations and Signatures --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ $isMs ? '10. Kawalan Dokumen dan Perubahan' : '10. Declarations and Document Control' }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px;margin-bottom:16px">
      <dt style="font-size:9pt">{{ __('Document No. / Version') }}</dt>
      <dd><b>MC-{{ $v->micro_credential_id }} v{{ $v->version_no }}</b></dd>

      <dt style="font-size:9pt">{{ __('Effective Date') }}</dt>
      <dd>{{ $v->created_at ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y') : '—' }}</dd>

      <dt style="font-size:9pt">{{ __('Change Summary') }}</dt>
      <dd>{{ $v->change_summary ?: ($v->version_no > 1 ? ($isMs ? 'Semakan kurikulum versi ' . $v->version_no : 'Curriculum revision version ' . $v->version_no) : ($isMs ? 'Cadangan penawaran baharu versi asal 1.0.' : 'Original new offering proposal version 1.0.')) }}</dd>

      <dt style="font-size:9pt">{{ __('Material Change?') }}</dt>
      <dd>{{ $v->change_class === 'material' ? ($isMs ? 'Ya (Perubahan Signifikan)' : 'Yes (Material Change)') : ($isMs ? 'Tidak (Biasa / Versi 1.0)' : 'No (Standard / Version 1.0)') }}</dd>

      <dt style="font-size:9pt">{{ __('Follow-up Action') }}</dt>
      <dd>{{ config('proposals.follow_up_actions.' . $v->follow_up_action) ?: ($v->follow_up_action ?: ($isMs ? 'Penawaran kepada kumpulan sasaran & pelaksanaan proses jaminan kualiti.' : 'Offering to target groups and quality assurance execution.')) }}</dd>
    </dl>

    <div style="font-size:10pt;margin-bottom:16px;padding-top:10px;border-top:1px solid var(--border)">
      @if($isMs)
        <b>Perakuan Harta Intelek:</b> Pemohon mengesahkan bahawa bahan pengajaran dalam micro-credential ini adalah karya asli UTeM atau telah mendapat kebenaran bertulis. Bahan pihak ketiga telah dikenal pasti dengan jelas.
      @else
        <b>Intellectual Property Declaration:</b> The proposer confirms that material in this micro-credential is original to UTeM or used with written permission. Third-party material is clearly identified.
      @endif
    </div>

    <div style="display:flex;gap:20px;justify-content:space-between;margin-top:20px;padding-top:16px;border-top:1px dashed var(--border)">
      <div style="flex:1">
        <div style="height:36px;border-bottom:1px solid var(--ink, #000);margin-bottom:4px"></div>
        <div style="font-size:10pt"><b>{{ $v->owner_name }}</b></div>
        <div class="small muted">{{ $proposer?->position ?: ($isMs ? 'Pemohon / Pensyarah' : 'Proposer / Lecturer') }}, {{ $ptjName }}</div>
        <div class="small muted">{{ $v->created_at ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y') : '—' }}</div>
      </div>
      <div style="flex:1">
        <div style="height:36px;border-bottom:1px solid var(--ink, #000);margin-bottom:4px"></div>
        <div style="font-size:10pt"><b>{{ $deanName }}</b></div>
        <div class="small muted">{{ $stage2?->decider_position ?: ($isMs ? 'Timbalan Dekan (Akademik)' : 'Deputy Dean (Academic)') }}, {{ $ptjName }}</div>
        <div class="small muted">{{ $stage2 ? \Carbon\Carbon::parse($stage2->created_at)->format('d/m/Y') : ($isMs ? 'Pengesahan PTj / Endorsan' : 'PTj Endorsement') }}</div>
      </div>
      <div style="flex:1">
        <div style="height:36px;border-bottom:1px solid var(--ink, #000);margin-bottom:4px"></div>
        <div style="font-size:10pt"><b>{{ $codlVerification?->decider_name ?: ($stage3?->decider_name ?: 'CODL & Senate Secretariat') }}</b></div>
        <div class="small muted">{{ $codlVerification?->decider_position ?: ($stage3?->decider_position ?: ($isMs ? 'Semakan CODL & Urus Setia Senat' : 'CODL Verification & Senate Approval')) }}</div>
        <div class="small muted">{{ $stage3 ? \Carbon\Carbon::parse($stage3->meeting_date ?: $stage3->created_at)->format('d/m/Y') : ($codlVerification ? \Carbon\Carbon::parse($codlVerification->created_at)->format('d/m/Y') : ($isMs ? 'Semakan CODL / Senat' : 'CODL / Senate Review')) }}</div>
      </div>
    </div>
  </div>
</div>

{{-- NOTA PENTING LAMPIRAN A --}}
<div class="print-note-box" style="margin-bottom:20px">
  <div style="font-weight:700;margin-bottom:4px;color:#ba1b1b;text-transform:uppercase;letter-spacing:0.5px">
    {{ $isMs ? 'NOTA PENTING' : 'IMPORTANT NOTICE' }}
  </div>
  @if($isMs)
    <div style="font-size:9pt;line-height:1.5">
      {{ __('Kelulusan penawaran MC, penganugerahan credential, penyediaan ACR, deposit ke ABC dan pemindahan kredit merupakan proses yang berbeza dan tertakluk kepada ketetapan serta kelulusan pihak berkuasa yang berkaitan. Kelulusan penawaran tidak menjamin pemindahan kredit atau kemasukan ke program akademik UTeM secara automatik.') }}
    </div>
  @else
    <div style="font-size:9pt;line-height:1.5">
      {{ __('MC offering approval, credential award, ACR preparation, ABC deposit, and credit transfer are distinct processes subject to the requirements and approvals of respective authorities. Offering approval does not guarantee automatic credit transfer or admission into UTeM degree programmes.') }}
    </div>
  @endif
</div>

@endsection
