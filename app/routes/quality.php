<?php

use App\Modules\Quality\QvController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:lecturer,deputy_dean'])->prefix('quality')->group(function () {
    Route::get('/', [QvController::class, 'index'])->name('quality');
    Route::get('/{mc}', [QvController::class, 'show'])->whereNumber('mc')->name('quality.show');
    Route::get('/{mc}/package', [QvController::class, 'package'])->whereNumber('mc')->name('quality.package');
});
Route::middleware(['auth', 'role:lecturer'])->prefix('quality/{mc}')->whereNumber('mc')->group(function () {
    Route::post('/start', [QvController::class, 'start'])->name('quality.start');
    Route::post('/evidence', [QvController::class, 'evidence'])->name('quality.evidence');
    Route::post('/review', [QvController::class, 'review'])->name('quality.review');
    Route::post('/readiness', [QvController::class, 'readiness'])->name('quality.readiness');
});
Route::middleware(['auth', 'role:deputy_dean'])->prefix('quality/{mc}')->whereNumber('mc')->group(function () {
    Route::post('/confirm', [QvController::class, 'confirm'])->name('quality.confirm');
    Route::post('/submit', [QvController::class, 'submit'])->name('quality.submit');
    Route::post('/result', [QvController::class, 'result'])->name('quality.result');
    Route::post('/end', [QvController::class, 'end'])->name('quality.end');
});
