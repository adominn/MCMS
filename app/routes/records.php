<?php

use App\Modules\CreditBank\CreditBankController;
use App\Modules\Records\RecordsController;
use App\Modules\Records\RetentionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get('/documents', [RecordsController::class, 'documents'])->name('documents');

Route::middleware(['auth', 'role:lecturer,deputy_dean,codl_admin'])->prefix('records')->group(function () {
    Route::get('/reports', [RecordsController::class, 'reports'])->name('reports');
    Route::get('/reports/{kind}', [RecordsController::class, 'report'])->where('kind', '[a-z]+')->name('reports.show');
    Route::get('/reports/{kind}/print', [RecordsController::class, 'reportPrint'])->where('kind', '[a-z]+')->name('reports.print');
    Route::get('/reports/{kind}/csv', [RecordsController::class, 'reportCsv'])->where('kind', '[a-z]+')->name('reports.csv');
});
Route::middleware(['auth', 'role:lecturer,deputy_dean'])->prefix('records')->group(function () {
    Route::get('/trace', [RecordsController::class, 'traceIndex'])->name('trace');
    Route::get('/trace/{enrolment}', [RecordsController::class, 'traceShow'])->whereNumber('enrolment')->name('trace.show');
    Route::get('/cohort/{offering}', [RecordsController::class, 'cohort'])->whereNumber('offering')->name('cohort.records');
    Route::get('/audit', [RecordsController::class, 'audit'])->name('records.audit');
    Route::get('/audit/print', [RecordsController::class, 'auditPrint'])->name('records.audit.print');
    Route::get('/audit/csv', [RecordsController::class, 'auditCsv'])->name('records.audit.csv');
});
Route::middleware(['auth', 'role:deputy_dean'])->prefix('records')->group(function () {
    Route::get('/discrepancies', [RecordsController::class, 'discrepancies'])->name('discrepancies');
    Route::post('/discrepancies/scan', [RecordsController::class, 'scan'])->name('discrepancies.scan');
    Route::post('/discrepancies/{id}/resolve', [RecordsController::class, 'resolve'])->whereNumber('id')->name('discrepancies.resolve');
});
Route::middleware(['auth', 'role:deputy_dean'])->get('/deposits/batches/{id}', [CreditBankController::class, 'batch'])->whereNumber('id')->name('deposits.batch');
Route::middleware(['auth', 'role:codl_admin,deputy_dean'])->prefix('records/retention')->group(function () {
    Route::get('/', [RetentionController::class, 'index'])->name('retention');
    Route::post('/holds', [RetentionController::class, 'hold'])->name('retention.hold');
    Route::post('/holds/{id}/lift', [RetentionController::class, 'lift'])->whereNumber('id')->name('retention.lift');
    Route::post('/{category}/dispose', [RetentionController::class, 'dispose'])->where('category', '[a-z_]+')->name('retention.dispose');
});
