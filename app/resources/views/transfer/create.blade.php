@extends('layouts.app')
@section('title', __('New transfer application'))
@section('content')
<div class="crumb" style="margin-bottom:8px">
  <a href="{{ route('transfer') }}">{{ __('Credit transfer') }}</a>
  <span>/</span>
  <span>{{ __('New application') }}</span>
</div>

<div class="title">
  <div>
    <h1>{{ __('New transfer application') }}</h1>
    <div class="sub">{{ __('Applications to have deposited credit counted towards a UTeM programme') }}</div>
  </div>
</div>

<div class="track" style="margin-bottom:24px">
  <div class="stp now"><b>{{ __('1. Programme & credit') }}</b>{{ __('Target & source records') }}</div>
  <div class="stp"><b>{{ __('2. Record check') }}</b>{{ __('Authenticity & validity') }}</div>
  <div class="stp"><b>{{ __('3. Outcome matching') }}</b>{{ __('Course equivalence') }}</div>
  <div class="stp"><b>{{ __('4. Decision') }}</b>{{ __('Faculty endorsement') }}</div>
</div>

<div class="cols">
  <div>
    <form method="post" action="{{ route('transfer.store') }}">
      @csrf

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Target programme') }}</h2></div>
        <div class="bd">
          <div class="form-grid cols-2">
            <div class="field">
              <label for="programme_id">{{ __('Target programme') }}</label>
              <select id="programme_id" name="programme_id" required>
                @foreach($programmes as $p)
                  <option value="{{ $p->id }}" {{ (old('programme_id') == $p->id || (!old('programme_id') && $p->code === 'BITS')) ? 'selected' : '' }}>{{ $p->code }} {{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field">
              <label for="institution">{{ __('Receiving institution') }}</label>
              <input id="institution" name="institution" type="text" value="UTeM">
            </div>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Credit selection & mapping') }}</h2></div>
        <div class="bd">
          <p class="small muted" style="margin-top:0;margin-bottom:14px">{{ __('Only credit that is Available can be chosen. Expired credit can be chosen but is flagged so the evaluator judges the currency of knowledge again. Credit already in another application, or held for a programme, cannot be chosen again.') }}</p>

          <table id="credits" style="margin:0 0 16px">
            <thead>
              <tr>
                <th style="width:40px"></th>
                <th>{{ __('Credit') }}</th>
                <th>{{ __('Course requested') }}</th>
                <th style="width:140px">{{ __('Credits requested') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($deposits as $d)
                <tr>
                  <td>
                    <input type="checkbox" name="deposits[]" value="{{ $d->id }}" aria-label="Use {{ $d->name_en }}" @disabled(! $d->selectable)>
                  </td>
                  <td>
                    <b>{{ $d->name_en }}</b><br>
                    <span class="muted small">{{ $d->acr_reference }} &middot; {{ __(':count credits', ['count' => rtrim(rtrim((string) $d->credits, '0'), '.')]) }}</span>
                    @if($d->why)
                      <br><span class="tag {{ $d->selectable ? '' : 'err' }}">{{ $d->selectable ? __('Expired: needs re-evaluation') : __($d->why) }}</span>
                    @endif
                  </td>
                  <td>
                    <select name="course[{{ $d->id }}]" aria-label="Course for {{ $d->name_en }}" style="max-width:280px">
                      @foreach($courses as $c)
                        <option value="{{ $c->id }}" data-programme="{{ $c->programme_id }}" {{ (old("course.{$d->id}") == $c->id || (!old("course.{$d->id}") && ($c->name === $d->name_en || str_contains($c->name, $d->name_en) || str_contains($d->name_en, $c->name)))) ? 'selected' : '' }}>{{ $c->code }} {{ $c->name }} ({{ __(':count credit', ['count' => $c->credit]) }})</option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <input type="number" name="credits[{{ $d->id }}]" step="0.5" min="0.5" max="{{ $d->credits }}" value="{{ $d->credits }}" aria-label="Credits requested for {{ $d->name_en }}" style="max-width:110px">
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

      <div class="panel" style="margin-bottom:20px">
        <div class="hd"><h2>{{ __('Supporting documents') }}</h2></div>
        <div class="bd">
          <label for="documents" style="display:block;margin-bottom:8px">{{ __('Supporting documents (ACR, credential, learning outcomes, content, learning time, assessment, QV evidence)') }}</label>
          <textarea id="documents" name="documents" rows="3" placeholder="{{ __('References to attached evidence or explanatory notes for the evaluator...') }}"></textarea>
        </div>
      </div>

      <div class="row" style="gap:12px">
        <button type="submit" class="btn">{{ __('Submit application') }}</button>
        <a class="btn ter" href="{{ route('transfer') }}">{{ __('Cancel') }}</a>
      </div>
    </form>
  </div>

  <div>
    <div class="panel">
      <div class="hd"><h2>{{ __('What this means') }}</h2></div>
      <div class="bd">
        <p class="small" style="margin:0 0 12px">{{ __('A transfer is decided case by case by the receiving faculty. Applying does not mean the credit is accepted.') }}</p>
        <p class="small" style="margin:0 0 12px">{{ __('An approved transfer is not an admission, and a refused one changes nothing about your ACR or credentials. Admission is recorded separately by Pejabat Pendaftar.') }}</p>
      </div>
    </div>

    <div style="margin-top:12px">
      <a class="btn ghost sm" href="{{ route('transfer') }}">&larr; {{ __('Back to credit transfer') }}</a>
    </div>
  </div>
</div>
@endsection
