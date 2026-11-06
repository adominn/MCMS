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
        return $request->user() ? redirect()->route('tasks') : redirect()->route('signin');
    }

    public function index()
    {
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
        if ($user->activeRoles()->contains(fn (Role $r) => $r->requiresMfa())) {
            session(['mfa_pending' => $user->id]);

            return redirect()->route('signin.mfa');
        }

        return $this->finish($request, $user, 'stub');
    }

    public function mfa(Request $request)
    {
        abort_unless($request->session()->has('mfa_pending'), 404);

        return view('mfa');
    }

    // The stub accepts any six digits; the real provider enforces the second step (SR-02).
    public function mfaCheck(Request $request)
    {
        abort_unless(config('mcms.stub_idp') && $request->session()->has('mfa_pending'), 404);
        $request->validate(['code' => ['required', 'regex:/^\d{6}$/']], ['code.regex' => 'Enter the 6-digit code.']);
        $user = User::findOrFail($request->session()->pull('mfa_pending'));

        return $this->finish($request, $user, 'stub+mfa');
    }

    private function finish(Request $request, User $user, string $method)
    {
        $request->session()->regenerate();
        Auth::login($user);
        session(['active_role' => $user->activeRoles()->first()?->value, 'last_activity' => now()->timestamp]);
        $user->forceFill(['last_login_at' => now()])->save();
        $this->audit->record('auth.signed_in', $user, context: ['method' => $method], actorId: $user->id);

        $intended = $request->session()->pull('url.intended');
        if ($intended && $this->canAccessIntendedUrl($request, $user, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route('tasks');
    }

    private function canAccessIntendedUrl(Request $request, User $user, string $url): bool
    {
        try {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && $host !== $request->getHost()) {
                return false;
            }

            $path = parse_url($url, PHP_URL_PATH);
            if (! $path || in_array($path, ['/signin', '/signout', '/mfa'], true)) {
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
