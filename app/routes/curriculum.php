<?php

use App\Modules\Curriculum\ProposalController;
use Illuminate\Support\Facades\Route;

Route::get('/proposals/{id}/print', [ProposalController::class, 'print'])->whereNumber('id')->middleware(['auth', 'role:lecturer,deputy_dean'])->name('proposals.print');

Route::middleware(['auth', 'role:lecturer'])->prefix('proposals')->group(function () {
    Route::get('/', [ProposalController::class, 'index'])->name('proposals');
    Route::get('/new', [ProposalController::class, 'wizard'])->name('proposals.new');
    Route::post('/', [ProposalController::class, 'create'])->name('proposals.create');
    Route::get('/{id}', [ProposalController::class, 'show'])->whereNumber('id')->name('proposals.show');
    Route::post('/{id}/submit', [ProposalController::class, 'submit'])->whereNumber('id')->name('proposals.submit');
    Route::post('/{id}/{tab}', [ProposalController::class, 'save'])->whereNumber('id')->name('proposals.save');
    Route::delete('/{id}', [ProposalController::class, 'destroy'])->whereNumber('id')->name('proposals.destroy');
});
