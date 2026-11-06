<?php

declare(strict_types=1);

namespace App\Modules\Core;

use Illuminate\Http\Request;

final class SettingsController
{
    public function __construct(private Settings $settings) {}

    public function index()
    {
        $rows = collect($this->settings->definitions())->map(fn ($d, $k) => $d + ['key' => $k, 'current' => $this->settings->get($k)]);

        return view('settings.index', ['rows' => $rows]);
    }

    public function update(Request $request, string $key)
    {
        $data = $request->validate(['value' => 'nullable|string|max:200', 'reason' => 'required|string|min:5|max:500', 'effective_from' => 'nullable|date|after_or_equal:today']);
        $this->settings->set($key, (string) ($data['value'] ?? ''), $data['reason'], $request->user(), $data['effective_from'] ?? null);

        return redirect()->route('settings.history', $key)->with('status', 'Setting saved. The earlier value stays in the history.');
    }

    public function history(string $key)
    {
        abort_unless(isset($this->settings->definitions()[$key]), 404);

        return view('settings.history', ['key' => $key, 'def' => $this->settings->definitions()[$key], 'versions' => $this->settings->history($key)]);
    }
}
