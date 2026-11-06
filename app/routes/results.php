<?php

use App\Modules\Assessment\ResultsController;
use App\Modules\Learning\UcredsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:codl_admin'])->prefix('admin/ucreds')->group(function () {
    Route::get('/', [UcredsController::class, 'index'])->name('ucreds');
    Route::get('/courses', [UcredsController::class, 'courses'])->name('ucreds.courses');
    Route::get('/export/{offering}', [UcredsController::class, 'export'])->whereNumber('offering')->name('ucreds.export');
    Route::get('/export/{offering}/manifest', [UcredsController::class, 'exportManifest'])->whereNumber('offering')->name('ucreds.export.manifest');
    Route::post('/check', [UcredsController::class, 'check'])->name('ucreds.check');
    Route::get('/imports/{id}', [UcredsController::class, 'report'])->whereNumber('id')->name('ucreds.report');
    Route::post('/imports/{id}/stage', [UcredsController::class, 'stage'])->whereNumber('id')->name('ucreds.stage');
    Route::get('/imports/{id}/errors.csv', [UcredsController::class, 'errors'])->whereNumber('id')->name('ucreds.errors');
});

Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/offerings/{id}/results', [ResultsController::class, 'cohort'])->whereNumber('id')->name('results.cohort');
    Route::post('/offerings/{id}/results', [ResultsController::class, 'save'])->whereNumber('id')->name('results.save');
    Route::post('/offerings/{id}/results/flag', [ResultsController::class, 'flag'])->whereNumber('id')->name('results.flag');
    Route::post('/results/{result}/change', [ResultsController::class, 'requestChange'])->whereNumber('result')->name('results.change');
});

Route::middleware(['auth', 'role:deputy_dean'])->prefix('results')->group(function () {
    Route::get('/', [ResultsController::class, 'index'])->name('results');
    Route::get('/{id}', [ResultsController::class, 'verifyPage'])->whereNumber('id')->name('results.verify');
    Route::post('/{id}/verify', [ResultsController::class, 'verify'])->whereNumber('id')->name('results.verify.do');
    Route::post('/{id}/cases/{case}/resolve', [ResultsController::class, 'resolveCase'])->whereNumber(['id', 'case'])->name('results.case');
    Route::post('/{id}/changes/{change}', [ResultsController::class, 'decideChange'])->whereNumber(['id', 'change'])->name('results.decide');
});

Route::middleware(['auth', 'role:student'])->prefix('my-results')->group(function () {
    Route::get('/', [ResultsController::class, 'mine'])->name('results.mine');
    Route::get('/{id}', [ResultsController::class, 'myDetail'])->whereNumber('id')->name('results.mine.show');
});
