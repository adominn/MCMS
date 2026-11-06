<?php

use App\Modules\Core\ReferenceDataController;
use App\Modules\Core\SettingsController;
use App\Modules\Core\TemplateController;
use App\Modules\Identity\SearchController;
use App\Modules\Identity\StubSignInController;
use App\Modules\Identity\TaskController;
use App\Modules\Identity\UserAdminController;
use App\Modules\Notifications\AdapterController;
use App\Modules\Notifications\NotificationController;
use App\Modules\Records\AuditController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/curriculum.php';
require __DIR__.'/approval.php';
require __DIR__.'/versions.php';
require __DIR__.'/offerings.php';
require __DIR__.'/enrolment.php';
require __DIR__.'/results.php';
require __DIR__.'/achievement.php';
require __DIR__.'/credentials.php';
require __DIR__.'/quality.php';
require __DIR__.'/creditbank.php';
require __DIR__.'/transfer.php';
require __DIR__.'/pathways.php';
require __DIR__.'/monitoring.php';
require __DIR__.'/records.php';

Route::get('/', [StubSignInController::class, 'home']);
Route::get('/signin', [StubSignInController::class, 'index'])->name('signin');
Route::post('/signin/stub', [StubSignInController::class, 'store'])->name('signin.stub');
Route::get('/signin/mfa', [StubSignInController::class, 'mfa'])->name('signin.mfa');
Route::post('/signin/mfa', [StubSignInController::class, 'mfaCheck']);

Route::post('/language', [\App\Modules\Core\UiLanguage::class, 'switch'])->name('language');
Route::middleware('auth')->group(function () {
    Route::post('/signout', [StubSignInController::class, 'destroy'])->name('signout');
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/profile', [SearchController::class, 'profile'])->name('profile');
    Route::post('/profile/preferences', [SearchController::class, 'updatePreferences'])->name('profile.preferences');
    Route::post('/profile/data-correction', [SearchController::class, 'requestCorrection'])->name('profile.data_correction');
    Route::post('/profile/sessions/terminate', [SearchController::class, 'terminateSession'])->name('profile.sessions.terminate');
    Route::get('/help', [SearchController::class, 'help'])->name('help');
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
    Route::get('/tasks/{id}', [TaskController::class, 'show'])->whereNumber('id')->name('tasks.show');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->whereNumber('id')->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readall');
    Route::post('/notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.prefs');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->whereNumber('id')->name('notifications.read');

    Route::middleware('role:codl_admin')->prefix('admin')->group(function () {
        Route::get('/users', [UserAdminController::class, 'index'])->name('users');
        Route::get('/users/{user}', [UserAdminController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/roles', [UserAdminController::class, 'assign'])->name('users.assign');
        Route::post('/users/{user}/roles/{assignment}/revoke', [UserAdminController::class, 'revoke'])->name('users.revoke');
        Route::post('/users/{user}/status/{to}', [UserAdminController::class, 'setStatus'])->name('users.status');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::get('/settings/{key}', [SettingsController::class, 'history'])->where('key', '[a-z_.]+')->name('settings.history');
        Route::post('/settings/{key}', [SettingsController::class, 'update'])->where('key', '[a-z_.]+')->name('settings.update');
        Route::get('/reference/{entity}', [ReferenceDataController::class, 'index'])->name('reference');
        Route::post('/reference/{entity}', [ReferenceDataController::class, 'store'])->name('reference.store');
        Route::post('/reference/{entity}/import', [ReferenceDataController::class, 'import'])->name('reference.import');
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates');
        Route::get('/templates/{key}', [TemplateController::class, 'edit'])->where('key', '[a-z_.]+')->name('templates.edit');
        Route::post('/templates/{key}', [TemplateController::class, 'update'])->where('key', '[a-z_.]+')->name('templates.update');
        Route::get('/adapters', [AdapterController::class, 'index'])->name('adapters');
        Route::post('/adapters/calls/{id}/retry', [AdapterController::class, 'retry'])->whereNumber('id')->name('adapters.retry');
        Route::get('/notification-log', [NotificationController::class, 'log'])->name('notification.log');
        Route::get('/demo-clock', [\App\Modules\Core\DemoClockController::class, 'show'])->name('demo.clock');
        Route::post('/demo-clock', [\App\Modules\Core\DemoClockController::class, 'move'])->name('demo.clock.move');
        Route::post('/demo-clock/jobs', [\App\Modules\Core\DemoClockController::class, 'jobs'])->name('demo.clock.jobs');
        Route::get('/audit', [AuditController::class, 'index'])->name('audit');
        Route::post('/audit/verify', [AuditController::class, 'verify'])->name('audit.verify');
    });
});
