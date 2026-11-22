<?php

use App\Modules\Achievement\CompletionController;
use App\Modules\Achievement\RecordController;
use Illuminate\Support\Facades\Route;

// Guide Appendix F11 & H3:
// - P1 (lecturer / coordinator): required completion sign-off (F11 Row 1) before Deputy Dean confirms completion, plus read-only cohort ACR tracker.
// - P2 (deputy_dean): confirms completion after P1 sign-off (F11 Row 2).
Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/cohorts/{id}/signoff', [CompletionController::class, 'signOffShow'])->whereNumber('id')->name('completion.signoff.show');
    Route::post('/cohorts/{id}/signoff', [CompletionController::class, 'signOffStore'])->whereNumber('id')->name('completion.signoff.store');
    Route::get('/cohorts/{id}/credits', [CompletionController::class, 'cohortCredits'])->whereNumber('id')->name('cohorts.credits');
});

Route::middleware(['auth', 'role:deputy_dean,ptj_authority,records_custodian,records_depositor'])->group(function () {
    Route::get('/completion/{id}', [CompletionController::class, 'show'])->whereNumber('id')->name('completion.show');
});
Route::middleware(['auth', 'role:deputy_dean,ptj_authority'])->group(function () {
    Route::post('/completion/{id}', [CompletionController::class, 'confirm'])->whereNumber('id')->name('completion.confirm');
});
Route::middleware(['auth', 'role:student'])->get('/my-record', [RecordController::class, 'mine'])->name('records.mine');
Route::middleware(['auth', 'role:student,lecturer,deputy_dean,ptj_authority,academic_authority,qa_officer,records_custodian,abc_depositor,records_depositor'])->get('/records/{enrolment}', [RecordController::class, 'show'])->whereNumber('enrolment')->name('records.show');
