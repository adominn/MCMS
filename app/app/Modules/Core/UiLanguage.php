<?php

declare(strict_types=1);

namespace App\Modules\Core;

use Closure;
use Illuminate\Http\Request;

// UI/UX 4.7: the EN/BM switch. The choice is kept in the session; navigation, menus and page headings are translated (lang/ms.json), the rest falls back to English.
class UiLanguage
{
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale(session('ui_lang', session('lang')) === 'ms' ? 'ms' : 'en');

        return $next($request);
    }

    public function switch(Request $request)
    {
        $lang = $request->input('lang') === 'ms' ? 'ms' : 'en';
        $request->session()->put(['ui_lang' => $lang, 'lang' => $lang]);

        return back();
    }
}
