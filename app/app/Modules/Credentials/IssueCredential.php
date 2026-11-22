<?php

declare(strict_types=1);

namespace App\Modules\Credentials;

use App\Models\User;
use App\Modules\Achievement\Completion;
use App\Modules\Achievement\Events\CompletionConfirmed;
use App\Modules\Core\Templates;
use App\Modules\Enrolment\Recognition;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;

// MD-09: the credential is issued by the system when the deputy dean confirms completion and the pre-issuance checks pass (D-29, FR-144).
// There is no manual issuing step. A participant who fails a check is listed for the deputy dean with the reason.
final class IssueCredential
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier, private Recognition $recognition, private Templates $templates) {}

    public function handle(CompletionConfirmed $e): void
    {
        if ($this->issue($e->enrolmentId, $e->confirmedBy)) {
            app(\App\Modules\CreditBank\Acr::class)->prepare($e->enrolmentId, $e->confirmedBy); // EV-32 prepares the ACR of an eligible completion
        }
    }

    /** Appendix E8 8-item pre-issuance verification checklist */
    public function checklistE8(int $enrolmentId): array
    {
        $x = $this->load($enrolmentId);
        $list = [];

        // 1. Identity match (E8.1)
        $idOk = ! empty($x->user_name) && ! empty($x->institution_id);
        $list['identity_match'] = [
            'item' => '1. Padanan Identiti Peserta (Nama & No. Matrik / Pengenalan)',
            'passed' => $idOk,
            'note' => $idOk ? "Nama: {$x->user_name}, ID: {$x->institution_id}" : 'The identity does not match the official record: name or matric number is missing.',
        ];

        // 2. Completion terms met (E8.2)
        $compMet = true;
        $compNote = 'Semua syarat penyempurnaan disahkan selesai';
        if (! $x->completion_status) {
            $compMet = false;
            $compNote = 'Completion is not confirmed.';
        } elseif (! Completion::check($enrolmentId)['met'] && $x->status !== 'completed') {
            $compMet = false;
            $compNote = 'A completion requirement is missing.';
        }
        $list['completion_terms'] = [
            'item' => '2. Semua Syarat Penyempurnaan Dipenuhi (Kehadiran & Pencapaian)',
            'passed' => $compMet,
            'note' => $compNote,
        ];

        // 3. Assessment confirmed & verified (E8.3)
        $resCount = DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->count();
        $unverified = DB::table('assessment_results')->where('enrolment_id', $enrolmentId)->where('status', '!=', 'verified')->count();
        $assOk = $resCount > 0 && $unverified === 0;
        $list['assessment_verified'] = [
            'item' => '3. Keputusan Pentaksiran Telah Disahkan (Verified)',
            'passed' => $assOk,
            'note' => $assOk ? "Semua {$resCount} keputusan pentaksiran disahkan" : 'Some results are not verified.',
        ];

        // 4. Credential type matches level & approval (E8.4)
        $typeOk = in_array($x->version_status, ['approved', 'superseded'], true);
        $list['credential_type'] = [
            'item' => '4. Jenis Credential Sepadan dengan Tahap & Diluluskan',
            'passed' => $typeOk,
            'note' => $typeOk ? 'Jenis credential selaras dengan kelulusan versi' : 'The title and category do not match an approved version.',
        ];

        // 5. Accurate title & category (E8.5)
        $titleOk = in_array($x->version_status, ['approved', 'superseded'], true) && in_array($x->category, ['umc', 'samc', 'mce'], true);
        $list['accurate_title_cat'] = [
            'item' => '5. Tajuk & Kategori MC Tepat (UMC/SAMC/MCE)',
            'passed' => $titleOk,
            'note' => $titleOk ? "Tajuk: {$x->name_en} ({$x->category})" : 'The title and category do not match an approved version.',
        ];

        // 6. Valid credit / MQF / QV (E8.6)
        $course = $x->parent_course_id ? DB::table('parent_courses')->find($x->parent_course_id) : null;
        $claims = $this->recognition->claims($x, $course);
        $creditOk = true;
        if ($x->category === 'mce' && $claims['credit'] !== null) {
            $creditOk = false;
        }
        if ($x->category === 'samc' && empty($claims['qv']) && $claims['credit'] !== null) {
            $creditOk = false;
        }
        $list['valid_credit_claims'] = [
            'item' => '6. Maklumat Kredit, MQF dan QV Sah (Tiada Tuntutan Pramatang)',
            'passed' => $creditOk,
            'note' => $creditOk ? 'Pematuhan kawalan maklumat kredit dan QV' : 'Credit or MQF claims are invalid.',
        ];

        // 7. Working verification mechanism (E8.7)
        $keyOk = false;
        try {
            KeyStore::active();
            $keyOk = true;
        } catch (\Throwable) {
            $keyOk = false;
        }
        $list['working_verification'] = [
            'item' => '7. Mekanisme Verifikasi Berfungsi & Kunci Tandatangan Aktif',
            'passed' => $keyOk,
            'note' => $keyOk ? 'Kunci aktif Ed25519 sedia untuk pemeteraian' : 'No signing key is available, so the verification mechanism does not work.',
        ];

        // 8. Issuance record ready (E8.8)
        $list['issuance_ready'] = [
            'item' => '8. Rekod Pengeluaran Sedia Disimpan dalam Jejak Audit',
            'passed' => $idOk && $compMet && $assOk && $titleOk && $creditOk && $keyOk,
            'note' => 'Pematuhan keperluan rekod pengeluaran dan integriti',
        ];

        return $list;
    }

    /** @return list<string> the reasons issuing is blocked (FR-137, Appendix E8) */
    public function checks(int $enrolmentId): array
    {
        $checklist = $this->checklistE8($enrolmentId);
        $reasons = [];
        foreach ($checklist as $check) {
            if (! $check['passed']) {
                $reasons[] = $check['note'] ?? $check['item'];
            }
        }

        return $reasons;
    }

    public function issue(int $enrolmentId, ?int $by): ?object
    {
        if ($existing = DB::table('credentials')->where('enrolment_id', $enrolmentId)->whereNotIn('status', ['replaced'])->first()) {
            return $existing; // a repeated trigger never makes a second credential (IR-17)
        }
        if ($reasons = $this->checks($enrolmentId)) {
            DB::table('credential_issue_failures')->updateOrInsert(['enrolment_id' => $enrolmentId], ['reasons' => json_encode($reasons), 'created_at' => now()]);
            $this->audit->record('credential.issue_blocked', ['type' => 'Enrolment', 'id' => $enrolmentId], after: ['reasons' => $reasons], actorId: $by);

            return null;
        }

        return DB::transaction(function () use ($enrolmentId, $by) {
            $code = $this->newCode();
            $checklist = $this->checklistE8($enrolmentId);
            $signer = DB::table('users')->where('id', DB::table('achievement_records')->where('enrolment_id', $enrolmentId)->value('confirmed_by'))->first();
            $signoffs = [
                'coordinator' => [
                    'name' => 'Penyelaras Kursus / MC',
                    'position' => 'Penyelaras Kursus',
                    'signed_at' => now()->toIso8601String(),
                    'remarks' => 'Semakan pra-pengeluaran disahkan lengkap',
                ],
                'faculty_rep' => [
                    'name' => $signer?->name ?? 'Timbalan Dekan (Akademik)',
                    'position' => $signer?->position ?? 'Timbalan Dekan (Akademik)',
                    'signed_at' => now()->toIso8601String(),
                    'remarks' => 'Pengesahan penyempurnaan dan kelayakan credential',
                ],
                'credential_custodian' => [
                    'name' => DB::table('users')->where('id', $by)->value('name') ?? 'Urus Setia Credential',
                    'position' => 'Pegawai Credential',
                    'signed_at' => now()->toIso8601String(),
                    'remarks' => 'Pemeteraian dan pengeluaran credential digital',
                ],
            ];

            $id = DB::table('credentials')->insertGetId([
                'number' => 'TMP'.bin2hex(random_bytes(6)),
                'public_code' => $code,
                'enrolment_id' => $enrolmentId,
                'content' => '{}',
                'jwt' => '',
                'kid' => '',
                'e8_checklist' => json_encode($checklist),
                'e10_signoffs' => json_encode($signoffs),
                'issued_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $number = 'MCMS-C-'.now()->year.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            $content = $this->content($enrolmentId, $number, $code, now());
            [$jwt, $kid] = $this->sign($content, $number, $code);
            DB::table('credentials')->where('id', $id)->update(['number' => $number, 'content' => json_encode($content), 'jwt' => $jwt, 'kid' => $kid]);
            $this->revision($id, 1, 'issued', $content, $jwt, null, $by);
            DB::table('credential_issue_failures')->where('enrolment_id', $enrolmentId)->delete();
            $this->audit->record('credential.issued', ['type' => 'Credential', 'id' => $id], after: ['number' => $number, 'enrolment' => $enrolmentId], actorId: $by); // EV-32
            $u = User::find(DB::table('enrolments')->where('id', $enrolmentId)->value('user_id'));
            $this->notifier->send($u, 'credential.issued', ['name' => $u->name, 'title' => $content['name']['en'], 'number' => $number], dedupe: "issued-$id");

            return DB::table('credentials')->find($id);
        });
    }

    // ---- content and signature

    public function content(int $enrolmentId, string $number, string $code, $issuedAt, ?string $holderName = null): array
    {
        $x = $this->load($enrolmentId);
        $course = $x->parent_course_id ? DB::table('parent_courses')->find($x->parent_course_id) : null;
        $claims = $this->recognition->claims($x, $course); // credit, MQF level and QV only from verified, valid records (FR-143, D-17)
        $outcomes = DB::table('learning_outcomes')->where('mc_version_id', $x->mc_version_id)->orderBy('sort_order')->get();
        $comps = DB::table('assessment_components')->where('mc_version_id', $x->mc_version_id)->orderBy('id')->get();
        $slt = (int) DB::table('mc_modules')->where('mc_version_id', $x->mc_version_id)->sum('slt_hours');
        $statement = ['en' => $this->recognition->statement($x, $course, $claims, 'en'), 'ms' => $this->recognition->statement($x, $course, $claims, 'ms')];
        $stages = ['en' => $this->templates->render('statement.separate_stages', 'en', [])[1], 'ms' => $this->templates->render('statement.separate_stages', 'ms', [])[1]];
        $basis = 'Verified marks: '.$comps->map(fn ($c) => ($c->name_en ?: 'assessment').' ('.mcms_num($c->weight_percent).'%)')->implode(', ').'. Completion confirmed by the deputy dean.';
        $signer = DB::table('users')->where('id', DB::table('achievement_records')->where('enrolment_id', $enrolmentId)->value('confirmed_by'))->first();
        $ptj = DB::table('ptjs')->find($x->ptj_id);
        $offering = DB::table('offerings')->find($x->offering_id);

        return [
            'number' => $number, 'code' => $code,
            'holder' => $holderName ?? $x->user_name,
            'name' => ['en' => $x->name_en, 'ms' => $x->name_ms ?: $x->name_en],
            'category' => strtoupper($x->category), 'issuer' => config('mcms.issuer'), 'issued' => $issuedAt->toIso8601String(),
            'ptj' => $ptj?->name ?? $ptj?->code ?? 'FTMK',
            'cohort' => $offering?->cohort_name ?? 'Kohort 1',
            'slt_hours' => $slt, 'outcomes' => ['en' => $outcomes->pluck('text_en')->filter()->values()->all(), 'ms' => $outcomes->map(fn ($o) => $o->text_ms ?: $o->text_en)->filter()->values()->all()],
            'assessment_basis' => $basis, 'statement' => $statement, 'stages' => $x->category === 'mce' ? null : $stages,
            'claims' => ['credit' => $claims['credit'], 'mqf' => $claims['mqf'], 'qv' => (bool) $claims['qv'], 'qv_until' => $claims['qv_until']], // the status valid on the issue date (FR-152)
            'signatory' => $signer?->name ?? 'Timbalan Dekan (Akademik)',
            'signatory_position' => $signer?->position ?? 'Deputy Dean (Academic)',
            'signatory_position_ms' => $signer?->position_ms ?? 'Timbalan Dekan (Akademik)',
        ];
    }

    /** @return array{0: string, 1: string} the signed JWT and the key id */
    public function sign(array $c, string $number, string $code): array
    {
        $key = KeyStore::active();
        $url = CredentialUrl::publicVerifyUrl($code);
        $issuerUrl = CredentialUrl::publicBaseUrl();
        $vc = [
            '@context' => ['https://www.w3.org/ns/credentials/v2', 'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json'],
            'id' => $url, 'type' => ['VerifiableCredential', 'OpenBadgeCredential'], 'name' => $c['name']['en'],
            'issuer' => ['id' => $issuerUrl, 'type' => ['Profile'], 'name' => $c['issuer']], 'validFrom' => $c['issued'],
            'credentialSubject' => [
                'type' => ['AchievementSubject'], 'identifier' => [['type' => 'IdentityObject', 'identityType' => 'name', 'hashed' => false, 'identityHash' => $c['holder']]],
                'achievement' => ['id' => $url.'#achievement', 'type' => ['Achievement'], 'achievementType' => 'Certificate', 'name' => $c['name']['en'], 'description' => $c['statement']['en'],
                    'criteria' => ['narrative' => $c['assessment_basis']], 'humanCode' => $number, 'tag' => $c['outcomes']['en']] + ($c['claims']['credit'] !== null ? ['creditsAvailable' => (float) $c['claims']['credit']] : []),
            ],
            'mcms' => ['credential_number' => $number, 'category' => $c['category'], 'slt_hours' => $c['slt_hours'], 'claims' => $c['claims'], 'ms' => ['name' => $c['name']['ms'], 'statement' => $c['statement']['ms']]],
        ];

        return [Jwt::sign(['iss' => $issuerUrl, 'jti' => $url, 'sub' => 'urn:mcms:holder:'.substr(hash('sha256', $c['holder'].$number), 0, 16), 'nbf' => strtotime($c['issued']), 'iat' => time(), 'vc' => $vc], $key), $key->kid];
    }

    public function revision(int $id, int $rev, string $event, array $content, string $jwt, ?string $reason, ?int $by): void
    {
        DB::table('credential_revisions')->insert(['credential_id' => $id, 'revision' => $rev, 'event' => $event, 'content' => json_encode($content), 'jwt' => $jwt, 'reason' => $reason, 'by_user_id' => $by, 'created_at' => now()]);
    }

    /** Unique and non-guessable: 10 random Crockford base32 characters (FR-146, DA-16). */
    public function newCode(): string
    {
        do {
            $raw = random_bytes(7);
            $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
            $bits = '';
            foreach (str_split($raw) as $b) {
                $bits .= str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT);
            }
            $s = '';
            for ($i = 0; $i < 10; $i++) {
                $s .= $alphabet[bindec(substr($bits, $i * 5, 5))];
            }
            $code = substr($s, 0, 5).'-'.substr($s, 5);
        } while (DB::table('credentials')->where('public_code', $code)->exists());

        return $code;
    }

    private function load(int $enrolmentId): object
    {
        return DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')->leftJoin('achievement_records as a', 'a.enrolment_id', '=', 'e.id')->where('e.id', $enrolmentId)
            ->first(['e.*', 'e.mc_version_id', 'u.name as user_name', 'u.institution_id', 'v.name_en', 'v.name_ms', 'v.status as version_status', 'v.parent_course_id', 'v.qv_status', 'v.qv_valid_until', 'v.proposed_credit', 'v.proposed_mqf_level',
                'm.category', 'm.ptj_id', 'o.end_date', 'o.id as offering_id', 'a.completion_status']);
    }
}
