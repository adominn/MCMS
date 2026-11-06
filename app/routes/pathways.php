<?php

use App\Modules\Pathways\PathwayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->get('/my-plan', [PathwayController::class, 'mine'])->name('plan.mine');
Route::middleware(['auth', 'role:lecturer,deputy_dean'])->group(function () {
    Route::get('/pathways', [PathwayController::class, 'index'])->name('pathways');
    Route::post('/pathways', [PathwayController::class, 'store'])->name('pathways.store');
    Route::get('/pathways/{id}', [PathwayController::class, 'show'])->whereNumber('id')->name('pathways.show');
    Route::post('/pathways/{id}/items', [PathwayController::class, 'addItem'])->whereNumber('id')->name('pathways.item');
    Route::post('/pathways/{id}/items/{item}/remove', [PathwayController::class, 'removeItem'])->whereNumber(['id', 'item'])->name('pathways.remove');
    Route::get('/plans/new', [PathwayController::class, 'planForm'])->name('plans.new');
    Route::post('/plans', [PathwayController::class, 'planStore'])->name('plans.store');
    Route::get('/plans/{id}', [PathwayController::class, 'planShow'])->whereNumber('id')->name('plans.show');
    Route::post('/plans/{id}', [PathwayController::class, 'planUpdate'])->whereNumber('id')->name('plans.update');
});
Route::middleware(['auth', 'role:deputy_dean'])->group(function () {
    Route::post('/pathways/{id}/approve', [PathwayController::class, 'approve'])->whereNumber('id')->name('pathways.approve');
    Route::post('/pathways/{id}/publish', [PathwayController::class, 'publish'])->whereNumber('id')->name('pathways.publish');
    Route::post('/plans/{id}/decision', [PathwayController::class, 'planDecision'])->whereNumber('id')->name('plans.decision');
    Route::post('/plans/{id}/award', [PathwayController::class, 'planAward'])->whereNumber('id')->name('plans.award');
});
