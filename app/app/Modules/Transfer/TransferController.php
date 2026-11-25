<?php

declare(strict_types=1);

namespace App\Modules\Transfer;

use App\Modules\Core\Tasks;
use App\Modules\CreditBank\CreditLedger;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// ST-11 to ST-13: the participant applies for credit transfer. Acceptance is decided by the receiving programme, never by MCMS or ABC (BR-receiver).
final class TransferController
{
    public const ACTIVE = ['submitted', 'under_eval', 'more_evidence'];

    public function __construct(private AuditRecorder $audit, private CreditLedger $ledger) {}

    public function index(Request $request)
    {
        $rows = DB::table('transfer_applications as t')->join('programmes as p', 'p.id', '=', 't.programme_id')->where('t.user_id', $request->user()->id)->orderByDesc('t.id')->get(['t.*', 'p.code as programme', 'p.name as programme_name']);

        return view('transfer.index', ['rows' => $rows, 'status' => config('transfer.status')]);
    }

    public function create(Request $request)
    {
        $programmes = DB::table('programmes')->orderBy('code')->get();
        $courses = DB::table('parent_courses')
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'withdrawn'))
            ->orderBy('code')
            ->get();

        // Published pathway mappings: [programme_id][micro_credential_id] => parent_course_id
        $pathwayMaps = [];
        $progCourseIds = [];
        $pwRows = DB::table('pathway_items as i')
            ->join('pathways as w', 'w.id', '=', 'i.pathway_id')
            ->where('w.status', 'published')
            ->get(['w.programme_id', 'i.micro_credential_id', 'i.parent_course_id']);
        foreach ($pwRows as $r) {
            $pathwayMaps[(int) $r->programme_id][(int) $r->micro_credential_id] = (int) $r->parent_course_id;
            $progCourseIds[(int) $r->programme_id][] = (int) $r->parent_course_id;
        }

        // Approved course equivalences: [source_course_id][target_programme_id] => target_course_id
        $equivMaps = [];
        $eqRows = DB::table('course_equivalences')
            ->where('status', 'approved')
            ->whereIn('equivalence_type', ['exact', 'partial', 'conditional'])
            ->get(['source_course_id', 'target_course_id', 'target_programme_id']);
        foreach ($eqRows as $er) {
            if ($er->target_programme_id) {
                $equivMaps[(int) $er->source_course_id][(int) $er->target_programme_id] = (int) $er->target_course_id;
            }
        }

        // Non-transferable course codes per programme
        $nonTransferable = [];
        foreach ($programmes as $p) {
            $raw = (string) ($p->non_transferable ?? '');
            $decoded = json_decode($raw, true);
            $list = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $raw)));
            $nonTransferable[(int) $p->id] = array_map(fn ($v) => strtoupper(trim((string) $v)), $list);
        }

        // Courses placed in each programme's active curriculum version
        $plRows = DB::table('programme_course_placements as pl')
            ->join('programme_versions as pv', 'pv.id', '=', 'pl.programme_version_id')
            ->where('pv.status', 'active')
            ->get(['pv.programme_id', 'pl.parent_course_id']);
        foreach ($plRows as $pl) {
            $progCourseIds[(int) $pl->programme_id][] = (int) $pl->parent_course_id;
        }
        foreach ($courses as $c) {
            if ($c->programme_id) {
                $progCourseIds[(int) $c->programme_id][] = (int) $c->id;
            }
        }
        foreach ($progCourseIds as $pid => $cids) {
            $progCourseIds[$pid] = array_values(array_unique($cids));
        }

        $defaultProgId = (int) (old('programme_id') ?: ($programmes->firstWhere('code', 'BITS')->id ?? $programmes->first()->id ?? 0));

        $deposits = DB::table('abc_deposits as d')
            ->join('acrs as a', 'a.id', '=', 'd.acr_id')
            ->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('a.user_id', $request->user()->id)
            ->whereNotNull('d.credit_status')
            ->get(['d.*', 'a.reference as acr_reference', 'a.mc_version_id', 'a.mqf_level as acr_mqf_level', 'v.micro_credential_id', 'v.parent_course_id', 'v.name_en', 'm.category as mc_category'])
            ->map(function ($d) use ($programmes, $courses, $pathwayMaps, $equivMaps, $nonTransferable, $progCourseIds, $defaultProgId) {
                $d->why = $this->ledger->whyNot($d);
                $d->expired = $d->credit_status === 'expired' || $d->credit_expiry < today()->toDateString();
                $d->busy = $this->activeLine($d->id);
                if ($d->busy) {
                    $d->why = 'This credit is already in an application that is still being evaluated ('.$d->busy->reference.'). Wait for its decision.';
                }
                $d->selectable = ! $d->why || ($d->expired && $d->credit_status !== 'provisional' && $d->credit_status !== 'redeemed' && ! $d->busy);

                // Auto-detect closest transferable course per programme
                $suggestions = [];
                foreach ($programmes as $p) {
                    $pid = (int) $p->id;
                    $blocked = $nonTransferable[$pid] ?? [];
                    $matchId = null;

                    // 1. Exact published pathway mapping
                    if (isset($pathwayMaps[$pid][(int) $d->micro_credential_id])) {
                        $matchId = $pathwayMaps[$pid][(int) $d->micro_credential_id];
                    }
                    // 2. Approved course equivalence from UMC parent course
                    elseif ($d->parent_course_id && isset($equivMaps[(int) $d->parent_course_id][$pid])) {
                        $matchId = $equivMaps[(int) $d->parent_course_id][$pid];
                    }
                    // 3. UMC parent course itself if transferable
                    elseif ($d->parent_course_id) {
                        $pc = $courses->firstWhere('id', (int) $d->parent_course_id);
                        if ($pc && ! in_array(strtoupper($pc->code), $blocked, true)) {
                            $matchId = (int) $pc->id;
                        }
                    }
                    // 4. Closest title similarity match among transferable courses (preferring programme courses)
                    if (! $matchId) {
                        $bestScore = 0.0;
                        $inProg = $progCourseIds[$pid] ?? [];
                        foreach ($courses as $c) {
                            if (in_array(strtoupper($c->code), $blocked, true)) {
                                continue;
                            }
                            similar_text(mb_strtolower($d->name_en), mb_strtolower($c->name), $pct);
                            $score = $pct + (in_array((int) $c->id, $inProg, true) ? 15.0 : 0.0);
                            if ($score > $bestScore) {
                                $bestScore = $score;
                                $matchId = (int) $c->id;
                            }
                        }
                    }
                    if ($matchId) {
                        $suggestions[$pid] = $matchId;
                    }
                }

                $d->suggestions = $suggestions;
                $d->suggested_course_id = $suggestions[$defaultProgId] ?? ($courses->first()->id ?? null);

                return $d;
            });

        return view('transfer.create', [
            'deposits' => $deposits,
            'programmes' => $programmes,
            'courses' => $courses,
            'nonTransferable' => $nonTransferable,
            'progCourseIds' => $progCourseIds,
        ]);
    }

    public function store(Request $request)
    {
        $validDocs = implode(',', array_keys(config('transfer.documents_checklist')));
        $d = $request->validate([
            'programme_id' => 'required|integer|exists:programmes,id',
            'institution' => 'nullable|string|max:120',
            'document_checklist' => 'nullable|array',
            'document_checklist.*' => "in:$validDocs",
            'other_document' => 'nullable|string|max:500',
            'other_files' => 'nullable|array|max:5',
            'other_files.*' => 'file|mimes:pdf,png,jpg,jpeg,doc,docx|max:10240',
            'documents' => 'nullable|string|max:2000',
            'deposits' => 'required|array|min:1',
            'deposits.*' => 'integer',
            'course' => 'required|array',
            'credits' => 'required|array',
        ], [
            'deposits.required' => 'Choose the credit you want to use.',
            'other_files.*.mimes' => 'Supporting documents must be PDF, PNG, JPG, DOC, or DOCX.',
            'other_files.*.max' => 'Each supporting document must not exceed 10 MB.',
        ]);
        $checkedDocs = array_values(array_unique($d['document_checklist'] ?? []));
        $otherDoc = trim((string) ($d['other_document'] ?? '')) ?: null;
        $notesDoc = trim((string) ($d['documents'] ?? '')) ?: null;

        if (in_array('other', $checkedDocs, true)) {
            $errors = [];
            if (! $request->hasFile('other_files')) {
                $errors['other_files'] = 'Please upload at least one supporting document (PDF, image, or Word file) when "Other supporting documents" is ticked.';
            }
            if ($otherDoc === null) {
                $errors['other_document'] = 'Please specify what the other supporting document is.';
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        }
        $checklistPayload = ($checkedDocs !== [] || $otherDoc !== null || $notesDoc !== null)
            ? json_encode(['items' => $checkedDocs, 'other' => $otherDoc, 'notes' => $notesDoc])
            : null;
        $docSummaryParts = array_map(fn ($k) => config("transfer.documents_checklist.$k", $k), $checkedDocs);
        if ($otherDoc) {
            $docSummaryParts[] = 'Other: '.$otherDoc;
        }
        if ($notesDoc) {
            $docSummaryParts[] = $notesDoc;
        }
        $documentsText = $docSummaryParts !== [] ? implode('; ', $docSummaryParts) : null;

        $prog = DB::table('programmes')->find($d['programme_id']);
        $rawBlocked = (string) ($prog->non_transferable ?? '');
        $decBlocked = json_decode($rawBlocked, true);
        $blockedCodes = array_map(
            fn ($v) => strtoupper(trim((string) $v)),
            is_array($decBlocked) ? $decBlocked : array_filter(array_map('trim', explode(',', $rawBlocked)))
        );

        $lines = [];
        foreach (array_unique(array_map('intval', $d['deposits'])) as $did) {
            $dep = DB::table('abc_deposits as d')->join('acrs as a', 'a.id', '=', 'd.acr_id')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->where('d.id', $did)->where('a.user_id', $request->user()->id)->first(['d.*', 'a.mc_version_id', 'a.mqf_level as acr_mqf_level', 'v.version_no', 'v.name_en', 'm.category']) ?? abort(404);
            if ($dep->category === 'mce') {
                throw ValidationException::withMessages(['deposits' => 'A Micro-Credential Eksekutif cannot be used directly for credit transfer.']); // BR-32
            }
            if ($dep->acr_mqf_level && $prog->mqf_level && (int) $dep->acr_mqf_level < (int) $prog->mqf_level) {
                throw ValidationException::withMessages(['deposits' => "{$dep->name_en} is MQF {$dep->acr_mqf_level}, which is below {$prog->code}'s requirement (MQF {$prog->mqf_level})."]);
            }
            if ($busy = $this->activeLine($did)) {
                throw ValidationException::withMessages(['deposits' => "This credit is already in application {$busy->reference}, which is still being evaluated. Wait for its decision."]); // FR-rdconflict, first
            }
            $expired = $dep->credit_status === 'expired' || $dep->credit_expiry < today()->toDateString();
            if (! $expired && ($why = $this->ledger->whyNot($dep))) {
                throw ValidationException::withMessages(['deposits' => $why]);
            }
            if ($expired && in_array($dep->credit_status, ['provisional', 'redeemed'], true)) {
                throw ValidationException::withMessages(['deposits' => $this->ledger->whyNot($dep)]);
            }
            $courseId = (int) ($d['course'][$did] ?? 0);
            $progId = (int) $d['programme_id'];
            $course = DB::table('parent_courses')
                ->where('id', $courseId)
                ->where(function ($q) use ($progId) {
                    $q->where('programme_id', $progId)
                        ->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                            ->from('programme_course_placements as pl')
                            ->join('programme_versions as pv', 'pv.id', '=', 'pl.programme_version_id')
                            ->whereColumn('pl.parent_course_id', 'parent_courses.id')
                            ->where('pv.programme_id', $progId)
                            ->where('pv.status', 'active'))
                        ->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                            ->from('pathway_items as pi')
                            ->join('pathways as pw', 'pw.id', '=', 'pi.pathway_id')
                            ->whereColumn('pi.parent_course_id', 'parent_courses.id')
                            ->where('pw.programme_id', $progId)
                            ->where('pw.status', 'published'));
                })
                ->first();
            if (! $course) {
                throw ValidationException::withMessages(['course' => 'Choose a course of the target programme for each credit.']);
            }
            if (in_array(strtoupper((string) $course->code), $blockedCodes, true)) {
                throw ValidationException::withMessages(['course' => "{$course->code} is listed as non-transferable for {$prog->code}."]);
            }
            $cr = (float) ($d['credits'][$did] ?? 0);
            if ($cr <= 0 || $cr > (float) $dep->credits) {
                throw ValidationException::withMessages(['credits' => 'Credits requested must be more than 0 and not more than the credit you hold ('.(float) $dep->credits.').']);
            }
            if ($cr > (float) $course->credit) {
                throw ValidationException::withMessages(['credits' => "Credits requested for {$course->code} ({$cr}) cannot exceed the course credit value ({$course->credit})."]);
            }
            $lines[] = [$dep, $course, $cr, $expired];
        }
        $app = DB::transaction(function () use ($d, $lines, $request, $checkedDocs, $otherDoc, $notesDoc, $checklistPayload, $documentsText, $prog) {
            $id = DB::table('transfer_applications')->insertGetId([
                'reference' => 'TMP'.bin2hex(random_bytes(8)),
                'user_id' => $request->user()->id,
                'programme_id' => $prog->id,
                'institution' => ($d['institution'] ?? null) ?: 'UTeM',
                'documents' => $documentsText,
                'document_checklist' => $checklistPayload,
                'applied_by' => $request->user()->id,
                'applied_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ref = 'TR-'.now()->year.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            $updates = ['reference' => $ref];

            if (in_array('other', $checkedDocs, true) && $request->hasFile('other_files')) {
                $storedNames = [];
                $targetDir = storage_path('app/transfers/'.$id);
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                foreach ($request->file('other_files') as $file) {
                    if (! $file || ! $file->isValid()) {
                        continue;
                    }
                    $orig = basename((string) $file->getClientOriginalName());
                    $safe = preg_replace('/[^A-Za-z0-9._ -]/', '_', $orig) ?: ('document_'.time().'.'.$file->getClientOriginalExtension());
                    $candidate = $safe;
                    $n = 1;
                    while (file_exists($targetDir.DIRECTORY_SEPARATOR.$candidate)) {
                        $ext = pathinfo($safe, PATHINFO_EXTENSION);
                        $base = pathinfo($safe, PATHINFO_FILENAME);
                        $candidate = $base.'_'.$n.($ext ? '.'.$ext : '');
                        $n++;
                    }
                    $file->move($targetDir, $candidate);
                    $storedNames[] = $candidate;
                }
                if ($storedNames !== []) {
                    $updates['document_checklist'] = json_encode([
                        'items' => $checkedDocs,
                        'other' => $otherDoc,
                        'notes' => $notesDoc,
                        'uploaded_files' => $storedNames,
                    ]);
                    $updates['documents'] = trim(($documentsText ? $documentsText.'; ' : '').'Uploaded: '.implode(', ', $storedNames));
                }
            }

            DB::table('transfer_applications')->where('id', $id)->update($updates);
            foreach ($lines as [$dep, $course, $cr, $expired]) {
                DB::table('transfer_lines')->insert(['transfer_application_id' => $id, 'acr_id' => $dep->acr_id, 'abc_deposit_id' => $dep->id, 'mc_version_id' => $dep->mc_version_id, 'parent_course_id' => $course->id, 'credits_requested' => $cr,
                    'reevaluate' => $expired, 'target_version' => $prog->curriculum_version, 'source_version_no' => $dep->version_no, 'created_at' => now(), 'updated_at' => now()]);
            }
            Tasks::open('codl_admin', 'transfer_eval', $id, "Evaluate credit transfer $ref ({$request->user()->name})", "/transfers/$id", area: 'transfers');
            $this->audit->record('transfer.applied', ['type' => 'TransferApplication', 'id' => $id], after: ['reference' => $ref, 'lines' => count($lines)]); // EV-51

            return $id;
        });

        return redirect()->route('transfer.show', $app)->with('status', 'Application submitted. CODL and Senate will evaluate it. Applying does not mean the credit is accepted or that you are admitted.');
    }

    public function show(Request $request, int $id)
    {
        $a = DB::table('transfer_applications as t')
            ->join('programmes as p', 'p.id', '=', 't.programme_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('t.id', $id)
            ->where('t.user_id', $request->user()->id)
            ->first(['t.*', 'p.code as programme', 'p.name as programme_name', 'u.name as user_name', 'u.institution_id', 'u.email as user_email']) ?? abort(404);
        $lines = Transfers::lines($id);
        $decision = DB::table('transfer_decisions as td')
            ->leftJoin('users as du', 'du.id', '=', 'td.decided_by')
            ->where('td.transfer_application_id', $id)
            ->orderByDesc('td.id')
            ->first(['td.*', 'du.name as actor_name', 'du.position as actor_position']);
        $depositIds = $lines->pluck('abc_deposit_id')->unique()->all();
        $acrIds = $lines->pluck('acr_id')->unique()->all();
        $redemptions = $depositIds
            ? DB::table('redemption_events as re')
                ->leftJoin('users as ru', 'ru.id', '=', 're.recorded_by')
                ->whereIn('re.abc_deposit_id', $depositIds)
                ->orderBy('re.id')
                ->get(['re.*', 'ru.name as recorded_by_name', 'ru.position as recorded_by_position'])
            : collect();
        $verifications = $acrIds
            ? DB::table('acr_verifications')->whereIn('acr_id', $acrIds)->orderBy('id')->get()
            : collect();

        $targetCourseIds = $lines->pluck('parent_course_id')->unique()->filter()->all();
        $approvedEquivalences = $targetCourseIds
            ? DB::table('course_equivalences as e')
                ->join('parent_courses as sc', 'sc.id', '=', 'e.source_course_id')
                ->whereIn('e.target_course_id', $targetCourseIds)
                ->where('e.status', 'approved')
                ->get(['e.*', 'sc.code as source_code', 'sc.name as source_name'])
                ->groupBy('target_course_id')
            : collect();

        return view('transfer.show', [
            'a' => $a,
            'lines' => $lines,
            'decision' => $decision,
            'redemptions' => $redemptions,
            'verifications' => $verifications,
            'approvedEquivalences' => $approvedEquivalences,
            'status' => config('transfer.status'),
        ]);
    }

    public function document(Request $request, int $id, string $filename)
    {
        $a = DB::table('transfer_applications as t')
            ->join('programmes as p', 'p.id', '=', 't.programme_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('t.id', $id)
            ->where('t.user_id', $request->user()->id)
            ->first(['t.*', 'p.code as programme', 'p.name as programme_name', 'u.name as user_name', 'u.institution_id', 'u.email as user_email']) ?? abort(404);
        $lines = Transfers::lines($id);

        return Transfers::renderDocument($request, $a, $lines, $filename);
    }

    /** The participant answers a request for more evidence (returns to under_eval if validity already passed, else submitted). */
    public function evidence(Request $request, int $id)
    {
        $a = DB::table('transfer_applications')->where('id', $id)->where('user_id', $request->user()->id)->first() ?? abort(404);
        abort_unless($a->status === 'more_evidence', 422, 'No evidence is requested for this application.');
        $d = $request->validate([
            'documents' => 'nullable|required_without:files|string|max:2000',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|mimes:pdf,png,jpg,jpeg,doc,docx|max:10240',
        ], [
            'documents.required_without' => 'Describe what you are adding, provide a link, or upload at least one file.',
        ]);

        $storedNames = [];
        $targetDir = storage_path('app/transfers/'.$a->id);
        if ($request->hasFile('files')) {
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            foreach ($request->file('files') as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $orig = basename((string) $file->getClientOriginalName());
                $safe = preg_replace('/[^A-Za-z0-9._ -]/', '_', $orig) ?: ('evidence_'.time().'.'.$file->getClientOriginalExtension());
                $candidate = $safe;
                $n = 1;
                while (file_exists($targetDir.DIRECTORY_SEPARATOR.$candidate)) {
                    $ext = pathinfo($safe, PATHINFO_EXTENSION);
                    $base = pathinfo($safe, PATHINFO_FILENAME);
                    $candidate = $base.'_'.$n.($ext ? '.'.$ext : '');
                    $n++;
                }
                $file->move($targetDir, $candidate);
                $storedNames[] = $candidate;
            }
        }

        $noteText = trim((string) ($d['documents'] ?? ''));
        $checklist = json_decode((string) ($a->document_checklist ?? ''), true) ?: ['items' => [], 'other' => null, 'notes' => null];
        $existingFiles = is_array($checklist['uploaded_files'] ?? null) ? $checklist['uploaded_files'] : [];
        if ($storedNames !== []) {
            $checklist['uploaded_files'] = array_values(array_unique(array_merge($existingFiles, $storedNames)));
            if (! in_array('other', $checklist['items'] ?? [], true)) {
                $checklist['items'][] = 'other';
            }
        }
        if ($noteText !== '') {
            $checklist['notes'] = trim(((string) ($checklist['notes'] ?? ''))."\n".$noteText);
        }

        $summaryAdd = trim(implode('; ', array_filter([$noteText, $storedNames !== [] ? 'Uploaded: '.implode(', ', $storedNames) : ''])));
        $nextStatus = ! empty($a->validity) ? 'under_eval' : 'submitted';

        DB::transaction(function () use ($a, $checklist, $summaryAdd, $nextStatus) {
            DB::table('transfer_applications')->where('id', $a->id)->update([
                'status' => $nextStatus,
                'documents' => trim(((string) $a->documents)."\n".$summaryAdd),
                'document_checklist' => json_encode($checklist),
                'updated_at' => now(),
            ]);
            Tasks::close('transfer_evidence', $a->id);
            Tasks::open('codl_admin', 'transfer_eval', $a->id, "Evaluate credit transfer {$a->reference}: evidence added", "/transfers/{$a->id}", area: 'transfers');
        });

        return back()->with('status', 'Evidence added. The application is back with the evaluator.');
    }

    private function activeLine(int $depositId): ?object
    {
        return DB::table('transfer_lines as l')->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')->where('l.abc_deposit_id', $depositId)->whereIn('t.status', self::ACTIVE)->first(['t.reference']);
    }
}
