<?php

use App\Modules\CreditBank\CreditBankController;
use Illuminate\Support\Facades\Route;

// Guide Appendix H3 (p. 116):
// - P8 (student): view own ledger, inspect ACR, give/postpone/decline ABC deposit consent (F4)
// - P6/P7 (records_depositor): confirm ACR (F2/F3), export ABC batch, record ABC deposit (F4), log F10 corrections, sign F11 Row 3
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/my-credit', [CreditBankController::class, 'ledger'])->name('credit');
    Route::get('/my-credit/{id}', [CreditBankController::class, 'mine'])->whereNumber('id')->name('credit.acr');
    Route::post('/my-credit/{id}/consent', [CreditBankController::class, 'consent'])->whereNumber('id')->name('credit.consent');
});

// Read-only ACR & ABC browser for university oversight roles + full workspace for ABC/Records Depositors
// (Deputy Dean / PTj Authority remains strictly separated per Guide Appendix H2/H3)
Route::middleware(['auth', 'role:abc_depositor,records_depositor,codl_admin,academic_authority,qa_officer,records_custodian'])->prefix('deposits')->group(function () {
    Route::get('/', [CreditBankController::class, 'queue'])->name('deposits');
    Route::get('/{id}', [CreditBankController::class, 'show'])->whereNumber('id')->name('deposits.show');
});

Route::middleware(['auth', 'role:abc_depositor,records_depositor'])->prefix('deposits')->group(function () {
    Route::post('/export', [CreditBankController::class, 'export'])->name('deposits.export');
    Route::get('/batches/{id}/csv', [CreditBankController::class, 'batchCsv'])->whereNumber('id')->name('deposits.batch.csv');
    Route::post('/confirm-bulk', [CreditBankController::class, 'confirmBulk'])->name('deposits.confirm.bulk');
    Route::post('/record-bulk', [CreditBankController::class, 'recordBulk'])->name('deposits.record.bulk');
    Route::post('/{id}/confirm', [CreditBankController::class, 'confirm'])->whereNumber('id')->name('deposits.confirm');
    Route::post('/{id}/record', [CreditBankController::class, 'record'])->whereNumber('id')->name('deposits.record');
    Route::post('/{id}/correct', [CreditBankController::class, 'correct'])->whereNumber('id')->name('deposits.correct');
});
