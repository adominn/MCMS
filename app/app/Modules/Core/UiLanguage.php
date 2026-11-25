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
        $sessionLang = session('ui_lang', session('lang'));
        if (! $sessionLang && $request->user() && $request->user()->locale) {
            $sessionLang = $request->user()->locale;
            session(['ui_lang' => $sessionLang, 'lang' => $sessionLang]);
        }

        $docLang = $request->query('doc_lang');
        if (! in_array($docLang, ['en', 'ms'], true) && $request->routeIs('credentials.certificate') && in_array($request->query('lang'), ['en', 'ms'], true)) {
            $docLang = $request->query('lang');
        }

        if (in_array($docLang, ['en', 'ms'], true)) {
            app()->setLocale($docLang);
        } else {
            app()->setLocale($sessionLang === 'ms' ? 'ms' : 'en');
        }

        return $next($request);
    }

    public function switch(Request $request)
    {
        $lang = $request->input('lang') === 'ms' ? 'ms' : 'en';
        $request->session()->put(['ui_lang' => $lang, 'lang' => $lang]);

        if ($request->user()) {
            $request->user()->update(['locale' => $lang]);
        }

        return back();
    }
}
