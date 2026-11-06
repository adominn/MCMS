<?php

declare(strict_types=1);

namespace App\Modules\Monitoring;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Core\Tasks;
use App\Modules\Notifications\Notifier;
use App\Modules\Offering\OfferingStatus;
use App\Modules\Quality\Qv;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// LEC-13 / DD-13 dashboard, DD-16 periodic review, DD-17 suspension or termination, and the participant's feedback (FR-mnfeedback).
final class QualityReviewController
{
    public function __construct(private Settings $settings, private AuditRecorder $audit, private Notifier $notifier, private Indicators $indicators, private PeriodicReviews $reviews, private OfferingStatus $offerings) {}

    // ---- dashboard (FR-mndash)

    public function index(Request $request)
    {
        $this->reviews->ensure();
        $mcs = $this->mcs($request);
        $offerings = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->whereIn('v.micro_credential_id', $mcs->pluck('id'))->whereIn('o.status', ['running', 'ended', 'open'])->orderByDesc('o.id')->get(['o.id', 'o.cohort_name', 'o.status', 'v.name_en', 'v.micro_credential_id'])
            ->map(function ($o) {
                $ind = $this->indicators->for($o->id);
                $o->ind = $ind;
                $o->below = collect($ind['rows'])->where('below', true)->count();
                $o->record = DB::table('cqi_records')->where('offering_id', $o->id)->orderByDesc('id')->first();

                return $o;
            })->filter(fn ($o) => $o->ind['participants'] > 0)->values();

        $courses = $offerings->groupBy('micro_credential_id')->map(fn ($items, $mcId) => [
            'id' => $mcId,
            'name' => $items->first()->name_en,
            'count' => $items->count(),
        ])->values();
        $selectedCourse = $request->query('course') ?: $request->query('mc');

        return view('monitoring.index', [
            'offerings' => $offerings,
            'courses' => $courses,
            'selectedCourse' => $selectedCourse ? (int) $selectedCourse : null,
            'owner' => $request->user(),
            'actions' => DB::table('cqi_actions as a')->join('cqi_records as r', 'r.id', '=', 'a.cqi_record_id')->join('offerings as o', 'o.id', '=', 'r.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->whereIn('v.micro_credential_id', $mcs->pluck('id'))->whereIn('a.status', ['not_started', 'in_progress', 'done'])->orderBy('a.due_on')->get(['a.*', 'r.reference', 'v.name_en']),
            'qv' => $mcs->map(fn ($m) => ['name' => $m->name_en, 'standing' => Qv::standing($m->id)])->filter(fn ($x) => $x['standing']),
            'due' => DB::table('periodic_reviews as p')->whereIn('p.micro_credential_id', $mcs->pluck('id'))->whereNull('p.completed_on')->orderBy('p.due_on')->get()->each(fn ($p) => $p->name = $mcs->firstWhere('id', $p->micro_credential_id)->name_en),
            'suspensions' => DB::table('suspensions as s')->whereIn('s.micro_credential_id', $mcs->pluck('id'))->orderByDesc('s.id')->get()->each(fn ($s) => $s->name = $mcs->firstWhere('id', $s->micro_credential_id)->name_en),
        ]);
    }

    public function cohort(Request $request, int $id)
    {
        $mcs = $this->mcs($request);
        $offering = DB::table('offerings as o')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.owner_id')
            ->where('o.id', $id)
            ->whereIn('v.micro_credential_id', $mcs->pluck('id'))
            ->first([
                'o.id', 'o.cohort_name', 'o.status', 'o.start_date', 'o.end_date', 'o.reg_opens_on', 'o.reg_closes_on', 'o.capacity', 'o.fee',
                'o.delivery_mode', 'o.readiness_state',
                'v.id as version_id', 'v.name_en', 'v.name_ms', 'v.version_no', 'v.planned_slt', 'v.proposed_credit',
                'm.id as micro_credential_id', 'm.category', 'm.code as mc_code', 'm.owner_id',
                'p.name as ptj_name', 'p.code as ptj_code', 'u.name as owner_name',
            ]);

        abort_unless($offering, 404, 'Cohort not found or not in your academic scope.');

        $ind = $this->indicators->for($offering->id);
        $offering->ind = $ind;
        $offering->below = collect($ind['rows'])->where('below', true)->count();
        $offering->record = DB::table('cqi_records')->where('offering_id', $offering->id)->orderByDesc('id')->first();

        $actions = $offering->record
            ? DB::table('cqi_actions as a')->join('users as u', 'u.id', '=', 'a.responsible_id')->where('a.cqi_record_id', $offering->record->id)->orderBy('a.id')->get(['a.*', 'u.name as responsible'])
            : collect();

        return view('monitoring.cohort', [
            'o' => $offering,
            'ind' => $ind,
            'record' => $offering->record,
            'actions' => $actions,
            'isLecturer' => $request->user()->hasAnyRole('lecturer') && ! $request->user()->hasAnyRole('deputy_dean'),
            'isOwner' => $offering->owner_id === $request->user()->id,
            'isDean' => $request->user()->hasAnyRole('deputy_dean'),
        ]);
    }

    // ---- feedback (participant)

    // A participant's feedback: a table of the cohorts first, then one page per cohort to rate it.
    private function feedbackRows(int $userId)
    {
        return DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->where('e.user_id', $userId)
            ->where('e.status', 'completed')
            ->orderByDesc('o.end_date')->get(['e.id', 'e.reference', 'o.cohort_name', 'o.start_date', 'o.end_date', 'v.name_en'])->map(function ($e) {
                $e->given = DB::table('feedback_responses')->where('enrolment_id', $e->id)->first();

                return $e;
            });
    }

    public function feedback(Request $request)
    {
        return view('monitoring.feedback', ['rows' => $this->feedbackRows($request->user()->id)]);
    }

    public function feedbackForm(Request $request, int $enrolment)
    {
        $e = $this->feedbackRows($request->user()->id)->firstWhere('id', $enrolment) ?? abort(404);

        return view('monitoring.feedback-form', ['e' => $e, 'groups' => config('monitoring.feedback_groups'), 'required' => array_keys(config('monitoring.feedback_areas')),
            'answers' => $e->given ? (json_decode($e->given->answers, true) ?: []) : []]);
    }

    public function submitFeedback(Request $request, int $enrolment)
    {
        $e = DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->where('e.id', $enrolment)->where('e.user_id', $request->user()->id)->first(['e.*', 'o.end_date']) ?? abort(404);
        abort_unless($e->status === 'completed', 422, 'Feedback opens after you complete the micro-credential.');
        abort_if(DB::table('feedback_responses')->where('enrolment_id', $enrolment)->exists(), 422, 'You already gave feedback for this cohort.');
        $required = array_keys(config('monitoring.feedback_areas'));
        $rules = [];
        foreach (collect(config('monitoring.feedback_groups'))->flatMap(fn ($g) => array_keys($g)) as $k) {
            $rules["rating.$k"] = (in_array($k, $required, true) ? 'required' : 'nullable').'|integer|between:1,5';
        }
        $d = $request->validate($rules + ['comment' => 'nullable|string|max:2000']);
        DB::table('feedback_responses')->insert(['offering_id' => $e->offering_id, 'enrolment_id' => $enrolment, 'answers' => json_encode(array_filter($d['rating'], fn ($v) => $v !== null)), 'comment' => $d['comment'] ?? null, 'submitted_at' => now()]);

        return redirect()->route('feedback')->with('status', 'Thank you. Your feedback is stored without your name on reports.');
    }

    // ---- periodic review (FR-prschedule, FR-proutcome)

    public function reviewOutcome(Request $request, int $id)
    {
        $p = DB::table('periodic_reviews')->find($id) ?? abort(404);
        $m = $this->mcs($request)->firstWhere('id', $p->micro_credential_id) ?? abort(404);
        abort_if($p->completed_on, 422, 'This review is complete.');
        $d = $request->validate(['outcome' => 'required|in:'.implode(',', array_keys(config('monitoring.review_outcome'))), 'note' => 'nullable|string|max:2000']);
        DB::transaction(function () use ($p, $m, $d, $request) {
            DB::table('periodic_reviews')->where('id', $p->id)->update(['outcome' => $d['outcome'], 'note' => $d['note'] ?? null, 'completed_on' => today(), 'decided_by' => $request->user()->id, 'updated_at' => now()]);
            match ($d['outcome']) {
                'minor_cqi' => Tasks::open('lecturer', 'review_cqi', $p->id, 'Prepare a quality review (minor CQI): '.$m->name_en, '/monitoring', userId: $m->owner_id, area: 'offerings'),
                'content_review', 'reapproval' => Tasks::open('lecturer', 'review_change', $p->id, 'Request the change the periodic review asked for: '.$m->name_en, "/micro-credentials/{$m->id}/change", userId: $m->owner_id, area: 'proposals'),
                'suspension' => Tasks::open('deputy_dean', 'review_suspension', $p->id, 'Decide on suspension or termination: '.$m->name_en, "/suspensions?mc={$m->id}", $m->ptj_id, area: 'offerings'),
                default => null,
            };
            DB::table('periodic_reviews')->insert(['micro_credential_id' => $m->id, 'due_on' => today()->addMonths((int) $this->settings->get('review.interval_months'))->toDateString(), 'created_at' => now(), 'updated_at' => now()]); // the next review
            $this->audit->record('review.recorded', ['type' => 'PeriodicReview', 'id' => $p->id], after: ['outcome' => $d['outcome']], reason: $d['note'] ?? null);
        });

        return back()->with('status', 'Review outcome recorded: '.config('monitoring.review_outcome')[$d['outcome']].'.');
    }

    // ---- suspension or termination (FR-prsuspend, FR-prnotify, FR-prkeep)

    public function suspensions(Request $request)
    {
        $this->authority($request);
        $mc = (int) $request->query('mc');
        $m = $mc ? ($this->mcs($request)->firstWhere('id', $mc)) : null;

        return view('monitoring.suspensions', ['mcs' => $this->mcs($request), 'selected' => $m, 'participants' => $m ? $this->participants($m->id, null) : collect(), 'transition' => config('monitoring.transition'),
            'rows' => DB::table('suspensions as s')->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 's.micro_credential_id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = s.micro_credential_id)'))->whereIn('s.micro_credential_id', $this->mcs($request)->pluck('id'))->orderByDesc('s.id')->get(['s.*', 'v.name_en'])]);
    }

    public function decide(Request $request)
    {
        $this->authority($request);
        $d = $request->validate(['micro_credential_id' => 'required|integer', 'offering_id' => 'nullable|integer', 'kind' => 'required|in:suspension,termination', 'reason' => 'required|string|max:2000', 'effective_from' => 'required|date',
            'transition_type' => 'nullable|in:'.implode(',', array_keys(config('monitoring.transition'))), 'transition_plan' => 'nullable|string|max:2000']);
        $m = $this->mcs($request)->firstWhere('id', (int) $d['micro_credential_id']) ?? abort(404);
        $plan = trim((string) ($d['transition_plan'] ?? ''));
        if ($d['kind'] === 'termination' && ($plan === '' || empty($d['transition_type']))) {
            throw ValidationException::withMessages(['transition_plan' => 'A transition plan is required before termination: a completion window, a mapping to a new version, or an equivalence of earlier achievement.']);
        }
        $offeringId = ! empty($d['offering_id']) ? (int) $d['offering_id'] : null;
        $ids = DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('v.micro_credential_id', $m->id)->whereNotIn('o.status', ['ended', 'cancelled'])->when($offeringId, fn ($q) => $q->where('o.id', $offeringId))->pluck('o.id')->all();
        abort_if($offeringId && $ids === [], 422, 'This offering has already ended or been cancelled.');
        $sid = DB::transaction(function () use ($d, $m, $ids, $offeringId, $plan, $request) {
            foreach ($ids as $oid) {
                $col = $d['kind'] === 'termination' ? ['status' => 'cancelled', 'cancel_reason' => $d['reason']] : ['status' => 'held', 'held_reason' => $d['reason']];
                DB::table('offerings')->where('id', $oid)->update($col + ['updated_at' => now()]);
            }
            $sid = DB::table('suspensions')->insertGetId(['micro_credential_id' => $m->id, 'offering_id' => $offeringId, 'kind' => $d['kind'], 'reason' => $d['reason'], 'effective_from' => $d['effective_from'], 'transition_type' => $d['transition_type'] ?? null, 'transition_plan' => $plan ?: null,
                'offering_ids' => json_encode($ids), 'authority' => (string) $this->settings->get('approval.route.suspension'), 'decided_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            $n = 0;
            foreach ($this->participants($m->id, $ids) as $p) { // mandatory notice (FR-prnotify)
                $u = User::find($p->user_id);
                $this->notifier->send($u, 'suspension.notice', ['name' => $u->name, 'title' => $m->name_en.' '.$p->cohort_name, 'kind' => $d['kind'] === 'termination' ? 'terminated' : 'suspended', 'date' => \Carbon\Carbon::parse($d['effective_from'])->format('d/m/Y'),
                    'reason' => $d['reason'], 'plan' => $plan ?: 'The faculty will contact you with your options.'], dedupe: "suspension-$sid-{$u->id}");
                $n++;
            }
            DB::table('suspensions')->where('id', $sid)->update(['participants_notified' => $n]);
            Tasks::close('review_suspension', (int) DB::table('periodic_reviews')->where('micro_credential_id', $m->id)->where('outcome', 'suspension')->orderByDesc('id')->value('id'));
            $this->audit->record('suspension.decided', ['type' => 'Suspension', 'id' => $sid], after: ['kind' => $d['kind'], 'offerings' => $ids, 'notified' => $n], reason: $d['reason']); // EV-59

            return $sid;
        });

        return redirect()->route('suspensions')->with('status', ucfirst($d['kind']).' recorded for '.count($ids).' offering(s). The participants were notified. Credentials and credit already earned stay valid.');
    }

    public function lift(Request $request, int $id)
    {
        $this->authority($request);
        $s = DB::table('suspensions')->find($id) ?? abort(404);
        $this->mcs($request)->firstWhere('id', $s->micro_credential_id) ?? abort(404);
        abort_unless($s->kind === 'suspension' && ! $s->lifted_at, 422, 'Only an open suspension can be lifted.');
        DB::transaction(function () use ($s, $request) {
            DB::table('suspensions')->where('id', $s->id)->update(['lifted_at' => now(), 'updated_at' => now()]);
            DB::table('offerings')->whereIn('id', json_decode($s->offering_ids, true))->where('status', 'held')->update(['status' => 'not_ready', 'held_reason' => null, 'updated_at' => now()]);
            $this->audit->record('suspension.lifted', ['type' => 'Suspension', 'id' => $s->id], actorId: $request->user()->id);
        });
        $this->offerings->refresh();

        return back()->with('status', 'Suspension lifted. The offerings return to their readiness check.');
    }

    public function printSuspension(Request $request, int $id)
    {
        $this->authority($request);

        $s = DB::table('suspensions')->find($id);
        if ($s) {
            $mcId = $s->micro_credential_id;
        } else {
            $mcId = $id;
            $s = DB::table('suspensions')->where('micro_credential_id', $mcId)->orderByDesc('id')->first();
        }

        $m = DB::table('micro_credentials as m')
            ->join('ptjs as p', 'p.id', '=', 'm.ptj_id')
            ->where('m.id', $mcId)->first(['m.*', 'p.name as ptj_name', 'p.code as ptj_code']) ?? abort(404);

        $v = DB::table('mc_versions')->where('micro_credential_id', $m->id)->orderByDesc('version_no')->first();

        $ids = $request->user()->scopedPtjIds('deputy_dean');
        abort_unless($ids === null || in_array($m->ptj_id, $ids, true), 403, 'This micro-credential belongs to another PTj.');

        $cohorts = DB::table('offerings as o')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('v.micro_credential_id', $m->id)
            ->whereIn('o.status', ['open', 'running', 'published', 'held', 'cancelled'])
            ->get(['o.id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.status']);

        $cohortData = $cohorts->map(function ($c) {
            $cnt = DB::table('enrolments')->where('offering_id', $c->id)->whereNotIn('status', ['withdrawn', 'cancelled'])->count();

            return (object) [
                'name' => $c->cohort_name,
                'start' => $c->start_date ? \Carbon\Carbon::parse($c->start_date)->format('d M Y') : '–',
                'end' => $c->end_date ? \Carbon\Carbon::parse($c->end_date)->format('d M Y') : '–',
                'status' => ucfirst($c->status),
                'participants' => $cnt,
            ];
        });

        $dean = DB::table('users')->where('id', $s ? $s->decided_by : $request->user()->id)->value('name') ?: $request->user()->name;

        return view('monitoring.suspension-notice', [
            's' => $s,
            'm' => $m,
            'v' => $v,
            'cohorts' => $cohortData,
            'dean' => $dean,
        ]);
    }

    // ---- shared

    private function participants(int $mc, ?array $offeringIds)
    {
        return DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('users as u', 'u.id', '=', 'e.user_id')->where('v.micro_credential_id', $mc)->whereIn('e.status', ['enrolled', 'pending_payment', 'registered'])
            ->when($offeringIds !== null, fn ($q) => $q->whereIn('o.id', $offeringIds), fn ($q) => $q->whereNotIn('o.status', ['ended', 'cancelled']))->get(['e.user_id', 'u.name', 'o.cohort_name', 'e.status']);
    }

    private function authority(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole((string) $this->settings->get('approval.route.suspension')), 403, 'The role that decides suspension or termination is set in the settings.');
    }

    /** The micro-credentials the user may see: their own as a lecturer, their faculty's as a deputy dean. */
    private function mcs(Request $request)
    {
        $u = $request->user();
        $ids = $u->hasAnyRole('deputy_dean') ? $u->scopedPtjIds('deputy_dean') : null;

        return DB::table('micro_credentials as m')->join('mc_versions as v', fn ($j) => $j->on('v.micro_credential_id', '=', 'm.id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = m.id)'))
            ->when($u->hasAnyRole('deputy_dean'), fn ($q) => $ids === null ? $q : $q->whereIn('m.ptj_id', $ids), fn ($q) => $q->where('m.owner_id', $u->id))->whereIn('v.status', ['approved', 'superseded'])->get(['m.id', 'm.owner_id', 'm.ptj_id', 'm.category', 'v.name_en']);
    }
}
