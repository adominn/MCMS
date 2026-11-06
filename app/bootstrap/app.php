<?php

if (! function_exists('db_like')) {
    function db_like(): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
use App\Modules\Identity\EnsureRole;
use App\Modules\Identity\IdleTimeout;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->appendToGroup('web', IdleTimeout::class); // after the session starts
        $middleware->appendToGroup('web', \App\Modules\Core\UiLanguage::class);
        $middleware->appendToGroup('web', \App\Modules\Core\DemoClock::class); // after the idle check, which uses the real time
        $middleware->redirectGuestsTo(fn () => route('signin'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['card']);
    })->create();
