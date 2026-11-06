<?php

use App\Modules\CreditBank\CreditBankController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/my-credit', [CreditBankController::class, 'ledger'])->name('credit');
    Route::get('/my-credit/{id}', [CreditBankController::class, 'mine'])->whereNumber('id')->name('credit.acr');
    Route::post('/my-credit/{id}/consent', [CreditBankController::class, 'consent'])->whereNumber('id')->name('credit.consent');
});
Route::middleware(['auth', 'role:deputy_dean'])->prefix('deposits')->group(function () {
    Route::get('/', [CreditBankController::class, 'queue'])->name('deposits');
    Route::post('/export', [CreditBankController::class, 'export'])->name('deposits.export');
    Route::get('/batches/{id}/csv', [CreditBankController::class, 'batchCsv'])->whereNumber('id')->name('deposits.batch.csv');
    Route::get('/{id}', [CreditBankController::class, 'show'])->whereNumber('id')->name('deposits.show');
    Route::post('/{id}/confirm', [CreditBankController::class, 'confirm'])->whereNumber('id')->name('deposits.confirm');
    Route::post('/{id}/record', [CreditBankController::class, 'record'])->whereNumber('id')->name('deposits.record');
});
