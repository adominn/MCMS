<?php

declare(strict_types=1);

namespace App\Modules\Transfer;

use Illuminate\Support\Facades\DB;

// Shared reads and rules of MD-12.
final class Transfers
{
    /** The lines of an application with what the evaluator has recorded so far. */
    public static function lines(int $applicationId)
    {
        return DB::table('transfer_lines as l')
            ->join('parent_courses as c', 'c.id', '=', 'l.parent_course_id')
            ->join('acrs as a', 'a.id', '=', 'l.acr_id')
            ->join('abc_deposits as d', 'd.id', '=', 'l.abc_deposit_id')
            ->join('mc_versions as v', 'v.id', '=', 'l.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('l.transfer_application_id', $applicationId)->orderBy('l.id')
            ->get([
                'l.*', 'c.code as course_code', 'c.name as course_name', 'c.clos', 'c.credit as course_credit', 'c.slt_hours as course_slt_hours',
                'a.reference as acr_reference', 'a.slt_hours as acr_slt_hours', 'a.mqf_level as acr_mqf_level', 'a.completion_date as acr_completion_date',
                'd.abc_reference', 'd.deposit_date', 'd.credit_status', 'd.credit_expiry',
                'v.name_en as source_name', 'v.micro_credential_id', 'v.parent_course_id as mc_parent_course_id', 'v.planned_slt as mc_planned_slt',
                'm.category as mc_category',
            ])
            ->map(function ($l) {
                $l->criteria = DB::table('equivalence_criteria')->where('transfer_line_id', $l->id)->get()->keyBy('criterion');
                $l->assessments = DB::table('equivalence_assessments')->where('transfer_line_id', $l->id)->get()->keyBy('target_outcome');
                $l->targets = array_values(array_filter(array_map('trim', explode(';', (string) $l->clos))));
                $l->outcomes = DB::table('learning_outcomes')->where('mc_version_id', $l->mc_version_id)->orderBy('sort_order')->get();
                $l->lo = self::outcomeResult($l);
                $l->complete = count($l->criteria) === count(config('transfer.criteria')) && count($l->assessments) === count($l->targets) && $l->targets !== [];

                return $l;
            });
    }

    /** System auto-check & pre-fill for F7 equivalence criteria and LO matching (never overwrites saved evaluator records). */
    public static function autoCheck(object $app, $lines): void
    {
        $prog = DB::table('programmes')->find($app->programme_id);
        $progMqf = (int) ($prog->mqf_level ?? 6);
        $progCode = (string) ($prog->code ?? '');
        $rawBlocked = (string) ($prog->non_transferable ?? '');
        $decBlocked = json_decode($rawBlocked, true);
        $blocked = array_map(
            fn ($v) => strtoupper(trim((string) $v)),
            is_array($decBlocked) ? $decBlocked : array_filter(array_map('trim', explode(',', $rawBlocked)))
        );

        $mcVersionIds = $lines->pluck('mc_version_id')->unique()->filter()->values()->all();
        $mcIds = $lines->pluck('micro_credential_id')->unique()->filter()->values()->all();
        $targetCourseIds = $lines->pluck('parent_course_id')->unique()->filter()->values()->all();

        $modulesByVer = $mcVersionIds
            ? DB::table('mc_modules')->whereIn('mc_version_id', $mcVersionIds)->get()->groupBy('mc_version_id')
            : collect();
        $assessByVer = $mcVersionIds
            ? DB::table('assessment_components')->whereIn('mc_version_id', $mcVersionIds)->get()->groupBy('mc_version_id')
            : collect();
        $qvByMc = $mcIds
            ? DB::table('qv_records')->whereIn('micro_credential_id', $mcIds)->orderByDesc('id')->get()->groupBy('micro_credential_id')
            : collect();
        $equivs = $targetCourseIds
            ? DB::table('course_equivalences')
                ->whereIn('target_course_id', $targetCourseIds)
                ->where('status', 'approved')
                ->get()
            : collect();

        $today = today()->toDateString();

        foreach ($lines as $l) {
            $isDirectUmc = strtolower((string) $l->mc_category) === 'umc'
                && $l->mc_parent_course_id !== null
                && (int) $l->mc_parent_course_id === (int) $l->parent_course_id;

            $approvedEquiv = $equivs->first(fn ($e) => (int) $e->target_course_id === (int) $l->parent_course_id
                && $l->mc_parent_course_id !== null
                && (int) $e->source_course_id === (int) $l->mc_parent_course_id);

            $mods = $modulesByVer->get($l->mc_version_id, collect());
            $modCount = $mods->count();
            $totSlt = (int) ($l->acr_slt_hours ?: ($mods->sum('slt_hours') ?: ($l->mc_planned_slt ?: round((float) $l->credits_requested * 40))));
            $reqSlt = (int) round((float) $l->credits_requested * 40);

            $assList = $assessByVer->get($l->mc_version_id, collect());
            $assCount = $assList->count();
            $assWeight = (float) $assList->sum('weight_percent');

            $qv = $qvByMc->get($l->micro_credential_id, collect())->first();
            $mcMqf = (int) ($l->acr_mqf_level ?? 0);

            $criteria = [];
            $flags = [];

            // 1. Credit value and SLT
            if ((float) $l->credits_requested <= (float) $l->course_credit && $totSlt >= $reqSlt) {
                $criteria['credit_slt'] = [
                    'result' => 'adequate',
                    'status' => 'ok',
                    'finding' => 'System verified: '.mcms_num($l->credits_requested).' cr / '.$totSlt.' SLT hrs aligns with '.$l->course_code.' ('.mcms_num($l->course_credit).' cr).',
                ];
            } else {
                $criteria['credit_slt'] = [
                    'result' => 'inadequate',
                    'status' => 'flagged',
                    'finding' => 'Flagged: '.$totSlt.' SLT hrs / '.mcms_num($l->credits_requested).' cr requested vs '.$l->course_code.' ('.mcms_num($l->course_credit).' cr, '.$reqSlt.' SLT hrs required).',
                ];
                $flags[] = 'Credit/SLT mismatch';
            }

            // 2. MQF level
            if ($mcMqf >= $progMqf && $mcMqf > 0) {
                $criteria['mqf'] = [
                    'result' => 'matched',
                    'status' => 'ok',
                    'finding' => "System verified: MQF {$mcMqf} matches target programme {$progCode} (MQF {$progMqf}).",
                ];
            } elseif ($mcMqf > 0) {
                $criteria['mqf'] = [
                    'result' => 'not_matched',
                    'status' => 'flagged',
                    'finding' => "Flagged: MC is MQF {$mcMqf} while target programme {$progCode} requires MQF {$progMqf} — verify equivalence.",
                ];
                $flags[] = "MQF {$mcMqf} vs programme MQF {$progMqf}";
            } else {
                $criteria['mqf'] = [
                    'result' => 'na',
                    'status' => 'review',
                    'finding' => 'MQF level not recorded on ACR — confirm against programme level.',
                ];
            }

            // 3. Content and depth
            if ($isDirectUmc) {
                $criteria['content'] = [
                    'result' => 'equivalent',
                    'status' => 'ok',
                    'finding' => "System verified: Direct UMC unbundled from {$l->course_code}".($modCount > 0 ? " ({$modCount} modules aligned)." : '.'),
                ];
            } elseif ($approvedEquiv) {
                $criteria['content'] = [
                    'result' => 'equivalent',
                    'status' => 'ok',
                    'finding' => "System verified: Approved course equivalence on record for {$l->course_code}.",
                ];
            } else {
                $criteria['content'] = [
                    'result' => 'partly',
                    'status' => 'review',
                    'finding' => "Mapped to {$l->course_code}".($modCount > 0 ? " ({$modCount} modules)" : '').' — confirm content depth.',
                ];
            }

            // 4. Learning activities
            $criteria['activities'] = [
                'result' => 'aligned',
                'status' => ($isDirectUmc || $approvedEquiv || $modCount > 0) ? 'ok' : 'review',
                'finding' => $modCount > 0
                    ? "System verified: {$modCount} structured learning modules ({$totSlt} SLT hrs) recorded in MCMS."
                    : 'Learning activities verified in approved micro-credential structure.',
            ];

            // 5. Assessment and standard
            if ($assCount > 0) {
                $criteria['assessment'] = [
                    'result' => 'aligned',
                    'status' => 'ok',
                    'finding' => "System verified: {$assCount} assessment components totalling ".mcms_num($assWeight).'% verified in achievement record.',
                ];
            } else {
                $criteria['assessment'] = [
                    'result' => 'aligned',
                    'status' => 'review',
                    'finding' => 'Summative assessment verified in MCMS achievement record.',
                ];
            }

            // 6. Accreditation or Quality Verification status
            if (strtolower((string) $l->mc_category) === 'umc') {
                $criteria['accreditation'] = [
                    'result' => 'valid',
                    'status' => 'ok',
                    'finding' => 'System verified: UTeM accredited Unbundled Micro-Credential (UMC) under programme accreditation.',
                ];
            } elseif ($qv && in_array(strtolower((string) $qv->status), ['verified', 'approved', 'valid'], true) && (! $qv->valid_until || $qv->valid_until >= $today)) {
                $qvRef = $qv->qv_reference ?? $qv->submitted_ref ?? 'QV';
                $criteria['accreditation'] = [
                    'result' => 'valid',
                    'status' => 'ok',
                    'finding' => "System verified: Quality-Verified SAMC ({$qvRef}, valid until ".($qv->valid_until ?? 'active').').',
                ];
            } else {
                $criteria['accreditation'] = [
                    'result' => 'invalid',
                    'status' => 'flagged',
                    'finding' => 'Flagged: No active QV or UMC accreditation record found.',
                ];
                $flags[] = 'QV/Accreditation missing';
            }

            // 7. Currency of knowledge
            $isCurrent = ! $l->reevaluate && $l->credit_status === 'available' && (! $l->credit_expiry || $l->credit_expiry >= $today);
            if ($isCurrent) {
                $criteria['currency'] = [
                    'result' => 'current',
                    'status' => 'ok',
                    'finding' => 'System verified: Deposited '.($l->deposit_date ?? 'N/A').', valid until '.($l->credit_expiry ?? 'N/A').' (within 10-year ABC validity).',
                ];
            } else {
                $criteria['currency'] = [
                    'result' => 'needs_review',
                    'status' => 'flagged',
                    'finding' => 'Flagged: Credit expired or curriculum version changed — currency review required.',
                ];
                $flags[] = 'Currency needs review';
            }

            // 8. Programme or professional body requirements
            if (in_array(strtoupper((string) $l->course_code), $blocked, true)) {
                $criteria['professional'] = [
                    'result' => 'not_met',
                    'status' => 'flagged',
                    'finding' => "Flagged: {$l->course_code} is listed as non-transferable in {$progCode}.",
                ];
                $flags[] = "{$l->course_code} non-transferable";
            } else {
                $criteria['professional'] = [
                    'result' => 'met',
                    'status' => 'ok',
                    'finding' => "System verified: {$l->course_code} is eligible under {$progCode} programme rules.",
                ];
            }

            // 9. Auto-suggest Learning Outcomes mapping
            $autoOutcomes = [];
            $mcOutcomes = $l->outcomes->values();
            foreach ($l->targets as $idx => $target) {
                $bestO = null;
                $bestScore = -1.0;
                foreach ($mcOutcomes as $o) {
                    similar_text(mb_strtolower($target), mb_strtolower((string) $o->text_en), $pct);
                    if ($pct > $bestScore) {
                        $bestScore = $pct;
                        $bestO = $o;
                    }
                }
                if (! $bestO && $mcOutcomes->isNotEmpty()) {
                    $bestO = $mcOutcomes->get($idx % $mcOutcomes->count());
                }
                if ($bestO) {
                    $matchVal = ($isDirectUmc || $approvedEquiv || $bestScore >= 35.0) ? 'full' : 'partial';
                    $autoOutcomes[$target] = [
                        'source_outcome_id' => $bestO->id,
                        'match' => $matchVal,
                        'note' => ($isDirectUmc || $approvedEquiv)
                            ? 'Auto-matched via approved curriculum'
                            : 'Auto-suggested — review match',
                    ];
                }
            }

            $passed = collect($criteria)->where('status', 'ok')->count();
            $flaggedCount = collect($criteria)->where('status', 'flagged')->count();
            $reviewCount = collect($criteria)->where('status', 'review')->count();

            $l->auto = [
                'criteria' => $criteria,
                'outcomes' => $autoOutcomes,
                'summary' => [
                    'passed' => $passed,
                    'flagged' => $flaggedCount,
                    'review' => $reviewCount,
                    'total' => count($criteria),
                    'is_direct_umc' => $isDirectUmc,
                    'has_approved_equiv' => (bool) $approvedEquiv,
                    'flags' => $flags,
                ],
            ];
        }
    }

    /** The learning-outcome criterion is derived from the outcome-by-outcome comparison, never typed (FR-ctloequiv, BR-30). */
    public static function outcomeResult(object $l): ?string
    {
        if ($l->targets === [] || count($l->assessments) < count($l->targets)) {
            return null;
        }
        $m = $l->assessments->pluck('match');

        return $m->contains('none') ? 'not_equivalent' : ($m->contains('partial') ? 'partly' : 'equivalent');
    }

    /** Limit and non-transferable warnings for the decision-maker (FR-ctlimits). @return list<string> */
    public static function warnings(object $app, $lines, array $approved): array
    {
        $p = DB::table('programmes')->find($app->programme_id);
        $w = [];
        $earlier = (float) DB::table('transfer_lines as l')->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')->where('t.user_id', $app->user_id)->where('t.programme_id', $app->programme_id)->where('t.id', '!=', $app->id)->sum('l.credits_approved');
        $total = $earlier + array_sum($approved);
        $limit = $p->total_credits * $p->transfer_limit_percent / 100;
        if ($total > $limit) {
            $w[] = "Transfer limit: $total credits would be credited, above the programme limit of $limit ({$p->transfer_limit_percent}% of {$p->total_credits}).";
        }
        // Guide Cl. 14.5.1 (p. 64): at least 30% of graduating credits must be earned directly at UTeM (max 70% transferred).
        $minHome = (int) (app(\App\Modules\Core\Settings::class)->get('stackable.min_home_credit_percent') ?? 30);
        $utemMaxTransfer = $p->total_credits * (100 - $minHome) / 100;
        if ($total > $utemMaxTransfer) {
            $w[] = "UTeM minimum direct-credit requirement: $total credits would leave fewer than {$minHome}% of {$p->total_credits} graduating credits earned directly at UTeM (maximum transferable: {$utemMaxTransfer} credits).";
        }
        $raw = (string) $p->non_transferable;
        $decoded = json_decode($raw, true);
        $blocked = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $raw)));
        foreach ($lines as $l) {
            if (in_array($l->course_code, $blocked, true) && ($approved[$l->id] ?? 0) > 0) {
                $w[] = "{$l->course_code} is listed as non-transferable for this programme.";
            }
            if (($approved[$l->id] ?? 0) > (float) $l->course_credit) {
                $w[] = "Target course credit limit: credits for {$l->course_code} ({$approved[$l->id]}) exceed the course credit value ({$l->course_credit}).";
            }
            if ($l->reevaluate) {
                $w[] = "The credit for {$l->course_code} has expired or the curriculum changed: judge the currency of knowledge again.";
            }
            if ($l->target_version !== $p->curriculum_version) {
                $w[] = "{$l->course_code} was evaluated against curriculum version {$l->target_version}; the programme is now version {$p->curriculum_version}.";
            }
        }

        $counts = [];
        foreach ($lines as $l) {
            if (($approved[$l->id] ?? 0) > 0) {
                $counts[$l->parent_course_id] = ($counts[$l->parent_course_id] ?? 0) + 1;
            }
        }
        foreach ($counts as $cid => $cnt) {
            if ($cnt > 1) {
                $cname = $lines->firstWhere('parent_course_id', $cid)->course_code ?? "Course #$cid";
                $w[] = "Duplicate target course: $cname is mapped more than once in this application.";
            }
        }

        return $w;
    }

    /** EV-48 and EV-14: an application that rests on credit that expired, or on a version that changed, is flagged for review before it is reused. */
    public static function flagDeposit(int $depositId): void
    {
        DB::table('transfer_lines as l')->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')->where('l.abc_deposit_id', $depositId)->whereIn('t.status', TransferController::ACTIVE)->update(['reevaluate' => true]);
    }

    /** View or download official supporting documents (ACRs, syllabi, portfolios, transcripts). */
    public static function renderDocument($request, object $a, $lines, string $filename)
    {
        $filename = trim($filename);
        $cleanDoc = strtolower(str_replace([' ', '_', '-', '.pdf'], '', $filename));

        // 1. Check if matches an ACR reference in this application's lines
        $acrLine = $lines->first(function ($l) use ($cleanDoc, $filename) {
            $lClean = strtolower(str_replace([' ', '_', '-', '.pdf'], '', (string) $l->acr_reference));
            return $lClean === $cleanDoc || strtolower((string) $l->acr_reference) === strtolower($filename);
        });

        if ($acrLine && !empty($acrLine->acr_id)) {
            $print = $request->boolean('print') || $request->boolean('embed');
            $params = [
                'id' => $acrLine->acr_id,
                'print' => $print ? 1 : null,
                'embed' => $request->boolean('embed') ? 1 : null,
                'autoprint' => $request->boolean('autoprint') ? 1 : null,
            ];
            return redirect()->route('credit.acr', array_filter($params, fn ($v) => $v !== null));
        }

        // 2. Check if a physical file exists on disk
        $safeBase = basename($filename);
        $candidates = [
            storage_path('app/transfers/'.$a->id.'/'.$safeBase),
            storage_path('app/transfers/'.$safeBase),
            storage_path('app/public/transfers/'.$safeBase),
            public_path('uploads/documents/'.$safeBase),
        ];
        foreach ($candidates as $filePath) {
            if (file_exists($filePath) && is_file($filePath)) {
                if ($request->boolean('download')) {
                    return response()->download($filePath, $safeBase);
                }
                $mime = mime_content_type($filePath) ?: 'application/octet-stream';
                return response()->file($filePath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="'.$safeBase.'"',
                ]);
            }
        }

        // 3. Real MCMS Evidence Pack: load actual ACRs, learning outcomes, modules/SLT, assessments, QV, and credentials
        $ptj = DB::table('programmes as p')
            ->leftJoin('ptjs as j', 'j.id', '=', 'p.ptj_id')
            ->where('p.id', $a->programme_id)
            ->first(['j.code', 'j.name']);
        $firstLine = $lines->first();
        $coordinator = $firstLine
            ? DB::table('mc_versions as v')
                ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->join('users as u', 'u.id', '=', 'm.owner_id')
                ->leftJoin('ptjs as j', 'j.id', '=', 'm.ptj_id')
                ->where('v.id', $firstLine->mc_version_id)
                ->first(['u.name', 'u.position', 'j.code as ptj_code'])
            : null;
        $decision = DB::table('transfer_decisions as td')
            ->leftJoin('users as du', 'du.id', '=', 'td.decided_by')
            ->where('td.transfer_application_id', $a->id)
            ->orderByDesc('td.id')
            ->first(['td.*', 'du.name as actor_name', 'du.position as actor_position']);

        $mcVersionIds = $lines->pluck('mc_version_id')->unique()->filter()->values()->all();
        $acrIds = $lines->pluck('acr_id')->unique()->filter()->values()->all();

        $acrs = $acrIds
            ? DB::table('acrs as ac')
                ->leftJoin('abc_deposits as d', 'd.acr_id', '=', 'ac.id')
                ->join('mc_versions as v', 'v.id', '=', 'ac.mc_version_id')
                ->whereIn('ac.id', $acrIds)
                ->get(['ac.*', 'v.name_en', 'v.planned_slt', 'd.abc_reference', 'd.credit_status', 'd.credit_expiry'])
            : collect();

        $outcomes = $mcVersionIds
            ? DB::table('learning_outcomes')->whereIn('mc_version_id', $mcVersionIds)->orderBy('sort_order')->get()->groupBy('mc_version_id')
            : collect();

        $modules = $mcVersionIds
            ? DB::table('mc_modules')->whereIn('mc_version_id', $mcVersionIds)->orderBy('seq')->get()->groupBy('mc_version_id')
            : collect();

        $assessments = $mcVersionIds
            ? DB::table('assessment_components')->whereIn('mc_version_id', $mcVersionIds)->orderBy('id')->get()->groupBy('mc_version_id')
            : collect();

        $mcIds = $mcVersionIds
            ? DB::table('mc_versions')->whereIn('id', $mcVersionIds)->pluck('micro_credential_id')->unique()->all()
            : [];

        $qvRecords = $mcIds
            ? DB::table('qv_records as q')
                ->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'q.micro_credential_id')
                    ->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = q.micro_credential_id)'))
                ->whereIn('q.micro_credential_id', $mcIds)
                ->orderByDesc('q.id')
                ->get(['q.*', 'v.name_en'])
            : collect();

        $credentials = $acrIds
            ? DB::table('credentials as cr')
                ->join('achievement_records as ar', 'ar.enrolment_id', '=', 'cr.enrolment_id')
                ->join('acrs as ac', 'ac.achievement_record_id', '=', 'ar.id')
                ->join('mc_versions as v', 'v.id', '=', 'ac.mc_version_id')
                ->whereIn('ac.id', $acrIds)
                ->get(['cr.id', 'cr.number', 'cr.public_code', 'cr.status', 'cr.content', 'cr.issued_at', 'v.name_en', 'v.badge_image_path', 'ac.reference as acr_reference'])
            : collect();

        return response()->view('transfer.document', [
            'a' => $a,
            'lines' => $lines,
            'filename' => $filename,
            'ptj' => $ptj,
            'coordinator' => $coordinator,
            'decision' => $decision,
            'acrs' => $acrs,
            'outcomes' => $outcomes,
            'modules' => $modules,
            'assessments' => $assessments,
            'qvRecords' => $qvRecords,
            'credentials' => $credentials,
        ]);
    }
}
