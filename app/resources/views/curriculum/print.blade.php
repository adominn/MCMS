@extends('layouts.print')
@section('title', 'Proposal: ' . ($v->name_en ?: 'Micro-Credential'))
@section('doc_title', 'Proposal for a Micro-Credential (Appendix A)')
@section('doc_bm', 'Borang Cadangan Micro-Credential (Lampiran A)')
@section('doc_ref', 'MC-' . $v->micro_credential_id . ' v' . $v->version_no)
@section('doc_file', 'proposal-mc-' . $v->micro_credential_id . '-v' . $v->version_no . '.pdf')
@section('content')

@php
  $sltSum = (int) $modules->sum('slt_hours');
  $weightsSum = $comps->sum('weight_percent');
  $cat = strtoupper($v->category ?? 'UMC');
  $ptjName = $ptj ? $ptj->name : ($v->ptj_code ?? 'FTMK');
  $deanName = $dean ?: 'Deputy Dean (Academic)';
  $credTypes = is_string($v->credential_types) ? json_decode($v->credential_types, true) : ($v->credential_types ?? []);
  if (!is_array($credTypes)) { $credTypes = []; }
@endphp

<div style="margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:10px">
  <div class="small muted" style="text-transform:uppercase;letter-spacing:0.5px;font-weight:600;color:var(--accent, #0f62fe);margin-bottom:2px">
    Appendix A &middot; Lampiran A (Garis Panduan Micro-Credential UTeM)
  </div>
  <h1 style="font-size:18pt;margin:0 0 4px">{{ $v->name_en ?: 'Untitled micro-credential' }}</h1>
  <div class="small muted">
    {{ $cat }} &middot; Version {{ $v->version_no }} &middot; {{ $ptjName }} &middot; Proposer: {{ $v->owner_name }}
  </div>
</div>

{{-- 1. Basics --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('1. Basics') }} &middot; {{ __('A1. Maklumat Asas Penawaran') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Title (English)') }}</dt>
      <dd><b>{{ $v->name_en ?: '–' }}</b></dd>

      <dt style="font-size:9pt">{{ __('Title (Bahasa Melayu)') }}</dt>
      <dd>{{ $v->name_ms ?: 'Not filled in' }}</dd>

      <dt style="font-size:9pt">{{ __('Category') }}</dt>
      <dd><b>{{ $cat }}</b> ({{ ['umc' => 'Unbundled Micro-Credential', 'samc' => 'Stand-Alone Micro-Credential', 'mce' => 'Micro-Credential Eksekutif'][$v->category] ?? $cat }}){{ $course ? ', parent course ' . $course->code . ' (' . $course->name . ')' : '' }}</dd>

      <dt style="font-size:9pt">{{ __('Proposal Status / Type') }}</dt>
      <dd>{{ ['new' => 'Baharu (New)', 'revision' => 'Semakan (Revision)', 'reoffer' => 'Penawaran Semula (Reoffer)'][$v->proposal_type ?? 'new'] ?? ucfirst($v->proposal_type ?? 'new') }}</dd>

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
  <div class="hd"><h2 style="font-size:11pt">{{ __('2. Justification') }} &middot; {{ __('A2. Asas dan Justifikasi Pembangunan') }}</h2></div>
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
      <dd>{{ $v->existing_equivalents ?: 'Tiada kursus bertindih secara langsung / Standalone modular delivery without unnecessary overlap.' }}</dd>
    </dl>
  </div>
</div>

{{-- A3. Category Determination --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('A3. Category Determination') }} &middot; {{ __('Penentuan Kategori Micro-Credential') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Proposed Category') }}</dt>
      <dd><b>{{ $cat }}</b> &middot; {{ ['umc' => 'Unbundled Micro-Credential (Formal Learning, unbundled from accredited degree course)', 'samc' => 'Stand-Alone Micro-Credential (Non-Formal Learning, industry skills, eligible for Quality Verification)', 'mce' => 'Micro-Credential Eksekutif (Continuing education / executive upskilling, non-credit)'][$v->category] ?? $cat }}</dd>

      <dt style="font-size:9pt">{{ __('Category Justification') }}</dt>
      <dd>{{ $v->category_justification ?: ($cat === 'UMC' ? 'Unbundled directly from accredited parent course ' . ($course?->code ?? '') . ' to provide stackable academic credit pathways.' : ($cat === 'SAMC' ? 'Stand-alone competency-focused micro-credential designed for immediate industry and lifelong learner upskilling.' : 'Executive continuous professional development without academic credit accumulation.')) }}</dd>
    </dl>
  </div>
</div>

{{-- A4. Additional Information for UMC --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('A4. Additional Information for UMC') }} &middot; {{ __('Maklumat Tambahan bagi UMC') }}</h2></div>
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
        <dd><span class="tag ok">MQA Full Accreditation (Akreditasi Penuh)</span></dd>

        <dt style="font-size:9pt">{{ __('Parent Course SLT & Credits') }}</dt>
        <dd>{{ $course ? $course->credit . ' credits (' . $course->slt_hours . ' h SLT)' : '3 credits (120 h SLT)' }}</dd>

        <dt style="font-size:9pt">{{ __('Unbundling Model') }}</dt>
        <dd>
          @if($modelMeta)
            <b style="color:{{ $modelMeta['badge_color'] }}">({{ $modelMeta['letter'] }}) {{ $modelMeta['name_ms'] }} &middot; {{ $modelMeta['name_en'] }}</b>
            <div class="small muted">{{ $modelMeta['desc_ms'] }} &middot; {{ $modelMeta['rule_ms'] }}</div>
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
  <div class="hd"><h2 style="font-size:11pt">{{ __('3. Learning outcomes') }} &middot; {{ __('Hasil Pembelajaran') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    @if($los->isNotEmpty())
      <ol style="margin:0;padding-left:20px;font-size:10pt;line-height:1.6">
        @foreach($los as $idx => $lo)
          <li>
            <b>LO{{ $idx + 1 }}:</b> {{ $lo->text_en ?: $lo->text_ms }}
            @if($lo->text_ms && $lo->text_en !== $lo->text_ms)<div class="small muted">{{ $lo->text_ms }}</div>@endif
            @if($lo->parent_clo_id)<div class="small muted">Penjajaran Kursus Induk: CLO{{ $lo->parent_clo_id }}</div>@endif
          </li>
        @endforeach
      </ol>
    @else
      <div class="small muted">No learning outcomes entered yet.</div>
    @endif
  </div>
</div>

{{-- 4. Modules and learning time (SLT) --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('4. Modules and learning time (SLT)') }} &middot; {{ __('A5. Reka Bentuk Pembelajaran & Modul') }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:25%">{{ __('Module') }}</th>
          <th style="width:30%">{{ __('Key Content / Topik Utama') }}</th>
          <th style="width:25%">{{ __('Learning Activities / Pentaksiran') }}</th>
          @if($v->credit_model === 'separate_assessment')<th style="width:10%">{{ __('Type') }}</th>@endif
          <th class="num" style="width:10%">{{ __('Hours') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($modules as $m)
          @php $modActs = $activities[$m->id] ?? collect(); @endphp
          <tr>
            <td>
              <b>{{ $m->name_en ?: ('Module ' . $m->seq) }}</b>
              @if($m->name_ms && $m->name_ms !== $m->name_en)<div class="small muted">({{ $m->name_ms }})</div>@endif
            </td>
            <td>
              <div style="font-size:9pt">{{ $m->key_content ?: ($m->name_en . ' fundamental principles and applied practices.') }}</div>
            </td>
            <td>
              @forelse($modActs as $act)
                <div style="font-size:8.5pt">&bull; {{ $act->name_en }} ({{ config('proposals.activity_types.' . $act->activity_type) ?: ucfirst($act->activity_type) }}, {{ $act->slt_hours }}h)</div>
              @empty
                <div style="font-size:8.5pt" class="muted">Lecture, self-study, and practical tasks ({{ $m->slt_hours }}h)</div>
              @endforelse
            </td>
            @if($v->credit_model === 'separate_assessment')
              <td>
                <span style="font-size:8.5pt;font-weight:600;color:{{ $m->is_assessment ? '#6929c4' : '#0043ce' }}">
                  {{ $m->is_assessment ? __('Assessment') : __('Learning') }}
                </span>
              </td>
            @endif
            <td class="num">{{ $m->slt_hours }} h</td>
          </tr>
        @empty
          <tr><td colspan="{{ $v->credit_model === 'separate_assessment' ? 5 : 4 }}" class="muted" style="text-align:center">No modules configured.</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr style="font-weight:600;border-top:2px solid var(--border)">
          <td colspan="{{ $v->credit_model === 'separate_assessment' ? 4 : 3 }}">{{ __('Total Student Learning Time (SLT)') }}</td>
          <td class="num">{{ $sltSum }} h</td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div style="font-size:9pt;color:var(--muted);padding:8px 16px">
    @if($course && $sltSum === (int) $course->slt_hours)
      Total equals the declared parent course learning time of {{ $course->slt_hours }} hours ({{ $course->credit }} credits at 40 hours per credit).
    @elseif($sltSum > 0)
      Equivalent to {{ mcms_num($sltSum / 40) }} academic credit(s) based on 40 SLT hours per credit. Proposed credit: {{ $v->proposed_credit ? mcms_num($v->proposed_credit) : ($course ? mcms_num($course->credit) : mcms_num($sltSum / 40)) }} credit(s). Verified MQF Level: {{ $v->proposed_mqf_level ? 'Level ' . $v->proposed_mqf_level : ($cat === 'UMC' ? 'Level 6' : ($cat === 'SAMC' ? 'Level 4' : 'None')) }}.
    @endif
  </div>
</div>

{{-- 5. Assessment weightings --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('5. Assessment weightings') }} &middot; {{ __('A6. Pentaksiran dan Standard Penyempurnaan') }}</h2></div>
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
            <td class="num">{{ mcms_num($c->weight_percent) }}%</td>
          </tr>
        @empty
          <tr><td colspan="2" class="muted" style="text-align:center">No assessment components configured.</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr style="font-weight:600;border-top:2px solid var(--border)">
          <td>{{ __('Total') }}</td>
          <td class="num">{{ mcms_num($weightsSum) }}%</td>
        </tr>
      </tfoot>
    </table>
  </div>
  <div style="font-size:9pt;color:var(--muted);padding:8px 16px">
    <b>{{ __('Pass mark:') }}</b> {{ $v->assessment_standard ?: '50% (Standard minimum lulus kursus mengikut peraturan akademik UTeM / Pass mark: 50%)' }} &middot; <b>{{ __('Integrity:') }}</b> {{ $v->integrity_mechanism ?: 'Identity verified via official university portal credentials.' }} &middot; <b>{{ __('Feedback:') }}</b> {{ $v->feedback_method ?: 'Written feedback within 10 working days of each component.' }}
  </div>
</div>

{{-- 6. Alignment of outcomes to assessment --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('6. Alignment of outcomes to assessment') }} &middot; {{ __('Penjajaran Hasil Pembelajaran') }}</h2></div>
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
  <div class="hd"><h2 style="font-size:11pt">{{ __('7. Credential design') }} &middot; {{ __('A7. Credential dan Pengiktirafan') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Credential Awarded') }}</dt>
      <dd>
        @php
          $credDisplay = [];
          if (in_array('badge', $credTypes, true)) { $credDisplay[] = 'Digital Badge (Open Badges 3.0)'; }
          if (in_array('certificate', $credTypes, true)) { $credDisplay[] = 'Sijil Rasmi UTeM (Official A4 Certificate)'; }
          if (in_array('vc', $credTypes, true) || in_array('digital_credential', $credTypes, true)) { $credDisplay[] = 'Digital Credential (W3C Verifiable Credential)'; }
          if ($credDisplay === []) { $credDisplay[] = 'Digital Badge (Open Badges 3.0)'; }
        @endphp
        {{ implode(' &middot; ', $credDisplay) }}
      </dd>

      <dt style="font-size:9pt">{{ __('Achievement Level') }}</dt>
      <dd>{{ $v->achievement_level === 'module' ? 'Peringkat Modul / Unit Pembelajaran (Module level)' : 'Keseluruhan Micro-Credential (Overall MC)' }}</dd>

      <dt style="font-size:9pt">{{ __('Credit (proposed)') }}</dt>
      <dd>{{ $v->proposed_credit ? mcms_num($v->proposed_credit) . ' credit(s)' : ($course ? mcms_num($course->credit) . ' credit(s)' : 'None') }}</dd>

      <dt style="font-size:9pt">{{ __('MQF level (proposed)') }}</dt>
      <dd>{{ $v->proposed_mqf_level ? 'MQF level ' . $v->proposed_mqf_level : ($cat === 'UMC' ? 'MQF level 6 (proposed)' : ($cat === 'SAMC' ? 'MQF level 4 (proposed)' : 'None')) }}</dd>

      <dt style="font-size:9pt">{{ __('Quality Verification') }}</dt>
      <dd>{{ $cat === 'SAMC' ? ($v->qv_status ? ucfirst($v->qv_status) : ($v->qv_planned ? 'Will apply for Quality Verification (Akan dipohon)' : 'Not applied (Tidak dipohon)')) : 'Not applicable (TB - UMC / MCE)' }}</dd>

      <dt style="font-size:9pt">{{ __('Public Verification') }}</dt>
      <dd>{{ $v->verification_method ?: 'Public verification portal (https://verify.utem.edu.my/)' }}</dd>

      <dt style="font-size:9pt">{{ __('Recognition Potential') }}</dt>
      <dd>{{ $v->recognition_potential ?: 'Kelayakan industri dan laluan ijazah bertumpuk (Stackable Degree) di UTeM.' }}</dd>
    </dl>
  </div>
</div>

{{-- 8. Delivery --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('8. Delivery') }} &middot; {{ __('Mod & Jadual Pelaksanaan') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px">
      <dt style="font-size:9pt">{{ __('Schedule') }}</dt>
      <dd>{{ $v->delivery_mode ?: 'Weekly facilitated session plus self-paced work on UCreds platform' }}</dd>

      <dt style="font-size:9pt">{{ __('Platform') }}</dt>
      <dd>UCreds learning platform</dd>
    </dl>
  </div>
</div>

{{-- A8. Delivery Readiness Checklist --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('A8. Kesediaan Pelaksanaan') }} &middot; {{ __('Delivery Readiness Checklist') }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:5%">#</th>
          <th style="width:40%">{{ __('Perkara Semakan (Checklist Item)') }}</th>
          <th style="width:15%">{{ __('Status') }}</th>
          <th style="width:40%">{{ __('Catatan / Evidens (Notes / Evidence)') }}</th>
        </tr>
      </thead>
      <tbody>
        @php
          $a8Items = [
            'materials' => 'Bahan pembelajaran telah siap dan disemak (Learning materials ready & reviewed)',
            'platform' => 'Platform / persekitaran pembelajaran sedia digunakan (Learning platform ready)',
            'assessment' => 'Kaedah pentaksiran dan rubrik telah disediakan (Assessment & rubrics ready)',
            'facilitators' => 'Tenaga pengajar / fasilitator telah dikenal pasti (Facilitators identified)',
            'participant_info' => 'Maklumat peserta dan terma penawaran jelas (Participant info & terms clear)',
            'records' => 'Mekanisme rekod dan pensijilan sedia dilaksanakan (Records & credential mechanism ready)',
            'accessibility' => 'Aspek kebolehcapaian dan sokongan peserta dipertimbangkan (Accessibility & support considered)',
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
                <span class="tag ok">{{ __('Ya (Ready)') }}</span>
              @elseif($st === 'na')
                <span class="tag info">{{ __('TB (N/A)') }}</span>
              @else
                <span class="tag warn">{{ __('Belum (Pending)') }}</span>
              @endif
            </td>
            <td>{{ $note ?: ($st === 'yes' ? __('Semakan kendiri lengkap / Self-checked complete') : '—') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- 9. Co-developers --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('9. Co-developers') }} &middot; {{ __('Rakan Kerjasama / Pembangun Bersama') }}</h2></div>
  <div class="bd" style="padding:12px 16px;font-size:10pt">
    {{ __('None declared. Micro-credential developed entirely in-house by UTeM faculty.') }}
  </div>
</div>

{{-- A9. Review and Approval Sign-Offs --}}
<div class="panel" style="margin-bottom:16px">
  <div class="hd"><h2 style="font-size:11pt">{{ __('A9. Semakan dan Kelulusan') }} &middot; {{ __('Review and Approval Sign-Offs') }}</h2></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:25%">{{ __('Peringkat (Stage)') }}</th>
          <th style="width:25%">{{ __('Pegawai / Jawatan (Officer & Position)') }}</th>
          <th style="width:15%">{{ __('Keputusan (Decision)') }}</th>
          <th style="width:15%">{{ __('Tarikh (Date)') }}</th>
          <th style="width:20%">{{ __('Catatan / Rujukan Mesyuarat (Remarks)') }}</th>
        </tr>
      </thead>
      <tbody>
        {{-- Stage 1 --}}
        <tr>
          <td><b>{{ __('1. Semakan Akademik / Teknikal') }}</b><br><span class="muted small">{{ __('Academic / Technical Review') }}</span></td>
          <td>
            @if($stage1)
              <b>{{ $stage1->reviewer_name }}</b><br><span class="muted small">{{ $stage1->reviewer_position ?: __('Penyemak Kurikulum') }}</span>
            @else
              <span class="muted">{{ __('Belum selesai / Not yet recorded') }}</span>
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
          <td><b>{{ __('2. Pengesahan PTj / Fakulti') }}</b><br><span class="muted small">{{ __('Faculty Endorsement') }}</span></td>
          <td>
            @if($stage2)
              <b>{{ $stage2->decider_name }}</b><br><span class="muted small">{{ $stage2->decider_position ?: __('Timbalan Dekan (Akademik)') }}</span>
            @else
              <span class="muted">{{ __('Belum selesai / Not yet recorded') }}</span>
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
        <tr>
          <td><b>{{ __('3. Kelulusan Kuasa UTeM') }}</b><br><span class="muted small">{{ __('Senate / Academic Authority Approval') }}</span></td>
          <td>
            @if($stage3)
              <b>{{ $stage3->decider_name }}</b><br><span class="muted small">{{ $stage3->decider_position ?: __('Pengerusi Senat / Kuasa Universiti') }}</span>
            @else
              <span class="muted">{{ __('Belum selesai / Not yet recorded') }}</span>
            @endif
          </td>
          <td>
            @if($stage3)
              <span class="tag ok">{{ ucfirst($stage3->decision) }}</span>
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
  <div class="hd"><h2 style="font-size:11pt">{{ __('10. Declarations and Signatures') }} &middot; {{ __('A10. Kawalan Dokumen dan Perubahan') }}</h2></div>
  <div class="bd" style="padding:12px 16px">
    <dl class="dl" style="font-size:10pt;row-gap:6px;margin-bottom:16px">
      <dt style="font-size:9pt">{{ __('Document No. / Version') }}</dt>
      <dd><b>MC-{{ $v->micro_credential_id }} v{{ $v->version_no }}</b></dd>

      <dt style="font-size:9pt">{{ __('Effective Date') }}</dt>
      <dd>{{ $v->created_at ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y') : '—' }}</dd>

      <dt style="font-size:9pt">{{ __('Change Summary') }}</dt>
      <dd>{{ $v->change_summary ?: ($v->version_no > 1 ? 'Semakan kurikulum versi ' . $v->version_no : 'Cadangan penawaran baharu versi asal 1.0.') }}</dd>

      <dt style="font-size:9pt">{{ __('Material Change?') }}</dt>
      <dd>{{ $v->change_class === 'material' ? 'Ya (Perubahan Signifikan)' : 'Tidak (Biasa / Versi 1.0)' }}</dd>

      <dt style="font-size:9pt">{{ __('Follow-up Action') }}</dt>
      <dd>{{ config('proposals.follow_up_actions.' . $v->follow_up_action) ?: ($v->follow_up_action ?: 'Penawaran kepada kumpulan sasaran & pelaksanaan proses jaminan kualiti.') }}</dd>
    </dl>

    <div style="font-size:10pt;margin-bottom:16px;padding-top:10px;border-top:1px solid var(--border)">
      <b>{{ __('Intellectual property declaration:') }}</b> The proposer confirms that material in this micro-credential is original to UTeM or used with written permission. Third-party material is identified. (Perakuan Harta Intelek: Pemohon mengesahkan bahawa bahan pengajaran dalam micro-credential ini adalah karya asli UTeM atau telah mendapat kebenaran bertulis.)
    </div>

    <div style="display:flex;justify-content:space-between;margin-top:20px;padding-top:16px;border-top:1px dashed var(--border)">
      <div style="width:45%">
        <div style="height:36px;border-bottom:1px solid var(--ink, #000);margin-bottom:4px"></div>
        <div style="font-size:10pt"><b>{{ $v->owner_name }}</b></div>
        <div class="small muted">{{ $proposer?->position ?: 'Proposer / Lecturer' }}, {{ $ptjName }}</div>
        <div class="small muted">{{ $v->created_at ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y') : '—' }}</div>
      </div>
      <div style="width:45%">
        <div style="height:36px;border-bottom:1px solid var(--ink, #000);margin-bottom:4px"></div>
        <div style="font-size:10pt"><b>{{ $deanName }}</b></div>
        <div class="small muted">{{ $stage2?->decider_position ?: 'Deputy Dean (Academic)' }}, {{ $ptjName }}</div>
        <div class="small muted">{{ $stage2 ? \Carbon\Carbon::parse($stage2->created_at)->format('d/m/Y') : 'Pengesahan PTj / Endorsement' }}</div>
      </div>
    </div>
  </div>
</div>

{{-- NOTA PENTING LAMPIRAN A --}}
<div class="print-note-box" style="margin-bottom:20px">
  <div style="font-weight:700;margin-bottom:4px;color:#ba1b1b;text-transform:uppercase;letter-spacing:0.5px">
    {{ __('NOTA PENTING (Lampiran A) &middot; IMPORTANT NOTICE (Appendix A)') }}
  </div>
  <div style="font-size:9pt;line-height:1.5">
    <b>{{ __('Bahasa Melayu:') }}</b> {{ __('Kelulusan penawaran MC, penganugerahan credential, penyediaan ACR, deposit ke ABC dan pemindahan kredit merupakan proses yang berbeza dan tertakluk kepada ketetapan serta kelulusan pihak berkuasa yang berkaitan. Kelulusan penawaran tidak menjamin pemindahan kredit atau kemasukan ke program akademik UTeM secara automatik.') }}
  </div>
  <div style="font-size:9pt;line-height:1.5;margin-top:4px;color:var(--text-secondary)">
    <b>{{ __('English:') }}</b> {{ __('MC offering approval, credential award, ACR preparation, ABC deposit, and credit transfer are distinct processes subject to the requirements and approvals of respective authorities. Offering approval does not guarantee automatic credit transfer or admission into UTeM degree programmes.') }}
  </div>
</div>

@endsection
