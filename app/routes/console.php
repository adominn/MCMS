<?php

use App\Modules\Notifications\IntegrationHub;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => app(IntegrationHub::class)->retryDue())->everyMinute()->name('integration-retry');
Schedule::call(fn () => app(\App\Modules\Enrolment\Enrolments::class)->cancelUnpaid())->hourly()->name('cancel-unpaid-enrolments'); // JB-20
Schedule::call(fn () => app(\App\Modules\Achievement\CompletionWindow::class)->remind())->dailyAt('07:40')->name('completion-window-reminders'); // JB-11
Schedule::call(fn () => app(\App\Modules\Quality\QvAlerts::class)->alert())->dailyAt('07:50')->name('qv-expiry-alerts'); // JB-06
Schedule::call(fn () => app(\App\Modules\Quality\QvAlerts::class)->lapse())->dailyAt('00:10')->name('lapse-expired-qv'); // JB-08
Schedule::call(fn () => app(\App\Modules\CreditBank\CreditLedger::class)->alert())->dailyAt('07:55')->name('credit-expiry-alerts'); // JB-07
Schedule::call(fn () => app(\App\Modules\CreditBank\CreditLedger::class)->expire())->dailyAt('00:15')->name('expire-credits'); // JB-09
Schedule::call(fn () => app(\App\Modules\Monitoring\PeriodicReviews::class)->remind())->dailyAt('07:35')->name('periodic-review-reminders'); // JB-12
Schedule::call(fn () => app(\App\Modules\Records\Discrepancies::class)->scan())->dailyAt('01:30')->name('scan-discrepancies');
Schedule::call(fn () => app(\App\Modules\Records\AuditRecorder::class)->record('audit.integrity_checked', context: ['result' => app(\App\Modules\Records\AuditRecorder::class)->verify() === null ? 'intact' : 'broken', 'by' => 'JB-18']))->dailyAt('02:00')->name('verify-audit-chain'); // JB-18
Schedule::call(fn () => app(\App\Modules\Records\Retention::class)->candidates())->weeklyOn(1, '03:00')->name('list-disposal-candidates'); // JB-19
Schedule::call(fn () => app(\App\Modules\Offering\OfferingStatus::class)->refresh())->dailyAt('00:05')->name('open-close-registration'); // JB-21
