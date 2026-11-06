<?php

declare(strict_types=1);

namespace App\Modules\Enrolment;

use App\Modules\Core\Templates;
use Illuminate\Support\Facades\DB;

// The recognition wording of an offering: what the catalogue shows and what a participant acknowledges (FR-93, FR-99).
final class Recognition
{
    public const DEFAULTS = [
        'statement.catalogue.umc' => [
            'en' => 'This is an Unbundled Micro-Credential (UMC) from {parent}. Completing the whole UMC carries the credit of the parent course. A module or a badge on its own is not completion and not credit.',
            'ms' => 'Ini ialah Unbundled Micro-Credential (UMC) daripada {parent}. Menamatkan keseluruhan UMC membawa kredit kursus induk. Satu modul atau satu lencana sahaja bukan penamatan dan bukan kredit.',
        ],
        'statement.catalogue.samc' => [
            'en' => 'This is a Standalone Micro-Credential (SAMC). It carries no credit value and no MQF level unless it is Quality-Verified and the verification is valid.',
            'ms' => 'Ini ialah Standalone Micro-Credential (SAMC). Ia tidak membawa nilai kredit atau tahap MQF melainkan telah disahkan melalui Quality Verification dan pengesahan itu masih sah.',
        ],
        'statement.catalogue.samc_qv' => [
            'en' => 'This SAMC is Quality-Verified. Credit {credit}, MQF level {mqf}, valid until {until}.',
            'ms' => 'SAMC ini telah disahkan melalui Quality Verification. Kredit {credit}, tahap MQF {mqf}, sah hingga {until}.',
        ],
        'statement.catalogue.mce' => [
            'en' => 'This is an Executive Micro-Credential (MCE). It earns no academic credit, no Academic Credit Record and no deposit to the Academic Bank of Credit.',
            'ms' => 'Ini ialah Micro-Credential Eksekutif (MCE). Ia tidak memberi kredit akademik, Academic Credit Record atau deposit ke Academic Bank of Credit.',
        ],
        'statement.separate_stages' => [
            'en' => 'Completing this micro-credential, receiving an Academic Credit Record, depositing credit to the Academic Bank of Credit and transferring credit are separate decisions.',
            'ms' => 'Menamatkan micro-credential ini, menerima Academic Credit Record, mendepositkan kredit ke Academic Bank of Credit dan memindahkan kredit adalah keputusan yang berasingan.',
        ],
    ];

    public function __construct(private Templates $templates) {}

    /**
     * Credit, MQF level and Quality Verification are shown only from a verified, valid record (FR-89, D-17).
     * A UMC carries the credit of its parent course once approved. A SAMC shows nothing until its Quality Verification is valid to the cohort end date.
     */
    public function claims(object $o, ?object $course): array
    {
        if ($o->category === 'umc' && $course) {
            return ['credit' => $course->credit, 'mqf' => null, 'qv' => null, 'qv_until' => null];
        }
        if ($o->category === 'samc' && $o->qv_status === 'verified' && $o->qv_valid_until && substr((string) $o->qv_valid_until, 0, 10) >= substr((string) $o->end_date, 0, 10)) {
            return ['credit' => $o->proposed_credit !== null ? rtrim(rtrim((string) $o->proposed_credit, '0'), '.') : null, 'mqf' => $o->proposed_mqf_level, 'qv' => true, 'qv_until' => substr((string) $o->qv_valid_until, 0, 10)];
        }

        return ['credit' => null, 'mqf' => null, 'qv' => null, 'qv_until' => null, 'renewal' => $o->category === 'samc' && $o->qv_status === 'verified'];
    }

    public function statement(object $o, ?object $course, array $c, string $lang): string
    {
        $vars = ['parent' => $course ? $course->code.' '.$course->name : '', 'credit' => $c['credit'] ?? '', 'mqf' => $c['mqf'] ?? '', 'until' => $c['qv_until'] ?? ''];
        $key = $this->key($o, $c);
        try {
            return $this->templates->render($key, $lang, $vars)[1];
        } catch (\InvalidArgumentException) {
            return $this->renderDefault($key, $lang, $vars);
        }
    }

    public function key(object $o, array $c): string
    {
        return match (true) { $o->category === 'umc' => 'statement.catalogue.umc', $o->category === 'mce' => 'statement.catalogue.mce', ($c['qv'] ?? false) => 'statement.catalogue.samc_qv', default => 'statement.catalogue.samc' };
    }

    /** The wording shown at registration, in both languages, with the template versions it came from. */
    public function acknowledged(int $offeringId): array
    {
        $o = DB::table('offerings as off')->join('mc_versions as v', 'v.id', '=', 'off.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('off.id', $offeringId)
            ->first(['off.*', 'v.qv_status', 'v.qv_valid_until', 'v.proposed_credit', 'v.proposed_mqf_level', 'v.parent_course_id', 'm.category']);
        $course = $o->parent_course_id ? DB::table('parent_courses')->find($o->parent_course_id) : null;
        $c = $this->claims($o, $course);
        $keys = [$this->key($o, $c)] + ($o->category === 'mce' ? [] : [1 => 'statement.separate_stages']);
        $text = [];
        $versions = [];
        foreach (['en', 'ms'] as $lang) {
            foreach ($keys as $k) {
                $vars = ['parent' => $course ? $course->code.' '.$course->name : '', 'credit' => $c['credit'] ?? '', 'mqf' => $c['mqf'] ?? '', 'until' => $c['qv_until'] ?? ''];
                try {
                    $text[$lang][] = $this->templates->render($k, $lang, $vars)[1];
                    $versions[$k][$lang] = $this->templates->current($k, $lang)->version;
                } catch (\InvalidArgumentException) {
                    $text[$lang][] = $this->renderDefault($k, $lang, $vars);
                    $versions[$k][$lang] = 1;
                }
            }
        }

        return ['en' => implode("\n\n", $text['en']), 'ms' => implode("\n\n", $text['ms']), 'versions' => $versions];
    }

    private function renderDefault(string $key, string $lang, array $vars): string
    {
        $template = self::DEFAULTS[$key][$lang] ?? (self::DEFAULTS[$key]['en'] ?? $key);

        return preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($vars[$m[1]] ?? $m[0]), $template);
    }
}
