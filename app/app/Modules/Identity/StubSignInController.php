<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Models\User;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// FR-05: a stub identity provider for development and demonstration. Entra ID replaces it later (OI-03).
final class StubSignInController
{
    public function __construct(private AuditRecorder $audit) {}

    public function home(Request $request)
    {
        return $request->user() ? redirect()->to($request->user()->homeUrl()) : redirect()->route('signin');
    }

    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('tasks');
        }

        return view('signin', [
            'stub' => config('mcms.stub_idp'),
            'people' => config('mcms.stub_idp') ? User::with('roleAssignments')->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(config('mcms.stub_idp'), 404);
        $user = User::findOrFail($request->validate(['user' => 'required|integer'])['user']);
        if ($user->status !== 'active') {
            $this->audit->record('auth.refused_inactive', $user, actorId: $user->id);

            return redirect()->route('signin')->withErrors(['user' => 'This account is deactivated. Ask the CODL administrator.']);
        }

        return $this->finish($request, $user, 'stub');
    }

    public function mfa(Request $request)
    {
        return redirect()->route('tasks');
    }

    // The stub accepts any six digits; the real provider enforces the second step (SR-02).
    public function mfaCheck(Request $request)
    {
        return redirect()->route('tasks');
    }

    private function finish(Request $request, User $user, string $method)
    {
        $request->session()->regenerate();
        Auth::login($user);
        session(['active_role' => $user->activeRoles()->first()?->value, 'last_activity' => now()->timestamp]);
        $user->forceFill(['last_login_at' => now()])->save();
        $this->audit->record('auth.signed_in', $user, context: ['method' => $method], actorId: $user->id);

        $intended = $request->session()->pull('url.intended');
        if ($intended && self::canAccessUrl($request, $user, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route('tasks');
    }

    public static function canAccessUrl(Request $request, User $user, string $url): bool
    {
        try {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && $host !== $request->getHost()) {
                return false;
            }

            $path = parse_url($url, PHP_URL_PATH);
            if (! $path || in_array($path, ['/signin', '/signout', '/mfa', '/signin/mfa'], true)) {
                return false;
            }

            $tempRequest = Request::create($url, 'GET');
            $route = app('router')->getRoutes()->match($tempRequest);
            $middlewares = $route->gatherMiddleware();

            foreach ($middlewares as $mw) {
                if (is_string($mw) && str_starts_with($mw, 'role:')) {
                    $allowedRoles = explode(',', substr($mw, 5));
                    if (! $user->hasAnyRole(...$allowedRoles)) {
                        return false;
                    }
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function destroy(Request $request)
    {
        if ($user = $request->user()) {
            $this->audit->record('auth.signed_out', $user);
        }
        Auth::logout();
        $request->session()->forget('url.intended');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin');
    }
}
