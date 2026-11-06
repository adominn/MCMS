<?php

declare(strict_types=1);

namespace App\Modules\Credentials;

use App\Modules\Core\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// PUB-07 verify a credential, PUB-08 verification result: public, no login (FR-153 to FR-157).
final class VerifyController
{
    public function __construct(private Settings $settings) {}

    public function form()
    {
        return view('public.verify', ['lang' => session('lang', 'en'), 't' => fn ($k) => config("offerings.labels.$k.".session('lang', 'en')) ?? $k]);
    }

    public function show(Request $request, string $code)
    {
        $r = $this->lookup($code);
        $this->log($r['credential']->id ?? null, $r['outcome'], $request->wantsJson() ? 'api' : 'page', $request);
        abort_if($r['outcome'] === 'not_found' && $request->wantsJson(), 404, 'Credential not found.');
        if ($request->wantsJson()) {
            return response()->json($r['public']);
        }

        return response()->view('public.verify-result', [
            'lang' => session('lang', 'en'),
            't' => fn ($k) => config("offerings.labels.$k.".session('lang', 'en')) ?? $k,
            'r' => $r,
            'code' => $code,
        ], $r['outcome'] === 'not_found' ? 404 : 200);
    }

    // Public download endpoint for independent verifiers & employers
    public function download(Request $request, string $code, string $as)
    {
        $r = $this->lookup($code);
        abort_if($r['outcome'] === 'not_found' || empty($r['credential']), 404, 'Credential not found.');
        abort_unless(in_array($as, ['json', 'jwt'], true), 404);
        $c = $r['credential'];

        if ($as === 'jwt') {
            return response($c->jwt ?? '', 200, [
                'Content-Type' => 'application/jwt',
                'Content-Disposition' => 'attachment; filename="'.$c->number.'.jwt"'
            ]);
        }

        $vc = null;
        try {
            if (!empty($c->jwt)) {
                $verified = Jwt::verify($c->jwt);
                if (!empty($verified['ok']) && isset($verified['payload']['vc'])) {
                    $vc = $verified['payload']['vc'] + ['proof_jwt' => $c->jwt];
                }
            }
        } catch (\Throwable) {
            $vc = null;
        }

        if (!$vc) {
            $x = json_decode($c->content, true) ?: [];
            $vc = [
                '@context' => [
                    'https://www.w3.org/ns/credentials/v2',
                    'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json',
                ],
                'id' => CredentialUrl::publicVerifyUrl($c->public_code),
                'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
                'name' => $x['name']['en'] ?? 'Micro-Credential',
                'issuer' => [
                    'id' => CredentialUrl::publicBaseUrl(),
                    'type' => ['Profile'],
                    'name' => $x['issuer'] ?? 'Universiti Teknikal Malaysia Melaka',
                ],
                'validFrom' => $c->issued_at ? \Carbon\Carbon::parse($c->issued_at)->toIso8601String() : now()->toIso8601String(),
                'credentialSubject' => [
                    'type' => ['AchievementSubject'],
                    'identifier' => [
                        [
                            'type' => 'IdentityObject',
                            'identityType' => 'name',
                            'hashed' => false,
                            'identityHash' => $x['holder'] ?? 'Participant',
                        ],
                    ],
                    'achievement' => [
                        'id' => CredentialUrl::publicVerifyUrl($c->public_code).'#achievement',
                        'type' => ['Achievement'],
                        'achievementType' => 'Certificate',
                        'name' => $x['name']['en'] ?? '',
                        'description' => $x['statement']['en'] ?? '',
                        'criteria' => ['narrative' => $x['assessment_basis'] ?? ''],
                        'humanCode' => $c->number,
                        'tag' => $x['outcomes']['en'] ?? [],
                        'creditsAvailable' => (float) ($x['claims']['credit'] ?? 0),
                    ],
                ],
                'proof_jwt' => $c->jwt,
            ];
        }

        $body = json_encode($vc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($body, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$c->number.'.json"'
        ]);
    }

    // FR-155: a verifier checks the signature of a credential file against the issuer key.
    public function file(Request $request)
    {
        $request->validate(['file' => 'required|file|max:256']);
        $raw = (string) file_get_contents($request->file('file')->getRealPath());
        $jwtString = $raw;
        $decodedJson = json_decode($raw, true);
        if (is_array($decodedJson)) {
            if (!empty($decodedJson['proof_jwt'])) {
                $jwtString = (string) $decodedJson['proof_jwt'];
            } elseif (!empty($decodedJson['jwt'])) {
                $jwtString = (string) $decodedJson['jwt'];
            }
        }
        $v = Jwt::verify($jwtString);
        $code = null;
        if ($v['ok']) {
            $jti = (string) ($v['payload']['jti'] ?? '');
            if (preg_match('#/public/verify/([A-Za-z0-9\-_]+)$#', $jti, $m)) {
                $code = $m[1];
            }
        }
        $cred = $code ? DB::table('credentials')->where('public_code', $code)->orWhere('number', $code)->first() : null;
        $this->log($cred->id ?? null, $v['ok'] ? 'signature_ok' : 'signature_bad', 'file', $request);

        return view('public.verify', [
            'lang' => session('lang', 'en'),
            't' => fn ($k) => config("offerings.labels.$k.".session('lang', 'en')) ?? $k,
            'file' => $v + [
                'code' => $code,
                'known' => (bool) $cred,
                'current' => $code ? $this->lookup($code) : null,
                'is_json' => is_array($decodedJson),
            ],
        ]);
    }

    public function jwks()
    {
        return response()->json(KeyStore::jwks());
    }

    // ---- helpers

    /** Lookup a credential or digital badge by code or reference. */
    public function lookup(string $code): array
    {
        $clean = trim($code);
        $raw = strtoupper(str_replace([' ', '-'], '', $clean));
        $formatted = (strlen($raw) === 10) ? substr($raw, 0, 5).'-'.substr($raw, 5) : $clean;

        $none = [
            'outcome' => 'not_found',
            'credential' => null,
            'badge' => null,
            'crypto_status' => null,
            'public' => ['status' => 'not_found'],
            'code' => $code,
            'reason' => 'not_found',
        ];

        // 1. Try Credentials table (by public_code or number)
        $c = DB::table('credentials as c')
            ->leftJoin('enrolments as e', 'e.id', '=', 'c.enrolment_id')
            ->leftJoin('mc_versions as v', 'v.id', '=', 'e.mc_version_id')
            ->leftJoin('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->where(function ($q) use ($clean, $formatted, $raw) {
                $q->where('c.public_code', $clean)
                  ->orWhere('c.public_code', $formatted)
                  ->orWhere('c.public_code', strtoupper($clean))
                  ->orWhere('c.number', $clean)
                  ->orWhere('c.number', strtoupper($clean));
                if (strlen($raw) === 10) {
                    $q->orWhereRaw("REPLACE(c.public_code, '-', '') = ?", [$raw]);
                }
            })
            ->select([
                'c.*',
                'v.name_en as course_name_en',
                'v.name_ms as course_name_ms',
                'v.proposed_credit',
                'v.proposed_mqf_level',
                'v.qv_status',
                'v.qv_valid_until',
                'm.category as mc_category',
                'p.name as ptj_name',
            ])
            ->first();

        if ($c) {
            $x = json_decode($c->content, true) ?: [];

            // Cryptographic signature verification
            $cryptoVerify = !empty($c->jwt) ? Jwt::verify($c->jwt) : ['ok' => false, 'error' => 'No cryptographic signature attached'];
            $cryptoOk = !empty($cryptoVerify['ok']);

            // Status determination
            $isRevoked = $c->status === 'revoked';
            $isReplaced = $c->status === 'replaced';
            $isCorrected = $c->status === 'corrected';
            $qvUntil = $x['claims']['qv_until'] ?? $c->qv_valid_until;
            $isExpired = $qvUntil && \Carbon\Carbon::parse($qvUntil)->isPast() && $c->status === 'valid';

            $outcome = 'valid';
            if ($isRevoked) {
                $outcome = 'revoked';
            } elseif ($isReplaced) {
                $outcome = 'replaced';
            } elseif ($isCorrected) {
                $outcome = 'corrected';
            } elseif ($isExpired) {
                $outcome = 'expired';
            } elseif (!$cryptoOk) {
                $outcome = 'invalid_signature';
            }

            // Respect FR-154 setting for public fields
            $fields = array_map('strtolower', (array) $this->settings->get('verification.public_fields', ['Name', 'Credential', 'Issuer', 'Date', 'Status']));
            $show = fn (string $f) => in_array($f, $fields, true);

            $public = ['status' => $c->status]
                + ($show('name') ? ['recipient' => $x['holder'] ?? null] : [])
                + ($show('credential') ? ['credential' => $x['name'] ?? null] : [])
                + ($show('issuer') ? ['issuer' => $x['issuer'] ?? 'Universiti Teknikal Malaysia Melaka'] : [])
                + ($show('date') ? ['issued' => substr((string) ($x['issued'] ?? $c->issued_at ?? now()), 0, 10)] : [])
                + ($isCorrected ? ['corrected_on' => substr((string) $c->status_changed_at, 0, 10)] : [])
                + ($isRevoked ? ['revoked_on' => substr((string) $c->status_changed_at, 0, 10)] : [])
                + ($isReplaced ? ['replaced_by' => DB::table('credentials')->where('id', $c->replaced_by_id)->value('public_code')] : []);

            if ($isRevoked) {
                $public = ['status' => 'revoked', 'revoked_on' => substr((string) $c->status_changed_at, 0, 10)];
            }

            $details = [
                'credential_number' => $c->number,
                'public_code' => $c->public_code,
                'category' => $x['category'] ?? $c->mc_category ?? 'UMC',
                'credits' => $x['claims']['credit'] ?? $c->proposed_credit ?? 3,
                'mqf_level' => $x['claims']['mqf'] ?? $c->proposed_mqf_level ?? null,
                'slt_hours' => $x['slt_hours'] ?? null,
                'course' => ['en' => $c->course_name_en, 'ms' => $c->course_name_ms],
                'faculty' => $c->ptj_name,
                'outcomes' => $x['outcomes'] ?? null,
                'signatory' => $x['signatory'] ?? null,
                'crypto_verified' => $cryptoOk,
                'signature_algorithm' => 'Ed25519 (EdDSA)',
                'jwks_url' => url('/.well-known/jwks.json'),
            ];

            if ($isRevoked) {
                $details['revocation_reason'] = $c->status_reason ?: 'Revoked by faculty academic authority.';
            }

            // Load module achievements / badges for this credential
            $moduleBadges = DB::table('module_achievements as a')
                ->join('digital_badges as b', 'b.module_achievement_id', '=', 'a.id')
                ->join('mc_modules as m', 'm.id', '=', 'a.mc_module_id')
                ->where('a.enrolment_id', $c->enrolment_id)
                ->select([
                    'm.seq', 'm.name_en', 'm.name_ms', 'm.slt_hours',
                    'b.code as badge_code', 'b.external_id', 'b.external_url', 'a.achieved_at'
                ])
                ->orderBy('m.seq')
                ->get();

            return [
                'outcome' => $outcome,
                'type' => 'credential',
                'credential' => $c,
                'badge' => null,
                'module_badges' => $moduleBadges,
                'crypto_status' => $cryptoOk ? 'verified' : 'invalid',
                'crypto_error' => $cryptoVerify['error'] ?? null,
                'x' => $x,
                'public' => $public,
                'details' => $details,
                'show' => $show,
                'code' => $c->public_code,
                'newer' => $isReplaced ? DB::table('credentials')->find($c->replaced_by_id) : null,
            ];
        }

        // 2. Try Module Digital Badge (e.g. BADGE-CND-M1-01, B-2026-0001, or external UCreds ID)
        $badge = DB::table('digital_badges as b')
            ->join('module_achievements as a', 'a.id', '=', 'b.module_achievement_id')
            ->join('enrolments as e', 'e.id', '=', 'a.enrolment_id')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->join('mc_modules as m', 'm.id', '=', 'a.mc_module_id')
            ->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')
            ->leftJoin('micro_credentials as mc', 'mc.id', '=', 'v.micro_credential_id')
            ->leftJoin('ptjs as p', 'p.id', '=', 'mc.ptj_id')
            ->where('b.code', $clean)
            ->orWhere('b.code', strtoupper($clean))
            ->orWhere('b.external_id', $clean)
            ->select([
                'b.id', 'b.code', 'b.external_id', 'b.external_url', 'b.issued_at',
                'a.achieved_at', 'u.name as recipient_name', 'm.seq',
                'm.name_en as module_name_en', 'm.name_ms as module_name_ms', 'm.slt_hours',
                'v.name_en as course_name_en', 'v.name_ms as course_name_ms',
                'mc.category as mc_category', 'p.name as ptj_name',
            ])
            ->first();

        if ($badge) {
            $issuedDate = substr((string) ($badge->issued_at ?? $badge->achieved_at), 0, 10);

            return [
                'outcome' => 'valid',
                'type' => 'badge',
                'credential' => null,
                'badge' => $badge,
                'crypto_status' => 'verified',
                'public' => [
                    'status' => 'valid',
                    'type' => 'digital_badge',
                    'recipient' => $badge->recipient_name,
                    'credential' => "Module {$badge->seq}: {$badge->module_name_en}",
                    'badge_code' => $badge->code,
                    'module_seq' => $badge->seq,
                    'module_name_en' => $badge->module_name_en,
                    'module_name_ms' => $badge->module_name_ms,
                    'course_name_en' => $badge->course_name_en,
                    'course_name_ms' => $badge->course_name_ms,
                    'issuer' => 'Universiti Teknikal Malaysia Melaka',
                    'faculty' => $badge->ptj_name,
                    'issued' => $issuedDate,
                    'external_id' => $badge->external_id,
                    'external_url' => $badge->external_url,
                    'slt_hours' => $badge->slt_hours,
                    'standard' => 'Open Badges 3.0 (1EdTech) / W3C Verifiable Credentials',
                ],
                'details' => [],
                'show' => fn (string $f) => true,
                'code' => $badge->code,
            ];
        }

        return $none;
    }

    private function log(?int $credentialId, string $outcome, string $via, Request $request): void
    {
        try {
            DB::table('verification_log')->insert([
                'credential_id' => $credentialId,
                'outcome' => $outcome,
                'via' => $via,
                'network_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Do not fail verification on log error
        }
    }
}
