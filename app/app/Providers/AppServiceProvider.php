<?php

namespace App\Providers;

use App\Modules\Notifications\Adapters\EmailAdapter;
use App\Modules\Notifications\Adapters\SmtpEmailAdapter;
use App\Modules\Notifications\Adapters\StubEmailAdapter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Modules\Enrolment\PaymentAdapter::class, fn () => new \App\Modules\Enrolment\StubPaymentAdapter); // the dummy service until the UTeM payment hub is ready (A-03)
        // The adapter in use comes from config/integrations.php (IR-16).
        $this->app->bind(EmailAdapter::class, fn () => config('integrations.modes.email') === 'live' ? new SmtpEmailAdapter : new StubEmailAdapter);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(\App\Modules\Achievement\Events\CompletionConfirmed::class, [\App\Modules\Credentials\IssueCredential::class, 'handle']);
        // SR-19: a stub flag active in production is a configuration error, not a warning.
        if ($this->app->isProduction() && config('mcms.stub_idp')) {
            throw new \RuntimeException('MCMS_STUB_IDP must be off in production.');
        }
    }
}
