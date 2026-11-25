<?php

use App\Modules\Offering\CatalogueController;
use App\Modules\Offering\OfferingController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/', [CatalogueController::class, 'home'])->name('public.home');
    Route::get('/catalogue', [CatalogueController::class, 'catalogue'])->name('public.catalogue');
    Route::get('/offering/{id}', [CatalogueController::class, 'offering'])->whereNumber('id')->name('public.offering');
    Route::get('/how', [CatalogueController::class, 'how'])->name('public.how');
    Route::get('/pathways', [CatalogueController::class, 'pathways'])->name('public.pathways');
    Route::get('/pathways/{id}/print', [CatalogueController::class, 'pathwayPrint'])->whereNumber('id')->name('public.pathways.print');
    Route::get('/faq', [CatalogueController::class, 'faq'])->name('public.faq');
    Route::get('/privacy', [CatalogueController::class, 'privacy'])->name('public.privacy');
    Route::get('/terms', [CatalogueController::class, 'terms'])->name('public.terms');
    Route::get('/accessibility', [CatalogueController::class, 'accessibility'])->name('public.accessibility');
});

// Authenticated top-level portal URLs (students browse within the secured portal; guests & other roles use /public/*)
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/catalogue', [CatalogueController::class, 'catalogue'])->name('catalogue');
    Route::get('/offering/{id}', [CatalogueController::class, 'offering'])->whereNumber('id')->name('offering');
});
Route::middleware('auth')->group(function () {
    Route::get('/how', [CatalogueController::class, 'how'])->name('how');
    Route::get('/faq', [CatalogueController::class, 'faq'])->name('faq');
});
Route::get('/lang/{lang}', [CatalogueController::class, 'lang'])->name('lang');

Route::middleware(['auth', 'role:lecturer,deputy_dean,ptj_authority,codl_admin,academic_authority'])->get('/api/lecturers/search', [OfferingController::class, 'searchLecturers'])->name('lecturers.search');

Route::middleware('auth')->prefix('offerings')->group(function () {
    Route::middleware('role:lecturer,deputy_dean,ptj_authority')->group(function () {
        Route::get('/', [OfferingController::class, 'index'])->name('offerings');
        Route::get('/new', [OfferingController::class, 'create'])->name('offerings.new');
        Route::post('/', [OfferingController::class, 'store'])->name('offerings.store');
        Route::post('/{id}/readiness', [OfferingController::class, 'readiness'])->whereNumber('id')->name('offerings.readiness');
        Route::post('/{id}/cancel', [OfferingController::class, 'cancel'])->whereNumber('id')->name('offerings.cancel');
        Route::get('/{id}/participants', [OfferingController::class, 'participants'])->whereNumber('id')->name('offerings.participants');
    });

    Route::middleware('role:lecturer,deputy_dean,ptj_authority,codl_admin')->group(function () {
        Route::get('/{id}', [OfferingController::class, 'show'])->whereNumber('id')->name('offerings.show');
        Route::get('/{id}/readiness', [OfferingController::class, 'readinessPrint'])->whereNumber('id')->name('offerings.readiness.print');
        Route::post('/{id}/confirm', [OfferingController::class, 'confirm'])->whereNumber('id')->name('offerings.confirm');
    });
});
