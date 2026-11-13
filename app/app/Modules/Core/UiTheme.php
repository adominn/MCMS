<?php

declare(strict_types=1);

namespace App\Modules\Core;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UiTheme
{
    /**
     * Ensure the session has the user's saved theme preference when authenticated.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->session()->has('ui_theme')) {
            $pref = $request->user()->theme_preference ?: 'auto';
            $request->session()->put('ui_theme', $pref);
        }

        return $next($request);
    }

    /**
     * Switch the theme via POST /theme.
     */
    public function switch(Request $request)
    {
        $theme = $request->input('theme');
        if (! in_array($theme, ['auto', 'light', 'dark'], true)) {
            $theme = 'auto';
        }

        $request->session()->put('ui_theme', $theme);

        if ($u = $request->user()) {
            DB::table('users')->where('id', $u->id)->update([
                'theme_preference' => $theme,
                'updated_at' => now(),
            ]);
        }

        if ($request->expectsJson() || $request->isJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'theme' => $theme,
            ]);
        }

        return back();
    }
}
