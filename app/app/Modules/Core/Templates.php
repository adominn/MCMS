<?php

declare(strict_types=1);

namespace App\Modules\Core;

use App\Models\User;
use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;

// FR-22: templates in both languages, versioned. A change adds a version; nothing is overwritten.
final class Templates
{
    public const LOCALES = ['en' => 'English', 'ms' => 'Bahasa Melayu'];

    public const DEFAULTS = [
        'statement.catalogue.umc' => [
            'en' => [null, 'This is an Unbundled Micro-Credential (UMC) from {parent}. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.'],
            'ms' => [null, 'Ini ialah Unbundled Micro-Credential (UMC) daripada {parent}. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.'],
        ],
        'statement.catalogue.samc' => [
            'en' => [null, 'This is a Standalone Micro-Credential (SAMC). It carries no credit value and no MQF level unless it is Quality-Verified and the verification is valid.'],
            'ms' => [null, 'Ini ialah Standalone Micro-Credential (SAMC). Ia tidak membawa nilai kredit atau tahap MQF melainkan telah disahkan melalui Quality Verification dan pengesahan itu masih sah.'],
        ],
        'statement.catalogue.samc_qv' => [
            'en' => [null, 'This SAMC is Quality-Verified. Credit {credit}, MQF level {mqf}, valid until {until}.'],
            'ms' => [null, 'SAMC ini telah disahkan melalui Quality Verification. Kredit {credit}, tahap MQF {mqf}, sah hingga {until}.'],
        ],
        'statement.catalogue.mce' => [
            'en' => [null, 'This is an Executive Micro-Credential (MCE). It earns no academic credit, no Academic Credit Record and no deposit to the Academic Bank of Credit.'],
            'ms' => [null, 'Ini ialah Micro-Credential Eksekutif (MCE). Ia tidak memberi kredit akademik, Academic Credit Record atau deposit ke Academic Bank of Credit.'],
        ],
        'statement.separate_stages' => [
            'en' => [null, 'Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.'],
            'ms' => [null, 'Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.'],
        ],
        'consent.abc_deposit' => [
            'en' => ['ABC Deposit Consent Statement', 'I consent to the deposit of my Academic Credit Record into the Academic Bank of Credit.'],
            'ms' => ['Penyata Persetujuan Deposit ABC', 'Saya bersetuju mendepositkan Rekod Kredit Akademik saya ke Bank Kredit Akademik (ABC).'],
        ],
    ];

    public function __construct(private AuditRecorder $audit) {}

    public function current(string $key, string $locale): ?object
    {
        $id = DB::table('templates')->where('key', $key)->value('id');
        if ($id) {
            $v = DB::table('template_versions')->where('template_id', $id)->whereIn('locale', array_unique([$locale, 'en']))
                ->orderByRaw("locale = ? desc", [$locale])->orderByDesc('version')->first();
            if ($v) {
                return $v;
            }
        }

        if (isset(self::DEFAULTS[$key])) {
            $def = self::DEFAULTS[$key][$locale] ?? self::DEFAULTS[$key]['en'];

            return (object) ['subject' => $def[0], 'body' => $def[1], 'version' => 1, 'locale' => $locale];
        }

        return null;
    }

    // [subject, body] with {placeholders} filled; falls back to English when the language has no version.
    public function render(string $key, string $locale, array $vars): array
    {
        $v = $this->current($key, $locale);
        $fill = fn (?string $s) => preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($vars[$m[1]] ?? $m[0]), (string) $s);

        if ($v) {
            return [$fill($v->subject), $fill($v->body)];
        }

        if (isset(self::DEFAULTS[$key])) {
            $def = self::DEFAULTS[$key][$locale] ?? self::DEFAULTS[$key]['en'];

            return [$fill($def[0]), $fill($def[1])];
        }

        throw new \InvalidArgumentException("Unknown template $key");
    }

    public function history(int $templateId)
    {
        return DB::table('template_versions as v')->leftJoin('users as u', 'u.id', '=', 'v.set_by')->where('v.template_id', $templateId)
            ->orderBy('v.locale')->orderByDesc('v.version')->get(['v.*', 'u.name as set_by_name']);
    }

    public function addVersion(int $templateId, string $locale, ?string $subject, string $body, string $reason, ?User $by): void
    {
        DB::transaction(function () use ($templateId, $locale, $subject, $body, $reason, $by) {
            $next = 1 + (int) DB::table('template_versions')->where('template_id', $templateId)->where('locale', $locale)->max('version');
            DB::table('template_versions')->insert(['template_id' => $templateId, 'locale' => $locale, 'version' => $next, 'subject' => $subject, 'body' => $body, 'set_by' => $by?->id, 'reason' => $reason, 'created_at' => now()]);
            $key = DB::table('templates')->where('id', $templateId)->value('key');
            $this->audit->record('template.changed', ['type' => 'Template', 'id' => $key], after: ['locale' => $locale, 'version' => $next], reason: $reason);
        });
    }
}
