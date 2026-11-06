<?php

use App\Modules\Curriculum\VersionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:lecturer'])->prefix('micro-credentials')->group(function () {
    Route::get('/', [VersionController::class, 'index'])->name('mcs');
    Route::get('/{mc}', [VersionController::class, 'show'])->whereNumber('mc')->name('mcs.show');
    Route::get('/{mc}/change', [VersionController::class, 'changeForm'])->whereNumber('mc')->name('mcs.change');
    Route::post('/{mc}/change', [VersionController::class, 'change'])->whereNumber('mc');
    Route::post('/{mc}/reoffer', [VersionController::class, 'reoffer'])->whereNumber('mc')->name('mcs.reoffer');
});

Route::middleware(['auth', 'role:deputy_dean'])->prefix('changes')->group(function () {
    Route::get('/{id}', [VersionController::class, 'changeShow'])->whereNumber('id')->name('changes.show');
    Route::post('/{id}/acknowledge', [VersionController::class, 'acknowledge'])->whereNumber('id')->name('changes.acknowledge');
});
