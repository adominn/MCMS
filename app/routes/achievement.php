<?php

use App\Modules\Achievement\CompletionController;
use App\Modules\Achievement\RecordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:deputy_dean'])->group(function () {
    Route::get('/completion/{id}', [CompletionController::class, 'show'])->whereNumber('id')->name('completion.show');
    Route::post('/completion/{id}', [CompletionController::class, 'confirm'])->whereNumber('id')->name('completion.confirm');
});
Route::middleware(['auth', 'role:student'])->get('/my-record', [RecordController::class, 'mine'])->name('records.mine');
Route::middleware(['auth', 'role:student,lecturer,deputy_dean'])->get('/records/{enrolment}', [RecordController::class, 'show'])->whereNumber('enrolment')->name('records.show');
