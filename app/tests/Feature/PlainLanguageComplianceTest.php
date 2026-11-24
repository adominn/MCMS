<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Enrolment\Recognition;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['mcms.stub_idp' => true]);
    $this->seed(DatabaseSeeder::class);
    $this->aina = User::where('name', 'Dr. Aina Zulkifli')->first();
    $this->hakim = User::where('name', 'Hakim Rahman')->first();
    $this->amir = User::where('name', 'Amir Hakimi')->first();
    $this->farid = User::where('name', 'Farid Hashim')->first();
    $this->nurul = User::where('name', 'Nurul Izzah')->first();
    $this->lim = User::create(['name' => 'Dr. Lim Wei Jie', 'email' => 'lim@utem.example.test']);
    $this->lim->roleAssignments()->create([
        'role' => 'lecturer',
        'scope_type' => 'ptj',
        'ptj_id' => DB::table('ptjs')->where('code', 'FTMK')->value('id'),
    ]);
});

function visiblePlainText(string $html): string
{
    $clean = preg_replace('/<!--.*?-->/s', ' ', $html) ?? $html;
    $clean = preg_replace('/<(style|script)\b[^>]*>.*?<\/\1>/is', ' ', $clean) ?? $clean;

    return preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($clean), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';
}

function assertPlainLanguageClean(string $html, string $context): void
{
    $text = visiblePlainText($html);

    $patterns = [
        '/\bAppendix\s+[A-H]\d*(?:\.\d+)?\b/i',
        '/\bLampiran\s+[A-H]\d*(?:\.\d+)?\b/i',
        '/Garis\s+Panduan\s+Micro-Credential/i',
        '/\b(?:Cl|Kl|Klausa)\.?\s+\d+\.\d+(?:\.\d+)?\b/i',
        '/\bm\/s\s+119\b/i',
        '/\bper\s+Guide\b/i',
        '/\bA[1-9]\.\s|A10\.\s/',
        '/\bB[1-8]\.\s/',
        '/\bC[1-9]\.\s|C10\.\s/',
        '/\bD[1-9]\.\s|D10\.\s|D[2-7]\.[1-9]\b/',
        '/\bG(?:4|5|8|9|11)[:.]\s/',
        '/\((?:F[1-9]|F10|F11|H4|H5|H8|H9|C3|C8|C9|E10)\)/',
        '/\bPrint\s+(?:Appendix|Lampiran)\b/i',
        '/\bSave\s+C3\s+Summary\b/',
        '/\bSave\s+Decision\s*\(C9\)/',
    ];

    foreach ($patterns as $regex) {
        expect($text)->not->toMatch($regex, "Found prohibited guide/regulatory reference matching {$regex} on {$context}");
    }
}

it('shows zero guide or appendix references across UMC, credit bank, transfer, pathway, and record views in EN and BM', function () {
    // Deposited UMC workflow populates offering, proposal version, CQI, credential, ACR, and ABC deposit
    $dep = deposited();
    $acr = DB::table('acrs')->where('id', $dep->acr_id)->first();
    $ar = DB::table('achievement_records')->where('id', $acr->achievement_record_id)->first();
    $enrolment = DB::table('enrolments')->where('id', $ar->enrolment_id)->first();
    $o = (int) $enrolment->offering_id;
    $v = (int) DB::table('offerings')->where('id', $o)->value('mc_version_id');
    $mc = (int) DB::table('mc_versions')->where('id', $v)->value('micro_credential_id');
    $credId = (int) DB::table('credentials')->where('enrolment_id', $enrolment->id)->value('id');

    cqiOpen($o)->assertSessionHasNoErrors();
    $cqiId = (int) DB::table('cqi_records')->where('offering_id', $o)->orderByDesc('id')->value('id');

    $progId = (int) DB::table('programmes')->where('code', 'BITS')->value('id');
    $courseId = (int) DB::table('parent_courses')->where('code', 'BITS 1123')->value('id');
    $this->actingAs($this->amir)->post('/transfer', [
        'programme_id' => $progId,
        'deposits' => [$dep->id],
        'course' => [$dep->id => $courseId],
        'credits' => [$dep->id => 3],
        'documents' => 'syllabus-ds.pdf',
    ])->assertSessionHasNoErrors();
    $trId = (int) DB::table('transfer_applications')->orderByDesc('id')->value('id');

    $this->actingAs($this->aina)->post('/pathways', [
        'programme_id' => $progId,
        'plos' => "PLO1\nPLO2",
        'bok' => 'Algorithms',
    ])->assertSessionHasNoErrors();
    $pwId = (int) DB::table('pathways')->max('id');
    DB::table('pathways')->where('id', $pwId)->update(['status' => 'published']);

    $sharedUrls = [
        "/offerings/{$o}" => 'offering detail',
        "/offerings/{$o}/readiness" => 'offering readiness print',
        "/proposals/{$v}" => 'proposal workspace',
        "/proposals/{$v}?tab=justification" => 'proposal justification tab',
        "/proposals/{$v}?tab=modules" => 'proposal modules tab',
        "/proposals/{$v}/print" => 'proposal print',
        "/micro-credentials/{$mc}" => 'mc hub',
        "/cqi/{$cqiId}" => 'cqi show',
        "/cqi/{$cqiId}/print" => 'cqi print',
        "/cohorts/{$o}/signoff" => 'completion signoff',
        "/completion/{$o}" => 'completion show',
        "/pathways/{$pwId}" => 'pathway show',
        "/public/pathways/{$pwId}/print" => 'pathway print',
        "/records/cohort/{$o}" => 'cohort records',
        '/records/discrepancies' => 'discrepancies',
    ];

    foreach (['en', 'ms'] as $locale) {
        foreach ($sharedUrls as $url => $label) {
            $res = $this->actingAs($this->aina)
                ->withSession(['active_role' => 'lecturer', 'ui_lang' => $locale, 'lang' => $locale])
                ->get($url);
            if ($res->status() === 403) {
                $res = $this->actingAs($this->hakim)
                    ->withSession(['active_role' => 'deputy_dean', 'ui_lang' => $locale, 'lang' => $locale])
                    ->get($url);
            }
            $res->assertOk();
            assertPlainLanguageClean($res->getContent(), "{$url} [{$locale}] ({$label})");
        }

        foreach (['/records/retention', "/transfers/{$trId}", "/transfers/{$trId}?print=1"] as $deanUrl) {
            $res = $this->actingAs($this->hakim)
                ->withSession(['active_role' => 'deputy_dean', 'ui_lang' => $locale, 'lang' => $locale])
                ->get($deanUrl);
            $res->assertOk();
            assertPlainLanguageClean($res->getContent(), "{$deanUrl} [{$locale}]");
        }

        $credRes = $this->actingAs($this->hakim)
            ->withSession(['active_role' => 'deputy_dean', 'ui_lang' => $locale, 'lang' => $locale])
            ->get("/credentials/{$credId}/sheet");
        $credRes->assertOk();
        assertPlainLanguageClean($credRes->getContent(), "/credentials/{$credId}/sheet [{$locale}]");

        $depositor = User::whereHas('roleAssignments', fn ($q) => $q->whereIn('role', ['abc_depositor', 'records_depositor']))->first() ?? $this->nurul;
        $rawDepRole = $depositor->roleAssignments()->whereIn('role', ['abc_depositor', 'records_depositor'])->first()?->role;
        $depRole = $rawDepRole instanceof \App\Modules\Identity\Role ? $rawDepRole->value : (string) $rawDepRole;
        foreach (["/deposits/{$acr->id}", "/deposits/{$acr->id}?print=1"] as $regUrl) {
            $res = $this->actingAs($depositor)
                ->withSession(['active_role' => $depRole, 'ui_lang' => $locale, 'lang' => $locale])
                ->get($regUrl);
            $res->assertOk();
            assertPlainLanguageClean($res->getContent(), "{$regUrl} [{$locale}]");
        }

        foreach ([
            '/my-credit',
            "/my-credit/{$acr->id}",
            "/my-credit/{$acr->id}?print=1",
            "/my-credentials/{$credId}/sheet",
            "/transfer/{$trId}",
            "/transfer/{$trId}?print=1",
            "/transfer/{$trId}/document/syllabus-ds.pdf",
        ] as $stUrl) {
            $res = $this->actingAs($this->amir)
                ->withSession(['active_role' => 'student', 'ui_lang' => $locale, 'lang' => $locale])
                ->get($stUrl);
            $res->assertOk();
            assertPlainLanguageClean($res->getContent(), "{$stUrl} [{$locale}]");
        }
    }
});

it('shows zero guide or appendix references across Quality Verification views and package printouts in EN and BM', function () {
    [$samcMc] = samcCompleted();
    qvReady($samcMc);

    foreach (['en', 'ms'] as $locale) {
        foreach ([
            "/quality/{$samcMc}" => 'qv show',
            "/quality/{$samcMc}/package" => 'qv package web',
            "/quality/{$samcMc}/package?print=1" => 'qv package print',
        ] as $url => $label) {
            $res = $this->actingAs($this->aina)
                ->withSession(['active_role' => 'lecturer', 'ui_lang' => $locale, 'lang' => $locale])
                ->get($url);
            $res->assertOk();
            assertPlainLanguageClean($res->getContent(), "{$url} [{$locale}] ({$label})");
        }
    }
});
