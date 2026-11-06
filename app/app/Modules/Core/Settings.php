<?php

declare(strict_types=1);

namespace App\Modules\Core;

use App\Models\User;
use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// MD-00: versioned settings. Every change adds a version with a reason; nothing is overwritten (FR-20, FR-21, D-16).
final class Settings
{
    public function __construct(private AuditRecorder $audit) {}

    public function definitions(): array
    {
        return config('settings-defaults');
    }

    public function get(string $key): mixed
    {
        $v = DB::table('setting_versions as v')->join('settings as s', 's.id', '=', 'v.setting_id')
            ->where('s.key', $key)->where('v.effective_from', '<=', now())
            ->orderByDesc('v.effective_from')->orderByDesc('v.id')->value('v.value');

        return $v === null ? ($this->definitions()[$key]['value'] ?? null) : json_decode($v, true);
    }

    public function set(string $key, string $raw, string $reason, User $by, ?string $effectiveFrom = null): void
    {
        $def = $this->definitions()[$key] ?? throw ValidationException::withMessages(['key' => 'Unknown setting.']);
        $value = $this->cast($def['type'], $raw);
        $before = $this->get($key);

        DB::transaction(function () use ($key, $value, $reason, $by, $before, $effectiveFrom) {
            $setting = DB::table('settings')->where('key', $key)->first();
            $prev = DB::table('setting_versions')->where('setting_id', $setting->id)->orderByDesc('effective_from')->orderByDesc('id')->value('id');
            DB::table('setting_versions')->insert([
                'setting_id' => $setting->id, 'value' => json_encode($value), 'effective_from' => $effectiveFrom ?? now(),
                'set_by' => $by->id, 'reason' => $reason, 'supersedes_id' => $prev,
            ]);
            $this->audit->record('setting.changed', ['type' => 'Setting', 'id' => $key], ['value' => $before], ['value' => $value, 'effective_from' => $effectiveFrom ?? 'now'], $reason);
        });
    }

    public function history(string $key)
    {
        return DB::table('setting_versions as v')->join('settings as s', 's.id', '=', 'v.setting_id')
            ->leftJoin('users as u', 'u.id', '=', 'v.set_by')->where('s.key', $key)
            ->orderByDesc('v.effective_from')->orderByDesc('v.id')
            ->get(['v.id', 'v.value', 'v.effective_from', 'v.reason', 'u.name as set_by'])
            ->map(fn ($r) => tap($r, fn ($r) => $r->value = json_decode($r->value, true)));
    }

    public static function show(mixed $v): string
    {
        return $v === null ? 'No value yet' : (is_array($v) ? implode(', ', $v) : (string) $v);
    }

    private function cast(string $type, string $raw): mixed
    {
        $raw = trim($raw);
        $bad = fn (string $m) => ValidationException::withMessages(['value' => $m]);
        $int = fn (string $s) => preg_match('/^\d+$/', $s) ? (int) $s : throw $bad('Enter a whole number, zero or more.');

        return match ($type) {
            'int' => $int($raw),
            'int_or_empty' => $raw === '' ? null : $int($raw),
            'int_list' => array_map(fn ($s) => $int(trim($s)), explode(',', $raw)),
            'role' => in_array($raw, ['deputy_dean', 'lecturer'], true) ? $raw : throw $bad('Choose an academic role: deputy_dean or lecturer. The administrator role cannot decide academic matters.'),
            'text_list' => ($l = array_values(array_filter(array_map('trim', explode(',', $raw))))) ? $l : throw $bad('Enter at least one item, separated by commas.'),
        };
    }
}
