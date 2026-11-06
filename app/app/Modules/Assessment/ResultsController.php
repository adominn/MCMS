<?php

declare(strict_types=1);

namespace App\Modules\Assessment;

use App\Models\User;
use App\Modules\Core\Tasks;
use App\Modules\Learning\Template;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// LEC-10 cohort results, DD-08 results and completion, DD-09 verify results, the student's own results (MD-07).
final class ResultsController
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier, private Verifier $verifier) {}

    // ---- the lecturer

    public function cohort(Request $request, int $id)
    {
        $o = $this->offering($request, $id, lecturer: true);

        return view('results.cohort', $this->grid($o) + ['o' => $o, 'editable' => ! $o->results_submitted_at, 'locked' => $o->results_submitted_at]);
    }

    // FR-120: enter results per participant and component, submit them for verification.
    public function save(Request $request, int $id)
    {
        $o = $this->offering($request, $id, lecturer: true);
        $action = $request->input('action', 'save');
        if ($action === 'withdraw') {
            abort_unless($o->results_submitted_at, 403);
            DB::table('offerings')->where('id', $id)->update(['results_submitted_at' => null]);
            Tasks::close('verify_results', $id);
            $this->audit->record('results.withdrawn', ['type' => 'Offering', 'id' => $id]);

            return redirect()->route('results.cohort', $id)->with('status', 'The submission is withdrawn. You can change the marks again.');
        }
        abort_if($o->results_submitted_at, 403, 'The results are submitted for verification. Withdraw the submission to change them.');

        $comps = Template::components($o->mc_version_id)->keyBy('id');
        $mine = DB::table('enrolments')->where('offering_id', $id)->whereIn('status', ['enrolled', 'completed', 'not_completed'])->pluck('id')->all();
        $errors = [];
        $count = 0;
        DB::transaction(function () use ($request, $comps, $mine, &$errors, &$count) {
            foreach ((array) $request->input('score') as $eid => $row) {
                foreach ((array) $row as $cid => $v) {
                    $v = trim((string) $v);
                    if ($v === '' || ! in_array((int) $eid, $mine, true) || ! isset($comps[$cid])) {
                        continue;
                    }
                    if (! preg_match('/^\d+(\.\d{1,2})?$/', $v) || (float) $v > (float) $comps[$cid]->max_mark) {
                        $errors[] = "Enrolment $eid, ".Template::key($comps[$cid]).": '$v' is not a mark from 0 to ".rtrim(rtrim((string) $comps[$cid]->max_mark, '0'), '.').' with at most two decimals.';

                        continue;
                    }
                    $existing = DB::table('assessment_results')->where('enrolment_id', $eid)->where('assessment_component_id', $cid)->first();
                    if ($existing && $existing->status === 'verified') {
                        continue; // a verified result is never updated; ask for a change instead
                    }
                    if ($existing && (float) $existing->score === (float) $v) {
                        continue;
                    }
                    $vals = ['score' => $v, 'source' => 'manual', 'entered_by' => $request->user()->id, 'updated_at' => now()];
                    $existing ? DB::table('assessment_results')->where('id', $existing->id)->update($vals) : DB::table('assessment_results')->insert($vals + ['enrolment_id' => $eid, 'assessment_component_id' => $cid, 'status' => 'provisional', 'created_at' => now()]);
                    $count++;
                }
            }
            $count && $this->audit->record('results.entered', ['type' => 'Offering', 'id' => $request->route('id')], after: ['marks' => $count]);
        });
        if ($errors) {
            return redirect()->route('results.cohort', $id)->withErrors(['score' => $errors]);
        }

        if ($action === 'submit') {
            $missing = $this->missing($o);
            if ($missing) {
                return redirect()->route('results.cohort', $id)->withErrors(['submit' => 'Some marks are missing: '.implode('; ', array_slice($missing, 0, 8)).(count($missing) > 8 ? '; and '.(count($missing) - 8).' more' : '').'. Enter them before you submit.']);
            }
            DB::transaction(function () use ($id, $o) {
                DB::table('offerings')->where('id', $id)->update(['results_submitted_at' => now()]);
                Tasks::close('results_entry', $id);
                Tasks::open('deputy_dean', 'verify_results', $id, 'Verify results: '.$o->name_en.' '.$o->cohort_name, "/results/$id", $o->ptj_id, area: 'results');
                $this->audit->record('results.submitted', ['type' => 'Offering', 'id' => $id]);
            });

            return redirect()->route('results.cohort', $id)->with('status', 'Submitted for verification. The deputy dean now has a task.');
        }

        return redirect()->route('results.cohort', $id)->with('status', $count ? "Saved $count marks." : 'Nothing changed.');
    }

    // FR-122: an integrity case holds that participant's results.
    public function flag(Request $request, int $id)
    {
        $o = $this->offering($request, $id, lecturer: true);
        $d = $request->validate(['enrolment_id' => 'required|integer', 'reason' => 'required|string|min:5|max:1000']);
        abort_unless(DB::table('enrolments')->where('id', $d['enrolment_id'])->where('offering_id', $id)->exists(), 404);
        DB::transaction(function () use ($d, $id, $request) {
            DB::table('integrity_cases')->insert(['offering_id' => $id, 'enrolment_id' => $d['enrolment_id'], 'opened_by' => $request->user()->id, 'reason' => $d['reason'], 'created_at' => now()]);
            DB::table('assessment_results')->where('enrolment_id', $d['enrolment_id'])->where('status', 'provisional')->update(['status' => 'held', 'hold_reason' => $d['reason']]);
            $this->audit->record('integrity.opened', ['type' => 'Enrolment', 'id' => $d['enrolment_id']], reason: $d['reason']); // EV-28
        });

        return redirect()->route('results.cohort', $id)->with('status', 'Integrity case opened. The results of this participant are held and cannot be verified.');
    }

    // FR-125: after verification a change needs a reason and the deputy dean's approval.
    public function requestChange(Request $request, int $resultId)
    {
        $r = DB::table('assessment_results as r')->join('enrolments as e', 'e.id', '=', 'r.enrolment_id')->where('r.id', $resultId)->first(['r.*', 'e.offering_id']) ?? abort(404);
        $o = $this->offering($request, $r->offering_id, lecturer: true);
        abort_unless($r->status === 'verified', 403, 'Only a verified result needs a change request.');
        $comp = DB::table('assessment_components')->find($r->assessment_component_id);
        $d = $request->validate(['new_score' => ['required', 'numeric', 'min:0', 'max:'.$comp->max_mark, 'regex:/^\d+(\.\d{1,2})?$/'], 'reason' => 'required|string|min:5|max:1000']);
        DB::transaction(function () use ($d, $r, $request, $o) {
            $cid = DB::table('result_change_requests')->insertGetId(['assessment_result_id' => $r->id, 'new_score' => $d['new_score'], 'reason' => $d['reason'], 'requested_by' => $request->user()->id, 'created_at' => now()]);
            $this->audit->record('result.change_requested', ['type' => 'AssessmentResult', 'id' => $r->id], before: ['score' => $r->score], after: ['score' => $d['new_score']], reason: $d['reason']);
            Tasks::open('deputy_dean', 'result_change', $cid, 'Approve a result change: '.$o->name_en.' '.$o->cohort_name, "/results/{$o->id}", $o->ptj_id, area: 'results');
        });

        return redirect()->route('results.cohort', $r->offering_id)->with('status', 'Change requested. The deputy dean decides it.');
    }

    // ---- the deputy dean

    public function index(Request $request)
    {
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        $rows = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->when($ids !== null, fn ($b) => $b->whereIn('m.ptj_id', $ids))->whereIn('o.status', ['running', 'ended'])->orderByDesc('o.start_date')->get(['o.id', 'o.cohort_name', 'o.status', 'o.results_submitted_at', 'v.name_en']);
        $stats = DB::table('assessment_results as r')->join('enrolments as e', 'e.id', '=', 'r.enrolment_id')->whereIn('e.offering_id', $rows->pluck('id'))
            ->selectRaw('e.offering_id, r.status, count(*) n')->groupBy('e.offering_id', 'r.status')->get()->groupBy('offering_id');

        return view('results.index', ['rows' => $rows, 'stats' => $stats]);
    }

    public function verifyPage(Request $request, int $id)
    {
        $o = $this->offering($request, $id);
        $comps = Template::components($o->mc_version_id);
        $summary = $comps->map(function ($c) use ($id) {
            $rs = DB::table('assessment_results as r')->join('enrolments as e', 'e.id', '=', 'r.enrolment_id')->where('e.offering_id', $id)->where('r.assessment_component_id', $c->id)->get(['r.score', 'r.status']);
            $scored = $rs->whereNotNull('score');

            return ['c' => $c, 'n' => $rs->count(), 'provisional' => $rs->where('status', 'provisional')->count(), 'held' => $rs->where('status', 'held')->count(), 'verified' => $rs->where('status', 'verified')->count(),
                'pass_rate' => $scored->isEmpty() ? null : round($scored->filter(fn ($r) => (float) $r->score >= (float) $c->pass_mark)->count() / $scored->count() * 100)];
        });

        return view('results.verify', $this->grid($o) + [
            'o' => $o, 'summary' => $summary, 'modules' => DB::table('mc_modules')->where('mc_version_id', $o->mc_version_id)->orderBy('seq')->get(), 'hasWhole' => $comps->whereNull('mc_module_id')->isNotEmpty(),
            'cases' => DB::table('integrity_cases as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')->join('users as u', 'u.id', '=', 'e.user_id')->where('c.offering_id', $id)->where('c.status', 'open')->get(['c.*', 'u.name']),
            'changes' => DB::table('result_change_requests as q')->join('assessment_results as r', 'r.id', '=', 'q.assessment_result_id')->join('enrolments as e', 'e.id', '=', 'r.enrolment_id')->join('users as u', 'u.id', '=', 'e.user_id')->join('assessment_components as c', 'c.id', '=', 'r.assessment_component_id')
                ->where('e.offering_id', $id)->where('q.status', 'requested')->get(['q.*', 'r.score as old_score', 'u.name', 'c.name_en as component']),
        ]);
    }

    // FR-123, FR-124: verify a whole cohort or some modules at once. This creates module achievements and badges only.
    public function verify(Request $request, int $id)
    {
        $o = $this->offering($request, $id);
        abort_unless($o->results_submitted_at, 403, 'The lecturer has not submitted the results for verification yet.');
        $d = $request->validate(['evidence' => 'required|string|min:5|max:500', 'scopes' => 'nullable|array'], ['evidence.required' => 'Say how identity, assessment and result were linked (the evidence).']);
        $r = $this->verifier->verify($o, $request->user(), $d['evidence'], (array) ($d['scopes'] ?? []));
        if (! DB::table('assessment_results as r')->join('enrolments as e', 'e.id', '=', 'r.enrolment_id')->where('e.offering_id', $id)->where('r.status', 'provisional')->exists()) {
            Tasks::close('verify_results', $id);
        }
        foreach (DB::table('enrolments as e')->join('assessment_results as r', 'r.enrolment_id', '=', 'e.id')->where('e.offering_id', $id)->where('r.verification_evidence', $d['evidence'])->where('r.verified_at', '>=', now()->subMinute())->distinct()->pluck('e.user_id') as $uid) {
            $u = User::find($uid);
            $this->notifier->send($u, 'results.verified', ['name' => $u->name, 'title' => $o->name_en.' '.$o->cohort_name], dedupe: "verified-$id-$uid-".now()->timestamp);
        }

        return redirect()->route('results.verify', $id)->with('status', "Verified {$r['verified']} results".($r['held'] ? " ({$r['held']} held results left out)" : '').". Module badges created: {$r['badges']}. Nothing is issued yet: completion is confirmed separately.");
    }

    public function resolveCase(Request $request, int $id, int $case)
    {
        $this->offering($request, $id);
        $d = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        $c = DB::table('integrity_cases')->where('id', $case)->where('offering_id', $id)->where('status', 'open')->first() ?? abort(404);
        DB::transaction(function () use ($c, $d, $request) {
            DB::table('integrity_cases')->where('id', $c->id)->update(['status' => 'closed', 'outcome' => $d['reason'], 'closed_by' => $request->user()->id, 'closed_at' => now()]);
            if (! DB::table('integrity_cases')->where('enrolment_id', $c->enrolment_id)->where('status', 'open')->exists()) {
                DB::table('assessment_results')->where('enrolment_id', $c->enrolment_id)->where('status', 'held')->update(['status' => 'provisional', 'hold_reason' => null]);
            }
            $this->audit->record('integrity.resolved', ['type' => 'Enrolment', 'id' => $c->enrolment_id], reason: $d['reason']);
        });

        return redirect()->route('results.verify', $id)->with('status', 'Case resolved. Held results return to Provisional.');
    }

    public function decideChange(Request $request, int $id, int $change)
    {
        $o = $this->offering($request, $id);
        $d = $request->validate(['decision' => 'required|in:approve,reject', 'note' => 'required|string|min:5|max:500']);
        $q = DB::table('result_change_requests')->where('id', $change)->where('status', 'requested')->first() ?? abort(404);
        abort_if($q->requested_by === $request->user()->id, 403, 'The person who asked for the change cannot approve it.');
        $r = DB::table('assessment_results')->find($q->assessment_result_id);
        abort_unless(DB::table('enrolments')->where('id', $r->enrolment_id)->where('offering_id', $id)->exists(), 404);

        DB::transaction(function () use ($d, $q, $r, $request, $o) {
            DB::table('result_change_requests')->where('id', $q->id)->update(['status' => $d['decision'] === 'approve' ? 'approved' : 'rejected', 'decided_by' => $request->user()->id, 'decision_note' => $d['note'], 'decided_at' => now()]);
            if ($d['decision'] === 'approve') {
                DB::table('assessment_result_revisions')->insert(['assessment_result_id' => $r->id, 'old_score' => $r->score, 'new_score' => $q->new_score, 'reason' => $q->reason, 'requested_by' => $q->requested_by, 'approved_by' => $request->user()->id, 'created_at' => now()]); // the old value is kept
                DB::table('assessment_results')->where('id', $r->id)->update(['score' => $q->new_score, 'updated_at' => now()]);
                $this->verifier->achievements($r->enrolment_id, $o->mc_version_id, $request->user()->id);
                \App\Modules\Achievement\Completion::syncTask($o->id);
                if ($cred = DB::table('credentials')->where('enrolment_id', $r->enrolment_id)->whereIn('status', ['valid', 'corrected'])->first()) {
                    Tasks::open('deputy_dean', 'credential_correction', $cred->id, 'Check the credential after a result change: '.$cred->number, "/credentials/{$cred->id}", $o->ptj_id, area: 'credentials');
                }
            }
            $this->audit->record($d['decision'] === 'approve' ? 'result.change_approved' : 'result.change_rejected', ['type' => 'AssessmentResult', 'id' => $r->id], before: ['score' => $r->score], after: ['score' => $d['decision'] === 'approve' ? $q->new_score : $r->score], reason: $d['note']); // EV-30
            Tasks::close('result_change', $q->id);
        });

        return redirect()->route('results.verify', $id)->with('status', $d['decision'] === 'approve' ? 'Change approved. The earlier value is kept in the history.' : 'Change rejected.');
    }

    // ---- the student (FR-128)

    public function mine(Request $request)
    {
        $rows = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->where('e.user_id', $request->user()->id)
            ->whereIn('e.status', ['enrolled', 'completed', 'not_completed'])
            ->orderByDesc('e.id')
            ->get([
                'e.id', 'e.reference', 'e.status as enrolment_status', 'e.progress_percent', 'e.created_at as registered_at',
                'o.id as offering_id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.delivery_mode', 'o.facilitators', 'o.fee',
                'v.id as version_id', 'v.name_en', 'v.name_ms', 'v.discipline', 'v.planned_slt', 'v.proposed_credit', 'v.proposed_mqf_level', 'v.credential_types',
                'm.code as mc_code', 'm.category as mc_category',
                'p.code as ptj_code', 'p.name as ptj_name',
            ]);

        $data = $rows->map(fn ($e) => $this->formatEnrolmentData($e));

        $totalCredits = $rows->where('enrolment_status', 'completed')->sum(fn ($r) => (float) ($r->proposed_credit ?: 0));
        $totalBadges = DB::table('module_achievements as a')
            ->join('digital_badges as b', 'b.module_achievement_id', '=', 'a.id')
            ->join('enrolments as e', 'e.id', '=', 'a.enrolment_id')
            ->where('e.user_id', $request->user()->id)
            ->count();

        return view('student.results', [
            'data' => $data,
            'summary' => [
                'total_registrations' => $rows->count(),
                'total_verified' => $data->sum(fn ($d) => $d['results']->count()),
                'total_credits' => $totalCredits,
                'total_badges' => $totalBadges,
            ],
        ]);
    }

    public function myDetail(Request $request, int $id)
    {
        $e = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->where('e.id', $id)
            ->where('e.user_id', $request->user()->id)
            ->whereIn('e.status', ['enrolled', 'completed', 'not_completed'])
            ->first([
                'e.id', 'e.reference', 'e.status as enrolment_status', 'e.progress_percent', 'e.created_at as registered_at',
                'o.id as offering_id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.delivery_mode', 'o.facilitators', 'o.fee',
                'v.id as version_id', 'v.name_en', 'v.name_ms', 'v.discipline', 'v.planned_slt', 'v.proposed_credit', 'v.proposed_mqf_level', 'v.credential_types',
                'm.code as mc_code', 'm.category as mc_category',
                'p.code as ptj_code', 'p.name as ptj_name',
            ]) ?? abort(404);

        $d = $this->formatEnrolmentData($e);

        return view('student.result-detail', [
            'd' => $d,
            'e' => $d['e'],
            'overall' => $d['overall'],
            'results' => $d['results'],
            'components' => $d['components'],
            'modules' => $d['modules'],
            'badges' => $d['badges'],
        ]);
    }

    public function gradeScale(): \Closure
    {
        return function (float $score): array {
            return match (true) {
                $score >= 80 => ['grade' => 'A', 'point' => '4.00', 'desc' => 'High Distinction / Cemerlang'],
                $score >= 75 => ['grade' => 'A-', 'point' => '3.70', 'desc' => 'Distinction / Cemerlang'],
                $score >= 70 => ['grade' => 'B+', 'point' => '3.30', 'desc' => 'Credit / Kepujian'],
                $score >= 65 => ['grade' => 'B', 'point' => '3.00', 'desc' => 'Credit / Kepujian'],
                $score >= 60 => ['grade' => 'B-', 'point' => '2.70', 'desc' => 'Good / Baik'],
                $score >= 55 => ['grade' => 'C+', 'point' => '2.30', 'desc' => 'Satisfactory / Memuaskan'],
                $score >= 50 => ['grade' => 'C', 'point' => '2.00', 'desc' => 'Pass / Lulus'],
                $score >= 40 => ['grade' => 'D', 'point' => '1.00', 'desc' => 'Marginal Fail / Gagal'],
                default => ['grade' => 'F', 'point' => '0.00', 'desc' => 'Fail / Gagal'],
            };
        };
    }

    public function formatEnrolmentData(object $e): array
    {
        $gradeScale = $this->gradeScale();

        $components = DB::table('assessment_components as c')
            ->leftJoin('assessment_results as r', function ($j) use ($e) {
                $j->on('r.assessment_component_id', '=', 'c.id')->where('r.enrolment_id', '=', $e->id);
            })
            ->leftJoin('mc_modules as mod', 'mod.id', '=', 'c.mc_module_id')
            ->leftJoin('users as v_user', 'v_user.id', '=', 'r.verified_by')
            ->where('c.mc_version_id', $e->version_id)
            ->orderBy('c.id')
            ->select([
                'c.id as component_id', 'c.name_en as name_en', 'c.weight_percent', 'c.max_mark', 'c.pass_mark', 'c.mc_module_id',
                'mod.seq as module_seq', 'mod.name_en as module_name',
                'r.id as result_id', 'r.score', 'r.status as result_status', 'r.verified_at', 'r.verification_evidence',
                'v_user.name as verified_by_name',
            ])
            ->get()
            ->map(function ($c) {
                $score = $c->score !== null ? (float) $c->score : null;
                $max = (float) ($c->max_mark ?: 100);
                $weight = (float) ($c->weight_percent ?: 0);
                $weightedScore = ($score !== null && $max > 0) ? round(($score / $max) * $weight, 2) : null;
                $passMark = (float) ($c->pass_mark ?: 50);
                $isPass = $score !== null ? ($score >= $passMark) : null;

                $c->score_val = $score;
                $c->weighted_val = $weightedScore;
                $c->is_pass = $isPass;

                return $c;
            });

        $verifiedComps = $components->where('result_status', 'verified');
        $hasAllVerified = $components->isNotEmpty() && $components->every(fn ($c) => $c->result_status === 'verified');
        $weightedTotal = $components->whereNotNull('weighted_val')->sum('weighted_val');

        $overall = null;
        if ($verifiedComps->isNotEmpty()) {
            $calcScore = $hasAllVerified ? $weightedTotal : ($verifiedComps->sum('weight_percent') > 0 ? round(($weightedTotal / $verifiedComps->sum('weight_percent')) * 100, 2) : 0);
            $scale = $gradeScale($calcScore);
            $overall = [
                'total_weighted' => $weightedTotal,
                'scale_score' => $calcScore,
                'grade' => $scale['grade'],
                'point' => $scale['point'],
                'grade_desc' => $scale['desc'],
                'complete' => $hasAllVerified,
                'verified_count' => $verifiedComps->count(),
                'total_count' => $components->count(),
                'latest_verification' => $verifiedComps->sortByDesc('verified_at')->first(),
            ];
        }

        $modules = DB::table('mc_modules as m')
            ->leftJoin('module_achievements as a', function ($j) use ($e) {
                $j->on('a.mc_module_id', '=', 'm.id')->where('a.enrolment_id', '=', $e->id);
            })
            ->leftJoin('digital_badges as b', 'b.module_achievement_id', '=', 'a.id')
            ->where('m.mc_version_id', $e->version_id)
            ->orderBy('m.seq')
            ->select([
                'm.id', 'm.seq', 'm.name_en', 'm.name_ms', 'm.slt_hours', 'm.separately_recognisable',
                'a.id as achievement_id', 'a.achieved_at',
                'b.id as badge_id', 'b.code', 'b.external_id', 'b.external_url', 'b.issued_at as badge_issued_at',
            ])
            ->get();

        $earnedBadges = $modules->whereNotNull('badge_id')->values();

        return [
            'e' => $e,
            'components' => $components,
            'results' => $verifiedComps->values(),
            'modules' => $modules,
            'badges' => $earnedBadges,
            'overall' => $overall,
        ];
    }

    // ---- helpers

    private function grid(object $o): array
    {
        $comps = Template::components($o->mc_version_id);
        $enrol = DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->where('e.offering_id', $o->id)->whereIn('e.status', ['enrolled', 'completed', 'not_completed'])->orderBy('u.name')
            ->get(['e.id', 'e.reference', 'e.progress_percent', 'u.name', 'u.institution_id']);
        $results = DB::table('assessment_results')->whereIn('enrolment_id', $enrol->pluck('id'))->get()->groupBy('enrolment_id')->map(fn ($g) => $g->keyBy('assessment_component_id'));

        return ['comps' => $comps, 'enrol' => $enrol, 'results' => $results, 'openCases' => DB::table('integrity_cases')->where('offering_id', $o->id)->where('status', 'open')->pluck('enrolment_id')->all(), 'keys' => $comps->mapWithKeys(fn ($c) => [$c->id => Template::key($c)])];
    }

    /** Participants and components without a mark, leaving out participants with an open integrity case. */
    private function missing(object $o): array
    {
        $g = $this->grid($o);
        $out = [];
        foreach ($g['enrol'] as $e) {
            if (in_array($e->id, $g['openCases'], true)) {
                continue;
            }
            foreach ($g['comps'] as $c) {
                if (($g['results'][$e->id][$c->id]->score ?? null) === null) {
                    $out[] = "{$e->name}: {$g['keys'][$c->id]}";
                }
            }
        }

        return $out;
    }

    private function offering(Request $request, int $id, bool $lecturer = false): object
    {
        $user = $request->user();
        $o = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('o.id', $id)
            ->first(['o.*', 'v.name_en', 'm.ptj_id', 'm.owner_id']) ?? abort(404);
        if ($lecturer) {
            abort_unless($o->owner_id === $user->id, 403, 'These are the results of another lecturer\'s cohort.');
        } else {
            $ids = $user->scopedPtjIds('deputy_dean');
            abort_unless($ids === null || in_array($o->ptj_id, $ids, true), 403, 'This cohort belongs to another PTj.');
            abort_if($o->owner_id === $user->id, 403, 'You cannot verify the results of your own micro-credential.');
        }

        return $o;
    }
}
