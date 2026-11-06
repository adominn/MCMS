<?php

declare(strict_types=1);

namespace App\Modules\Core;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class TemplateController
{
    public function __construct(private Templates $templates) {}

    public function index()
    {
        return view('admin.templates.index', ['templates' => DB::table('templates')->orderBy('kind')->orderBy('key')->get()]);
    }

    public function edit(string $key)
    {
        $t = DB::table('templates')->where('key', $key)->first() ?? abort(404);

        return view('admin.templates.edit', ['t' => $t, 'history' => $this->templates->history($t->id), 'locales' => Templates::LOCALES,
            'current' => collect(Templates::LOCALES)->map(fn ($n, $l) => $this->templates->current($key, $l))]);
    }

    public function update(Request $request, string $key)
    {
        $t = DB::table('templates')->where('key', $key)->first() ?? abort(404);
        $d = $request->validate(['locale' => 'required|in:en,ms', 'subject' => $t->kind === 'notification' ? 'required|string|max:200' : 'nullable|string|max:200', 'body' => 'required|string|max:5000', 'reason' => 'required|string|min:5|max:500']);
        $this->templates->addVersion($t->id, $d['locale'], $d['subject'] ?? null, $d['body'], $d['reason'], $request->user());

        return redirect()->route('templates.edit', $key)->with('status', 'New version saved. The earlier version stays in the history.');
    }
}
