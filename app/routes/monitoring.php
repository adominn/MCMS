<?php

use App\Modules\Monitoring\CqiController;
use App\Modules\Monitoring\QualityReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/feedback', [QualityReviewController::class, 'feedback'])->name('feedback');
    Route::get('/feedback/{enrolment}', [QualityReviewController::class, 'feedbackForm'])->whereNumber('enrolment')->name('feedback.show');
    Route::post('/feedback/{enrolment}', [QualityReviewController::class, 'submitFeedback'])->whereNumber('enrolment')->name('feedback.submit');
});
Route::middleware(['auth', 'role:lecturer,deputy_dean'])->group(function () {
    Route::get('/monitoring', [QualityReviewController::class, 'index'])->name('monitoring');
    Route::get('/monitoring/cohort/{id}', [QualityReviewController::class, 'cohort'])->whereNumber('id')->name('monitoring.cohort');
    Route::post('/periodic-reviews/{id}', [QualityReviewController::class, 'reviewOutcome'])->whereNumber('id')->name('reviews.periodic');
    Route::post('/cqi', [CqiController::class, 'store'])->name('cqi.store');
    Route::get('/cqi/{id}', [CqiController::class, 'show'])->whereNumber('id')->name('cqi.show');
    Route::post('/cqi/{id}/domains', [CqiController::class, 'domains'])->whereNumber('id')->name('cqi.domains');
    Route::post('/cqi/{id}/issues', [CqiController::class, 'issue'])->whereNumber('id')->name('cqi.issue');
    Route::post('/cqi/{id}/submit', [CqiController::class, 'submit'])->whereNumber('id')->name('cqi.submit');
    Route::post('/cqi/actions/{action}/status', [CqiController::class, 'status'])->whereNumber('action')->name('cqi.status');
    Route::post('/cqi/actions/{action}/evaluate', [CqiController::class, 'evaluate'])->whereNumber('action')->name('cqi.evaluate');
});
Route::middleware(['auth', 'role:deputy_dean'])->group(function () {
    Route::post('/cqi/{id}/confirm', [CqiController::class, 'confirm'])->whereNumber('id')->name('cqi.confirm');
    Route::post('/cqi/actions/{action}/close', [CqiController::class, 'close'])->whereNumber('action')->name('cqi.close');
    Route::get('/suspensions', [QualityReviewController::class, 'suspensions'])->name('suspensions');
    Route::get('/suspensions/{id}/print', [QualityReviewController::class, 'printSuspension'])->whereNumber('id')->name('suspensions.print');
    Route::post('/suspensions', [QualityReviewController::class, 'decide'])->name('suspensions.decide');
    Route::post('/suspensions/{id}/lift', [QualityReviewController::class, 'lift'])->whereNumber('id')->name('suspensions.lift');
});
