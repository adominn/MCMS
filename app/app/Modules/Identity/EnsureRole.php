<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use Closure;
use Illuminate\Http\Request;

// Authorisation on every request (SR-03): the signed-in user must hold one of the named roles.
final class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        abort_unless($request->user()?->hasAnyRole(...$roles), 403, 'Your role cannot open this page.');

        return $next($request);
    }
}
