<?php

use App\Modules\Approval\ApprovalController;
use App\Modules\Approval\ReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:lecturer,industry_expert'])->prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->name('reviews');
    Route::get('/{assignment}', [ReviewController::class, 'show'])->whereNumber('assignment')->name('reviews.show');
    Route::post('/{assignment}/coi', [ReviewController::class, 'coi'])->whereNumber('assignment')->name('reviews.coi');
    Route::post('/{assignment}/submit', [ReviewController::class, 'submit'])->whereNumber('assignment')->name('reviews.submit');
    Route::post('/{assignment}/comment', [ReviewController::class, 'comment'])->whereNumber('assignment')->name('reviews.comment');
});

Route::middleware(['auth', 'role:lecturer'])->post('/proposals/{id}/comments/{comment}/respond', [ReviewController::class, 'respond'])->whereNumber(['id', 'comment'])->name('comments.respond');

Route::middleware(['auth', 'role:deputy_dean'])->prefix('approvals')->group(function () {
    Route::get('/', [ApprovalController::class, 'index'])->name('approvals');
    Route::get('/{id}', [ApprovalController::class, 'show'])->whereNumber('id')->name('approvals.show');
    Route::post('/{id}/reviewers', [ApprovalController::class, 'assign'])->whereNumber('id')->name('approvals.assign');
    Route::post('/{id}/comments/{comment}/resolve', [ApprovalController::class, 'resolve'])->whereNumber(['id', 'comment'])->name('approvals.resolve');
    Route::post('/{id}/endorse', [ApprovalController::class, 'endorse'])->whereNumber('id')->name('approvals.endorse');
    Route::post('/{id}/{how}', [ApprovalController::class, 'sendBack'])->whereNumber('id')->whereIn('how', ['return', 'reject'])->name('approvals.sendback');
    Route::get('/{id}/pack', [ApprovalController::class, 'pack'])->whereNumber('id')->name('approvals.pack');
    Route::get('/{id}/senate', [ApprovalController::class, 'senateForm'])->whereNumber('id')->name('approvals.senate');
    Route::post('/{id}/senate', [ApprovalController::class, 'senate'])->whereNumber('id');
});
