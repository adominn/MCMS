<?php

use App\Modules\Credentials\CredentialController;
use App\Modules\Credentials\VerifyController;
use Illuminate\Support\Facades\Route;

Route::get('/.well-known/jwks.json', [VerifyController::class, 'jwks'])->name('jwks');

// Clean verification URLs (internal and general use)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/verify', [VerifyController::class, 'form'])->name('verify');
    Route::post('/verify/file', [VerifyController::class, 'file'])->name('verify.file');
    Route::get('/verify/{code}', [VerifyController::class, 'show'])->where('code', '[A-Za-z0-9\-_]{1,40}')->name('verify.show');
    Route::get('/verify/{code}/download/{as}', [VerifyController::class, 'download'])->where('code', '[A-Za-z0-9\-_]{1,40}')->where('as', 'json|jwt')->name('verify.download');
});

Route::middleware('throttle:60,1')->prefix('public/verify')->group(function () {
    Route::get('/', [VerifyController::class, 'form'])->name('public.verify');
    Route::post('/file', [VerifyController::class, 'file'])->name('public.verify.file');
    Route::get('/{code}', [VerifyController::class, 'show'])->where('code', '[A-Za-z0-9\-_]{1,40}')->name('public.verify.show');
    Route::get('/{code}/download/{as}', [VerifyController::class, 'download'])->where('code', '[A-Za-z0-9\-_]{1,40}')->where('as', 'json|jwt')->name('public.verify.download');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/my-credentials', [CredentialController::class, 'wallet'])->name('credentials.wallet');
    Route::get('/my-credentials/{id}', [CredentialController::class, 'show'])->whereNumber('id')->name('credentials.show');
    Route::get('/my-credentials/{id}/certificate', [CredentialController::class, 'certificate'])->whereNumber('id')->name('credentials.certificate');
    Route::get('/my-credentials/{id}/sheet', [CredentialController::class, 'sheet'])->whereNumber('id')->name('credentials.sheet');
    Route::get('/my-credentials/{id}/download/{as}', [CredentialController::class, 'download'])->whereNumber('id')->name('credentials.download');
});

Route::middleware(['auth', 'role:deputy_dean'])->prefix('credentials')->group(function () {
    Route::get('/', [CredentialController::class, 'index'])->name('credentials.admin');
    Route::post('/retry/{enrolment}', [CredentialController::class, 'retry'])->whereNumber('enrolment')->name('credentials.retry');
    Route::get('/{id}', [CredentialController::class, 'adminShow'])->whereNumber('id')->name('credentials.admin.show');
    Route::get('/{id}/sheet', [CredentialController::class, 'sheet'])->whereNumber('id')->name('credentials.admin.sheet');
    Route::post('/{id}/correct', [CredentialController::class, 'correct'])->whereNumber('id')->name('credentials.correct');
    Route::post('/{id}/replace', [CredentialController::class, 'replace'])->whereNumber('id')->name('credentials.replace');
    Route::post('/{id}/revoke', [CredentialController::class, 'revoke'])->whereNumber('id')->name('credentials.revoke');
});
