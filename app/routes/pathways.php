<?php

use App\Modules\Pathways\DegreeCurriculumController;
use App\Modules\Pathways\EquivalenceController;
use App\Modules\Pathways\PathwayController;
use Illuminate\Support\Facades\Route;

// Public & authenticated degree plans, course details, comparisons, and approved equivalences
Route::get('/programmes', [DegreeCurriculumController::class, 'index'])->name('programmes.index');
Route::get('/programmes/compare', [DegreeCurriculumController::class, 'compare'])->name('programmes.compare');
Route::get('/programmes/{code}', [DegreeCurriculumController::class, 'show'])->name('programmes.show');
Route::get('/courses/{code}', [DegreeCurriculumController::class, 'course'])->where('code', '[^/]+')->name('courses.show');
Route::get('/equivalences', [EquivalenceController::class, 'index'])->name('equivalences');

Route::middleware(['auth', 'role:deputy_dean,ptj_authority,codl_admin,academic_authority'])->group(function () {
    Route::get('/programmes/{code}/manage', [DegreeCurriculumController::class, 'manage'])->name('programmes.manage');
    Route::post('/programmes/{code}/versions/clone', [DegreeCurriculumController::class, 'cloneVersion'])->name('programmes.versions.clone');
    Route::post('/programme-versions/{versionId}/placements', [DegreeCurriculumController::class, 'storePlacement'])->whereNumber('versionId')->name('programmes.placements.store');
    Route::delete('/programme-placements/{placementId}', [DegreeCurriculumController::class, 'destroyPlacement'])->whereNumber('placementId')->name('programmes.placements.destroy');
    Route::post('/programme-versions/{versionId}/prerequisites', [DegreeCurriculumController::class, 'storePrerequisite'])->whereNumber('versionId')->name('programmes.prerequisites.store');
    Route::post('/programme-versions/{versionId}/publish', [DegreeCurriculumController::class, 'publishVersion'])->whereNumber('versionId')->name('programmes.versions.publish');
    Route::post('/courses/{courseId}/status', [DegreeCurriculumController::class, 'updateCourseStatus'])->whereNumber('courseId')->name('courses.status');
});

Route::middleware(['auth', 'role:lecturer,deputy_dean,ptj_authority,codl_admin'])->group(function () {
    Route::post('/equivalences', [EquivalenceController::class, 'store'])->name('equivalences.store');
});
Route::middleware(['auth', 'role:deputy_dean,ptj_authority'])->group(function () {
    Route::post('/equivalences/{id}/endorse', [EquivalenceController::class, 'endorse'])->whereNumber('id')->name('equivalences.endorse');
});
Route::middleware(['auth', 'role:codl_admin'])->group(function () {
    Route::post('/equivalences/{id}/verify', [EquivalenceController::class, 'verify'])->whereNumber('id')->name('equivalences.verify');
});
Route::middleware(['auth', 'role:academic_authority'])->group(function () {
    Route::post('/equivalences/{id}/approve', [EquivalenceController::class, 'approve'])->whereNumber('id')->name('equivalences.approve');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/my-plan', [PathwayController::class, 'mine'])->name('plan.mine');
    Route::get('/my-plan/choose', [PathwayController::class, 'planChoose'])->name('plan.choose');
    Route::get('/my-plan/pathways/{id}', [PathwayController::class, 'planDetail'])->whereNumber('id')->name('plan.detail');
    Route::post('/my-plan', [PathwayController::class, 'planSelfStore'])->name('plan.selfStore');
    Route::get('/my-plan/{id}/edit', [PathwayController::class, 'planSelfEdit'])->whereNumber('id')->name('plan.selfEdit');
    Route::post('/my-plan/{id}', [PathwayController::class, 'planSelfUpdate'])->whereNumber('id')->name('plan.selfUpdate');
    Route::delete('/my-plan/{id}', [PathwayController::class, 'planSelfDestroy'])->whereNumber('id')->name('plan.selfDestroy');
});

Route::middleware(['auth', 'role:lecturer,deputy_dean,ptj_authority,academic_authority'])->group(function () {
    Route::get('/pathways', [PathwayController::class, 'index'])->name('pathways');
    Route::post('/pathways', [PathwayController::class, 'store'])->name('pathways.store');
    Route::get('/pathways/{id}', [PathwayController::class, 'show'])->whereNumber('id')->name('pathways.show');
    Route::post('/pathways/{id}/items', [PathwayController::class, 'addItem'])->whereNumber('id')->name('pathways.item');
    Route::post('/pathways/{id}/items/{item}/remove', [PathwayController::class, 'removeItem'])->whereNumber(['id', 'item'])->name('pathways.remove');
});

Route::middleware(['auth', 'role:deputy_dean,ptj_authority,academic_authority'])->group(function () {
    Route::get('/plans/new', [PathwayController::class, 'planForm'])->name('plans.new');
    Route::post('/plans', [PathwayController::class, 'planStore'])->name('plans.store');
    Route::get('/plans/{id}', [PathwayController::class, 'planShow'])->whereNumber('id')->name('plans.show');
    Route::post('/plans/{id}', [PathwayController::class, 'planUpdate'])->whereNumber('id')->name('plans.update');
    Route::post('/pathways/{id}/approve', [PathwayController::class, 'approve'])->whereNumber('id')->name('pathways.approve');
    Route::post('/pathways/{id}/publish', [PathwayController::class, 'publish'])->whereNumber('id')->name('pathways.publish');
    Route::post('/plans/{id}/decision', [PathwayController::class, 'planDecision'])->whereNumber('id')->name('plans.decision');
    Route::post('/plans/{id}/award', [PathwayController::class, 'planAward'])->whereNumber('id')->name('plans.award');
});
