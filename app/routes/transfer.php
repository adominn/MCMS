<?php

use App\Modules\Transfer\EvaluationController;
use App\Modules\Transfer\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->prefix('transfer')->group(function () {
    Route::get('/', [TransferController::class, 'index'])->name('transfer');
    Route::get('/new', [TransferController::class, 'create'])->name('transfer.create');
    Route::post('/', [TransferController::class, 'store'])->name('transfer.store');
    Route::get('/{id}', [TransferController::class, 'show'])->whereNumber('id')->name('transfer.show');
    Route::get('/{id}/document/{filename}', [TransferController::class, 'document'])->whereNumber('id')->name('transfer.document');
    Route::post('/{id}/evidence', [TransferController::class, 'evidence'])->whereNumber('id')->name('transfer.evidence');
});
Route::middleware(['auth', 'role:codl_admin,academic_authority,deputy_dean,ptj_authority,qa_officer'])->prefix('transfers')->group(function () {
    Route::get('/', [EvaluationController::class, 'index'])->name('transfers');
    Route::get('/{id}', [EvaluationController::class, 'show'])->whereNumber('id')->name('transfers.show');
    Route::get('/{id}/letter', [EvaluationController::class, 'letter'])->whereNumber('id')->name('transfers.letter');
    Route::get('/{id}/document/{filename}', [EvaluationController::class, 'document'])->whereNumber('id')->name('transfers.document');
});

Route::middleware(['auth', 'role:codl_admin,academic_authority'])->prefix('transfers')->group(function () {
    Route::post('/{id}/validity', [EvaluationController::class, 'validity'])->whereNumber('id')->name('transfers.validity');
    Route::post('/{id}/lines/{line}', [EvaluationController::class, 'evaluate'])->whereNumber(['id', 'line'])->name('transfers.evaluate');
    Route::post('/{id}/more-evidence', [EvaluationController::class, 'moreEvidence'])->whereNumber('id')->name('transfers.more');
    Route::post('/{id}/decide', [EvaluationController::class, 'decide'])->whereNumber('id')->name('transfers.decide');
});

Route::middleware(['auth', 'role:codl_admin,academic_authority,records_custodian,records_depositor'])->prefix('transfers')->group(function () {
    Route::post('/{id}/admission', [EvaluationController::class, 'admit'])->whereNumber('id')->name('transfers.admit');
});

Route::middleware(['auth', 'role:codl_admin,academic_authority,abc_depositor,records_depositor'])->prefix('transfers')->group(function () {
    Route::post('/lines/{line}/redeem', [EvaluationController::class, 'redeem'])->whereNumber('line')->name('transfers.redeem');
    Route::post('/redeem-bulk', [EvaluationController::class, 'redeemBulk'])->name('transfers.redeem.bulk');
});
