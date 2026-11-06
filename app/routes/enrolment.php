<?php

use App\Modules\Enrolment\EnrolmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/register/{offering}', [EnrolmentController::class, 'wizard'])->whereNumber('offering')->name('register');
    Route::post('/register/{offering}/eligibility', [EnrolmentController::class, 'eligibility'])->whereNumber('offering')->name('register.eligibility');
    Route::post('/register/{offering}/acknowledge', [EnrolmentController::class, 'acknowledge'])->whereNumber('offering')->name('register.acknowledge');
    Route::post('/register/{offering}/pay', [EnrolmentController::class, 'pay'])->whereNumber('offering')->name('register.pay');

    Route::get('/enrolments', [EnrolmentController::class, 'index'])->name('enrolments');
    Route::get('/enrolments/{id}', [EnrolmentController::class, 'show'])->whereNumber('id')->name('enrolments.show');
    Route::post('/enrolments/{id}/withdraw', [EnrolmentController::class, 'withdraw'])->whereNumber('id')->name('enrolments.withdraw');
    Route::get('/enrolments/{id}/receipt', [EnrolmentController::class, 'receipt'])->whereNumber('id')->name('enrolments.receipt');
    Route::get('/enrolments/{id}/confirmation', [EnrolmentController::class, 'confirmation'])->whereNumber('id')->name('enrolments.confirmation');
});
