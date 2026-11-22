@extends('layouts.app')
@section('title', __('New transfer application'))
@section('content')
<x-breadcrumb :crumbs="[
  'Credit transfer' => route('transfer'),
  'New transfer application' => null,
]" />

<div class="title">
  <div>
    <h1>{{ __('New Credit Transfer Application') }}</h1>
    <div class="sub">{{ __('Credit Transfer Application · Apply to count deposited Academic Bank of Credit (ABC) credit toward a UTeM programme.') }}</div>
  </div>
  <div class="act">
    <a class="btn ter sm" href="{{ route('transfer') }}">&larr; {{ __('Back to applications') }}</a>
  </div>
</div>

<x-credit-lifecycle :stage="7" :note="__('Preparing credit transfer application')" />

<div class="cols">
  <div>
    <form method="post" action="{{ route('transfer.store') }}">
      @csrf

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('1. Target Programme') }}</h2></div>
        <div class="bd">
          <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
            <div>
              <label for="programme_id">{{ __('Target programme') }} *</label>
              <select id="programme_id" name="programme_id" required>
                @foreach($programmes as $p)
                  <option value="{{ $p->id }}" {{ (old('programme_id') == $p->id || (!old('programme_id') && $p->code === 'BITS')) ? 'selected' : '' }}>{{ $p->code }} {{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label for="institution">{{ __('Receiving institution') }}</label>
              <input id="institution" name="institution" type="text" value="{{ old('institution', 'UTeM') }}">
            </div>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('2. Credit Selection & Course Mapping') }}</h2></div>
        <div class="bd">
          <p class="small muted" style="margin-top:0;margin-bottom:14px">{{ __('Only Available credit can be selected directly. Expired credit can be selected with an automatic flag for currency-of-knowledge re-evaluation. Credit held by another active application or already redeemed cannot be reused (Single Redemption Principle).') }}</p>

          <div class="table-wrap">
            <table id="credits" style="margin:0 0 12px; width:100%">
              <thead>
                <tr>
                  <th style="width:40px"></th>
                  <th style="min-width:180px; width:35%">{{ __('Credit (ACR & ABC)') }}</th>
                  <th style="min-width:260px; width:45%">{{ __('Course requested') }}</th>
                  <th style="width:130px; text-align:right">{{ __('Credits requested') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($deposits as $d)
                  <tr>
                    <td style="vertical-align:middle; width:40px">
                      <input type="checkbox" name="deposits[]" value="{{ $d->id }}" aria-label="Use {{ $d->name_en }}" @disabled(! $d->selectable)>
                    </td>
                    <td style="vertical-align:middle">
                      <b>{{ $d->name_en }}</b><br>
                      <span class="muted small"><code>{{ $d->acr_reference }}</code> &middot; {{ __(':count credits', ['count' => mcms_num($d->credits)]) }}</span>
                      @if($d->why)
                        <br><span class="tag {{ $d->selectable ? 'warn' : 'err' }}">{{ $d->selectable ? __('Expired: needs re-evaluation') : __($d->why) }}</span>
                      @endif
                    </td>
                    <td style="vertical-align:middle">
                      <select name="course[{{ $d->id }}]" aria-label="Course for {{ $d->name_en }}" style="width:100%; min-width:240px; max-width:340px">
                        @foreach($courses as $c)
                          <option value="{{ $c->id }}" data-programme="{{ $c->programme_id }}" {{ (old("course.{$d->id}") == $c->id || (!old("course.{$d->id}") && ($c->name === $d->name_en || str_contains($c->name, $d->name_en) || str_contains($d->name_en, $c->name)))) ? 'selected' : '' }}>{{ $c->code }} {{ $c->name }} ({{ __(':count credit', ['count' => $c->credit]) }})</option>
                        @endforeach
                      </select>
                    </td>
                    <td style="vertical-align:middle; text-align:right">
                      <input type="number" name="credits[{{ $d->id }}]" step="0.5" min="0.5" max="{{ $d->credits }}" value="{{ old("credits.{$d->id}", $d->credits) }}" aria-label="Credits requested for {{ $d->name_en }}" style="width:90px; text-align:right">
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="muted" style="padding:16px">{{ __('You have no deposited credit. Give consent to deposit an Academic Credit Record first.') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px">
        <div class="hd">
          <h2>{{ __('3. Supporting Documents Checklist') }}</h2>
          <span class="r"><span class="tag info">{{ __('Checklist') }}</span></span>
        </div>
        <div class="bd">
          <p class="small muted" style="margin-top:0;margin-bottom:10px">{{ __('Tick each supporting document attached or available in MCMS for the faculty evaluator:') }}</p>
          <div class="checklist-grid" id="f6-checklist">
            @foreach(config('transfer.documents_checklist') as $key => $label)
              <label class="checklist-item">
                <input type="checkbox" name="document_checklist[]" value="{{ $key }}" @checked(in_array($key, old('document_checklist', ['acr', 'credential', 'learning_outcomes']), true))>
                <span>{{ __($label) }}</span>
              </label>
            @endforeach
          </div>

          <div style="margin-top:14px">
            <label for="other_document">{{ __('If "Other supporting documents" is ticked, specify') }}</label>
            <input id="other_document" name="other_document" type="text" value="{{ old('other_document') }}" placeholder="{{ __('e.g. Portfolio link, syllabus PDF reference, or professional endorsement') }}">
          </div>

          <div style="margin-top:12px">
            <label for="documents">{{ __('Additional supporting notes or reference links (optional)') }}</label>
            <textarea id="documents" name="documents" rows="2" placeholder="{{ __('Optional notes or references for the faculty evaluator...') }}">{{ old('documents') }}</textarea>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" class="btn">{{ __('Submit application') }}</button>
        <a class="btn ter" href="{{ route('transfer') }}">{{ __('Cancel') }}</a>
      </div>
    </form>
  </div>

  <div>
    <div class="panel">
      <div class="hd"><h2>{{ __('What this means') }}</h2></div>
      <div class="bd">
        <p class="small" style="margin:0 0 12px">{{ __('A credit transfer is decided case by case by the receiving faculty based on learning outcomes — never on credit value or Student Learning Time (SLT) alone.') }}</p>
        <p class="small" style="margin:0 0 12px">{{ __('At least 30% of graduating credits must be earned directly at UTeM residency, alongside any faculty transfer cap.') }}</p>
        <p class="small" style="margin:0">{{ __('An approved transfer is not programme admission, and a refused transfer leaves your ACR, credentials, and ABC credit unchanged.') }}</p>
      </div>
    </div>

    <div style="margin-top:12px">
      <a class="btn ghost sm" href="{{ route('transfer') }}">&larr; {{ __('Back to credit transfer') }}</a>
    </div>
  </div>
</div>
@endsection
