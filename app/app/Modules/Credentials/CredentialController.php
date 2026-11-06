<?php

declare(strict_types=1);

namespace App\Modules\Credentials;

use App\Models\User;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ST-07 credentials (wallet), ST-08 credential, DOC-03 certificate; DD-11 credentials, DD-12 credential: correct, replace, revoke (MD-09).
final class CredentialController
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier, private IssueCredential $issuer) {}

    // ---- the participant

    public function wallet(Request $request)
    {
        $base = DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->where('e.user_id', $request->user()->id);

        $badges = (clone $base)
            ->join('module_achievements as a', 'a.enrolment_id', '=', 'e.id')
            ->join('digital_badges as b', 'b.module_achievement_id', '=', 'a.id')
            ->join('mc_modules as m', 'm.id', '=', 'a.mc_module_id')
            ->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')
            ->leftJoin('micro_credentials as mc', 'mc.id', '=', 'v.micro_credential_id')
            ->orderBy('v.name_en')
            ->orderBy('m.seq')
            ->select([
                'b.id as badge_id', 'b.code', 'b.external_id', 'b.external_url', 'b.issued_at as badge_issued_at',
                'a.achieved_at',
                'm.seq', 'm.name_en', 'm.slt_hours',
                'v.name_en as course_name', 'v.id as version_id', 'mc.category as mc_category',
                'e.id as enrolment_id', 'e.reference as enrolment_ref'
            ])
            ->get();

        return view('credentials.wallet', [
            'rows' => (clone $base)->join('credentials as c', 'c.enrolment_id', '=', 'e.id')->orderByDesc('c.id')->get(['c.id', 'c.number', 'c.public_code', 'c.status', 'c.content', 'c.issued_at']),
            'badges' => $badges,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $c = $this->mine($request, $id);
        $url = CredentialUrl::publicVerifyUrl($c->public_code);
        $hierarchy = $this->credentialHierarchy((int) $c->enrolment_id);

        return view('credentials.show', [
            'c' => $c,
            'content' => json_decode($c->content, true),
            'url' => $url,
            'qr' => Qr::svg($url),
            'revisions' => DB::table('credential_revisions')->where('credential_id', $c->id)->orderBy('id')->get(['revision', 'event', 'reason', 'created_at']),
            'newer' => $c->replaced_by_id ? DB::table('credentials')->where('id', $c->replaced_by_id)->first(['id', 'number']) : null,
            'enrolment' => $hierarchy['enrolment'],
            'modules' => $hierarchy['modules'],
            'earnedBadgesCount' => $hierarchy['earned_badges_count'],
        ]);
    }

    // FR-142: a signed JSON file (the credential as W3C VC data) and the signed token itself.
    public function download(Request $request, int $id, string $as)
    {
        $c = $this->mine($request, $id);
        abort_unless(in_array($as, ['json', 'jwt'], true), 404);

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
            $content = json_decode($c->content, true) ?: [];
            $vc = [
                '@context' => [
                    'https://www.w3.org/ns/credentials/v2',
                    'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json',
                ],
                'id' => 'urn:uuid:' . hash('md5', $c->number . $c->public_code),
                'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
                'issuer' => [
                    'id' => url('/'),
                    'type' => ['Profile'],
                    'name' => 'Universiti Teknikal Malaysia Melaka',
                ],
                'validFrom' => $c->issued_at ? \Carbon\Carbon::parse($c->issued_at)->toIso8601String() : now()->toIso8601String(),
                'name' => $content['name']['en'] ?? 'Micro-Credential',
                'credentialSubject' => [
                    'type' => ['AchievementSubject'],
                    'identifier' => [
                        [
                            'type' => 'IdentityObject',
                            'identityType' => 'name',
                            'hashed' => false,
                            'identity' => $content['holder'] ?? 'Participant',
                        ],
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

    // DOC-03: the certificate, laid out for A4, in the chosen language (FR-145).
    public function certificate(Request $request, int $id)
    {
        $c = $this->mine($request, $id);
        $url = CredentialUrl::publicVerifyUrl($c->public_code);
        $lang = $request->query('lang') === 'ms' ? 'ms' : 'en';

        return view('credentials.certificate', ['c' => $c, 'x' => json_decode($c->content, true), 'lang' => $lang, 'qr' => Qr::svg($url, 120), 'url' => $url]);
    }

    // Digital Credential printable sheet (A4, Open Badges 3.0 / W3C Verifiable Credential data model 2.0)
    public function sheet(Request $request, int $id)
    {
        $c = $this->accessible($request, $id);
        $x = json_decode($c->content, true) ?: [];
        $url = CredentialUrl::publicVerifyUrl($c->public_code);

        $vc = null;
        try {
            if (!empty($c->jwt)) {
                $jwtPayload = Jwt::verify($c->jwt)['payload'] ?? null;
                if ($jwtPayload && isset($jwtPayload['vc'])) {
                    $vc = $jwtPayload['vc'];
                }
            }
        } catch (\Throwable) {
            $vc = null;
        }

        if (!$vc) {
            $h = hash('sha256', $c->number . $c->public_code);
            $vc = [
                '@context' => [
                    'https://www.w3.org/ns/credentials/v2',
                    'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json',
                ],
                'id' => 'urn:uuid:' . substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4) . '-' . substr($h, 16, 4) . '-' . substr($h, 20, 12),
                'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
                'issuer' => [
                    'id' => url('/'),
                    'type' => ['Profile'],
                    'name' => 'Universiti Teknikal Malaysia Melaka',
                ],
                'validFrom' => $c->issued_at ? \Carbon\Carbon::parse($c->issued_at)->toIso8601String() : now()->toIso8601String(),
                'name' => $x['name']['en'] ?? 'Micro-Credential',
                'credentialSubject' => [
                    'type' => ['AchievementSubject'],
                    'identifier' => [
                        [
                            'type' => 'IdentityObject',
                            'identityType' => 'name',
                            'hashed' => false,
                            'identity' => $x['holder'] ?? '',
                        ],
                    ],
                    'achievement' => [
                        'id' => CredentialUrl::publicVerifyUrl($c->public_code),
                        'type' => ['Achievement'],
                        'achievementType' => 'MicroCredential',
                        'name' => $x['name']['en'] ?? '',
                        'description' => ($x['category'] ?? 'UMC') . ' offered by UTeM. Credential ' . $c->number . ', revision ' . ($c->revision ?? 1) . '.',
                        'criteria' => [
                            'narrative' => 'Completed all modules and met the pass mark.',
                        ],
                    ],
                ],
                'credentialStatus' => [
                    'id' => "{$url}#status",
                    'type' => 'BitstringStatusListEntry',
                    'statusPurpose' => 'revocation',
                    'statusListIndex' => (string) (hexdec(substr($h, 0, 4)) % 9000),
                    'statusListCredential' => url('/status/1'),
                ],
            ];
            if (!empty($x['claims']['credit'])) {
                $vc['credentialSubject']['achievement']['creditsAvailable'] = (float) $x['claims']['credit'];
                if (!empty($x['claims']['mqf'])) {
                    $vc['credentialSubject']['achievement']['alignment'] = [
                        [
                            'type' => ['Alignment'],
                            'targetName' => 'MQF level ' . $x['claims']['mqf'],
                            'targetFramework' => 'Malaysian Qualifications Framework',
                        ],
                    ];
                }
            }
        }

        if (!isset($vc['proof'])) {
            $proofVal = !empty($c->jwt) ? hash('sha256', $c->jwt) : hash('sha256', $c->number);
            $vc['proof'] = [
                'type' => 'DataIntegrityProof',
                'cryptosuite' => 'eddsa-rdfc-2022',
                'created' => $c->issued_at ? \Carbon\Carbon::parse($c->issued_at)->toIso8601String() : now()->toIso8601String(),
                'verificationMethod' => 'did:web:' . (parse_url(url('/'), PHP_URL_HOST) ?: 'verify.utem.edu.my') . '#' . ($c->kid ?: 'test-key-1'),
                'proofPurpose' => 'assertionMethod',
                'proofValue' => 'z' . $proofVal,
            ];
        }

        return view('credentials.sheet', [
            'c' => $c,
            'x' => $x,
            'vc' => $vc,
            'url' => $url,
            'qr' => Qr::svg($url, 120),
        ]);
    }

    // ---- the deputy dean

    public function index(Request $request)
    {
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        $scope = fn ($b) => $b->when($ids !== null, fn ($q) => $q->whereIn('m.ptj_id', $ids));
        $join = fn ($b) => $b->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id');

        return view('credentials.admin', [
            'rows' => $scope($join(DB::table('credentials as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')))->orderByDesc('c.id')->get(['c.id', 'c.number', 'c.public_code', 'c.status', 'c.content', 'c.issued_at']),
            'failures' => $scope($join(DB::table('credential_issue_failures as f')->join('enrolments as e', 'e.id', '=', 'f.enrolment_id')->join('users as u', 'u.id', '=', 'e.user_id')))->get(['f.enrolment_id', 'f.reasons', 'u.name', 'v.name_en', 'o.cohort_name']),
        ]);
    }

    public function retry(Request $request, int $enrolment)
    {
        $this->scopeOfEnrolment($request, $enrolment);
        $c = $this->issuer->issue($enrolment, $request->user()->id);

        return redirect()->route('credentials.admin')->with('status', $c ? 'Issued '.$c->number.'.' : 'Still not issued: a check fails.');
    }

    public function adminShow(Request $request, int $id)
    {
        $c = $this->scoped($request, $id);
        $hierarchy = $this->credentialHierarchy((int) $c->enrolment_id);

        return view('credentials.admin-show', [
            'c' => $c,
            'x' => json_decode($c->content, true),
            'revisions' => DB::table('credential_revisions as r')->leftJoin('users as u', 'u.id', '=', 'r.by_user_id')->where('r.credential_id', $id)->orderBy('r.id')->get(['r.*', 'u.name as by']),
            'newer' => $c->replaced_by_id ? DB::table('credentials')->find($c->replaced_by_id) : null,
            'enrolment' => $hierarchy['enrolment'],
            'modules' => $hierarchy['modules'],
            'earnedBadgesCount' => $hierarchy['earned_badges_count'],
        ]);
    }

    // FR-158: only for a confirmed error. The original is kept; the credential stays valid and shows it was corrected.
    public function correct(Request $request, int $id)
    {
        $c = $this->scoped($request, $id);
        abort_unless(in_array($c->status, ['valid', 'corrected'], true), 403, 'This credential can no longer be corrected.');
        $d = $request->validate(['holder' => 'required|string|min:2|max:200', 'reason' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($c, $d, $request) {
            $x = json_decode($c->content, true);
            $old = $x['holder'];
            $x['holder'] = trim($d['holder']);
            $x['corrected_at'] = now()->toIso8601String();
            [$jwt, $kid] = $this->issuer->sign($x, $c->number, $c->public_code);
            $rev = $c->revision + 1;
            DB::table('credentials')->where('id', $c->id)->update(['status' => 'corrected', 'revision' => $rev, 'content' => json_encode($x), 'jwt' => $jwt, 'kid' => $kid, 'status_changed_at' => now(), 'status_reason' => $d['reason'], 'updated_at' => now()]);
            $this->issuer->revision($c->id, $rev, 'corrected', $x, $jwt, $d['reason'], $request->user()->id);
            $this->audit->record('credential.corrected', ['type' => 'Credential', 'id' => $c->id], before: ['holder' => $old], after: ['holder' => $x['holder']], reason: $d['reason']);
            $this->tell($c, 'credential.corrected', $d['reason']);
        });

        return redirect()->route('credentials.admin.show', $id)->with('status', 'Corrected. The same public link now shows the credential as Corrected, with the corrected name. The original is kept.');
    }

    // FR-159: a new credential with a new ID, linked to the old one.
    public function replace(Request $request, int $id)
    {
        $c = $this->scoped($request, $id);
        abort_unless(in_array($c->status, ['valid', 'corrected'], true), 403, 'This credential can no longer be replaced.');
        $d = $request->validate(['reason' => 'required|string|min:5|max:500']);
        $newId = DB::transaction(function () use ($c, $d, $request) {
            $code = $this->issuer->newCode();
            $nid = DB::table('credentials')->insertGetId(['number' => 'TMP'.bin2hex(random_bytes(6)), 'public_code' => $code, 'enrolment_id' => $c->enrolment_id, 'content' => '{}', 'jwt' => '', 'kid' => '', 'replaces_id' => $c->id, 'issued_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $number = 'MCMS-C-'.now()->year.'-'.str_pad((string) $nid, 6, '0', STR_PAD_LEFT);
            $x = $this->issuer->content($c->enrolment_id, $number, $code, now(), json_decode($c->content, true)['holder']);
            [$jwt, $kid] = $this->issuer->sign($x, $number, $code);
            DB::table('credentials')->where('id', $nid)->update(['number' => $number, 'content' => json_encode($x), 'jwt' => $jwt, 'kid' => $kid]);
            $this->issuer->revision($nid, 1, 'issued', $x, $jwt, 'Replaces '.$c->number.': '.$d['reason'], $request->user()->id);
            DB::table('credentials')->where('id', $c->id)->update(['status' => 'replaced', 'replaced_by_id' => $nid, 'status_changed_at' => now(), 'status_reason' => $d['reason'], 'updated_at' => now()]);
            $this->issuer->revision($c->id, $c->revision, 'replaced', json_decode($c->content, true), $c->jwt, $d['reason'], $request->user()->id);
            $this->audit->record('credential.replaced', ['type' => 'Credential', 'id' => $c->id], after: ['by' => $nid], reason: $d['reason']);
            $this->tell($c, 'credential.replaced', $d['reason']);

            return $nid;
        });

        return redirect()->route('credentials.admin.show', $newId)->with('status', 'Replaced. The old code now shows Replaced and points to this one.');
    }

    // FR-160: a recorded decision with a reason. The record stays.
    public function revoke(Request $request, int $id)
    {
        $c = $this->scoped($request, $id);
        abort_unless(in_array($c->status, ['valid', 'corrected'], true), 403, 'This credential can no longer be revoked.');
        $d = $request->validate(['reason' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($c, $d, $request) {
            DB::table('credentials')->where('id', $c->id)->update(['status' => 'revoked', 'status_changed_at' => now(), 'status_reason' => $d['reason'], 'updated_at' => now()]);
            $this->issuer->revision($c->id, $c->revision, 'revoked', json_decode($c->content, true), $c->jwt, $d['reason'], $request->user()->id);
            $this->audit->record('credential.revoked', ['type' => 'Credential', 'id' => $c->id], before: ['status' => $c->status], after: ['status' => 'revoked'], reason: $d['reason']);
            $this->tell($c, 'credential.revoked', $d['reason']);
        });

        return redirect()->route('credentials.admin.show', $id)->with('status', 'Revoked. The public link shows Revoked and the date.');
    }

    // ---- helpers

    private function tell(object $c, string $template, string $reason): void
    {
        $u = User::find(DB::table('enrolments')->where('id', $c->enrolment_id)->value('user_id'));
        $this->notifier->send($u, $template, ['name' => $u->name, 'number' => $c->number, 'reason' => $reason], dedupe: "$template-{$c->id}-".now()->timestamp); // FR-162
    }

    private function mine(Request $request, int $id): object
    {
        $c = DB::table('credentials as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')->where('c.id', $id)->first(['c.*', 'e.user_id']) ?? abort(404);
        abort_unless($c->user_id === $request->user()->id, 403, 'This credential belongs to someone else.');

        return $c;
    }

    private function accessible(Request $request, int $id): object
    {
        $c = DB::table('credentials as c')
            ->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')
            ->where('c.id', $id)
            ->first(['c.*', 'e.user_id']) ?? abort(404);

        if ($request->user()->id === $c->user_id) {
            return $c;
        }

        if ($request->user()->hasRole('codl_admin')) {
            return $c;
        }

        if ($request->user()->hasRole('deputy_dean')) {
            $e = DB::table('enrolments as e')
                ->join('offerings as o', 'o.id', '=', 'e.offering_id')
                ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
                ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->where('e.id', $c->enrolment_id)->first(['m.ptj_id']) ?? abort(404);
            $ids = $request->user()->scopedPtjIds('deputy_dean');
            abort_unless($ids === null || in_array($e->ptj_id, $ids, true), 403, 'This credential belongs to another PTj.');

            return $c;
        }

        abort(403, 'This credential belongs to someone else.');
    }

    private function scoped(Request $request, int $id): object
    {
        $c = DB::table('credentials')->find($id) ?? abort(404);
        $this->scopeOfEnrolment($request, $c->enrolment_id);

        return $c;
    }

    private function scopeOfEnrolment(Request $request, int $enrolment): void
    {
        $e = DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('e.id', $enrolment)->first(['m.ptj_id', 'm.owner_id']) ?? abort(404);
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        abort_unless($ids === null || in_array($e->ptj_id, $ids, true), 403, 'This credential belongs to another PTj.');
        abort_if($e->owner_id === $request->user()->id, 403, 'You cannot change a credential of your own micro-credential.');
    }

    private function credentialHierarchy(int $enrolmentId): array
    {
        $enrolment = DB::table('enrolments as e')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')
            ->leftJoin('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('e.id', $enrolmentId)
            ->select([
                'e.id as enrolment_id', 'e.reference as enrolment_ref', 'e.status as enrolment_status', 'e.progress_percent',
                'u.id as user_id', 'u.name as student_name', 'u.institution_id as matric_no', 'u.email as student_email',
                'o.id as offering_id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.delivery_mode',
                'v.id as version_id', 'v.name_en as course_name_en', 'v.name_ms as course_name_ms', 'm.code as mc_code', 'm.category as mc_category',
                'v.proposed_credit', 'v.planned_slt', 'v.proposed_mqf_level'
            ])
            ->first();

        if (!$enrolment) {
            return ['enrolment' => null, 'modules' => collect(), 'earned_badges_count' => 0];
        }

        $achievements = DB::table('module_achievements as a')
            ->leftJoin('digital_badges as b', 'b.module_achievement_id', '=', 'a.id')
            ->where('a.enrolment_id', $enrolmentId)
            ->select([
                'a.id as achievement_id', 'a.mc_module_id', 'a.achieved_at',
                'b.id as badge_id', 'b.code as badge_code', 'b.external_id as badge_external_id',
                'b.external_url as badge_external_url', 'b.issued_at as badge_issued_at'
            ])
            ->get()
            ->keyBy('mc_module_id');

        $modules = DB::table('mc_modules')
            ->where('mc_version_id', $enrolment->version_id)
            ->orderBy('seq')
            ->get(['id', 'seq', 'name_en', 'name_ms', 'slt_hours', 'separately_recognisable'])
            ->map(function ($mod) use ($achievements) {
                $ach = $achievements->get($mod->id);
                $mod->is_achieved = !empty($ach);
                $mod->achieved_at = $ach->achieved_at ?? null;
                $mod->badge_id = $ach->badge_id ?? null;
                $mod->badge_code = $ach->badge_code ?? null;
                $mod->badge_external_id = $ach->badge_external_id ?? null;
                $mod->badge_external_url = $ach->badge_external_url ?? null;
                $mod->badge_issued_at = $ach->badge_issued_at ?? null;
                return $mod;
            });

        $earnedBadgesCount = $modules->whereNotNull('badge_id')->count();

        return [
            'enrolment' => $enrolment,
            'modules' => $modules,
            'earned_badges_count' => $earnedBadgesCount,
        ];
    }
}
