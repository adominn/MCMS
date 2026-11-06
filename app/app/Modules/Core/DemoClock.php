<?php

declare(strict_types=1);

namespace App\Modules\Core;

use App\Modules\Achievement\CompletionWindow;
use App\Modules\CreditBank\CreditLedger;
use App\Modules\Enrolment\Enrolments;
use App\Modules\Monitoring\PeriodicReviews;
use App\Modules\Offering\OfferingStatus;
use App\Modules\Quality\QvAlerts;
use App\Modules\Records\Discrepancies;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

// PRD 7.2: a demo clock that moves the date forward so that registration, Quality Verification and credit expiry can be shown.
// It exists only in a test environment: it needs the test sign-in and refuses to work in production (FR-devidp, PRD 7.4).
final class DemoClock
{
    public static function enabled(): bool
    {
        return (bool) config('mcms.demo_clock') && (bool) config('mcms.stub_idp') && ! app()->isProduction();
    }

    /** Middleware: after the session timeout has looked at the real time, the rest of the request sees the moved date. */
    public function handle(Request $request, Closure $next)
    {
        if (! self::enabled() || ($days = (int) app(Settings::class)->get('demo.clock_offset_days')) === 0) {
            return $next($request);
        }
        $before = Carbon::getTestNow();
        Carbon::setTestNow(); // from the real time, so that the days are never added twice
        Carbon::setTestNow(Carbon::now()->addDays($days));
        try {
            return $next($request);
        } finally {
            Carbon::setTestNow($before); // the moved date lasts for this request only
        }
    }

    /** The daily jobs, run in order, as the scheduler would on each of the days that passed. @return array<string, int> */
    public static function runDailyJobs(): array
    {
        return [
            'Open and close registration' => (function () { app(OfferingStatus::class)->refresh(); return 0; })(),
            'Cancel unpaid registrations' => (int) app(Enrolments::class)->cancelUnpaid(),
            'Completion window reminders' => (int) app(CompletionWindow::class)->remind(),
            'Quality Verification expiry alerts' => app(QvAlerts::class)->alert(),
            'Lapse expired Quality Verification' => app(QvAlerts::class)->lapse(),
            'Credit expiry alerts' => app(CreditLedger::class)->alert(),
            'Expire credits' => app(CreditLedger::class)->expire(),
            'Periodic review reminders' => app(PeriodicReviews::class)->remind(),
            'Scan for discrepancies' => app(Discrepancies::class)->scan(),
        ];
    }
}
