<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Modules\Core\Settings;
use App\Modules\Records\AuditRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// FR-06: end an idle session after auth.idle_timeout_minutes.
final class IdleTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            $limit = (int) app(Settings::class)->get('auth.idle_timeout_minutes');
            $last = $request->session()->get('last_activity');
            if ($limit > 0 && $last && now()->timestamp - $last > $limit * 60) {
                app(AuditRecorder::class)->record('auth.session_expired', $user, actorId: $user->id);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('signin')->with('status', 'You were signed out after a period of inactivity. Please sign in again.');
            }
            $request->session()->put('last_activity', now()->timestamp);
        }

        return $next($request);
    }
}
