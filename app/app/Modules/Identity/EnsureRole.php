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
        $user = $request->user();
        if (! $user?->hasAnyRole(...$roles)) {
            if ($redirect = AccessHandoff::redirectFor($request, $user)) {
                return $redirect;
            }

            $handoff = AccessHandoff::resolve($request, $user);
            $request->attributes->set('handoff', $handoff);
            abort(403, $handoff['text']);
        }

        return $next($request);
    }
}
