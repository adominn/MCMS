<?php

declare(strict_types=1);

namespace App\Modules\Approval;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Core\Tasks;
use App\Modules\Curriculum\ProposalChecker;
use App\Modules\Curriculum\ProposalSummary;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// DD-02 proposals and approval, DD-03 review, DD-04 Senate decision, DD-05 decision pack (MD-03).
final class ApprovalController
{
    public const STATUS = ['draft' => 'Draft', 'submitted' => 'Submitted', 'in_review' => 'In review', 'returned' => 'Returned for amendment', 'endorsed' => 'Endorsed', 'approved' => 'Approved', 'not_approved' => 'Not approved', 'superseded' => 'Superseded'];

    public function __construct(private Settings $settings, private AuditRecorder $audit, private Notifier $notifier, private ProposalChecker $checker, private \App\Modules\Offering\OfferingStatus $offerings) {}

    public function index(Request $request)
    {
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        $status = array_key_exists((string) $request->query('status'), self::STATUS) ? $request->query('status') : null;
        $base = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->join('users as u', 'u.id', '=', 'm.owner_id')
            ->where('v.status', '!=', 'draft')->when($ids !== null, fn ($b) => $b->whereIn('m.ptj_id', $ids));

        return view('approval.index', [
            'rows' => (clone $base)->when($status, fn ($b) => $b->where('v.status', $status))->orderBy('v.submitted_at')->get(['v.id', 'v.name_en', 'v.status', 'v.submitted_at', 'm.category', 'm.category_status', 'u.name as owner']),
            'counts' => (clone $base)->selectRaw('v.status, count(*) n')->groupBy('v.status')->pluck('n', 'v.status'),
            'status' => $status, 'labels' => self::STATUS,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $v = $this->version($request, $id, act: false);
        $assign = DB::table('review_assignments as a')->join('users as u', 'u.id', '=', 'a.reviewer_id')->leftJoin('reviews as r', 'r.review_assignment_id', '=', 'a.id')
            ->where('a.mc_version_id', $id)->orderBy('a.id')->get(['a.*', 'u.name as reviewer', 'r.ratings', 'r.recommendation', 'r.summary']);
        $comments = DB::table('review_comments as c')->join('users as u', 'u.id', '=', 'c.author_id')->where('c.mc_version_id', $id)->orderBy('c.id')->get(['c.*', 'u.name as author']);
        $assigned = $assign->where('status', '!=', 'declined')->pluck('reviewer_id');

        return view('approval.show', ProposalSummary::load($id) + [
            'labels' => self::STATUS, 'assign' => $assign, 'comments' => $comments, 'checks' => $this->checker->check($id),
            'decisions' => DB::table('approval_decisions as d')->join('users as u', 'u.id', '=', 'd.decided_by')->where('d.mc_version_id', $id)->orderBy('d.id')->get(['d.*', 'u.name as by']),
            'lecturers' => $this->candidates('lecturer', $v->owner_id, $assigned), 'experts' => $this->candidates('industry_expert', $v->owner_id, $assigned),
            'blocking' => $comments->where('blocking', true)->where('status', '!=', 'resolved')->count(),
            'submittedReviews' => $assign->where('kind', 'reviewer')->where('status', 'submitted')->count(),
            'items' => config('proposals.review_items'),
        ]);
    }

    // FR-60: one or more reviewers, with a due date and the scope of review. Nobody reviews their own proposal (FR-14).
    public function assign(Request $request, int $id)
    {
        $v = $this->version($request, $id);
        abort_unless(in_array($v->status, ['submitted', 'in_review'], true), 403, 'Reviewers can be assigned to a submitted proposal.');
        $request->merge(['reviewers' => array_map('intval', (array) $request->input('reviewers'))]);
        $d = $request->validate(['reviewers' => 'required|array|min:1', 'reviewers.*' => 'integer|exists:users,id', 'due_on' => 'required|date|after_or_equal:today', 'scope' => 'required|string|min:5|max:500']);

        DB::transaction(function () use ($d, $v, $id, $request) {
            foreach (array_unique($d['reviewers']) as $uid) {
                $u = User::findOrFail($uid);
                $kind = $u->hasAnyRole('industry_expert') && ! $u->hasAnyRole('lecturer') ? 'expert' : 'reviewer';
                abort_if($uid === $v->owner_id, 422, 'A lecturer cannot review their own proposal.');
                abort_unless($u->status === 'active' && $u->hasAnyRole('lecturer', 'industry_expert'), 422, 'This person cannot be a reviewer.');
                if (DB::table('review_assignments')->where(['mc_version_id' => $id, 'reviewer_id' => $uid])->where('status', '!=', 'declined')->exists()) {
                    continue;
                }
                DB::table('review_assignments')->where(['mc_version_id' => $id, 'reviewer_id' => $uid, 'status' => 'declined'])->delete();
                $aid = DB::table('review_assignments')->insertGetId(['mc_version_id' => $id, 'reviewer_id' => $uid, 'kind' => $kind, 'assigned_by' => $request->user()->id, 'due_on' => $d['due_on'], 'scope' => $d['scope'], 'created_at' => now(), 'updated_at' => now()]);
                $this->audit->record('review.assigned', ['type' => 'McVersion', 'id' => $id], after: ['reviewer' => $u->name, 'kind' => $kind, 'due_on' => $d['due_on']], reason: $d['scope']); // EV-09
                $this->notifier->send($u, 'reviewer.assigned', ['name' => $u->name, 'title' => $v->name_en, 'due' => $d['due_on']], dedupe: "assignment-$aid");
                Tasks::open($u->hasAnyRole('lecturer') ? 'lecturer' : 'industry_expert', 'review', $aid, ($kind === 'expert' ? 'Comment on: ' : 'Review: ').$v->name_en, "/reviews/$aid", userId: $uid);
            }
            if ($v->status === 'submitted') {
                DB::table('mc_versions')->where('id', $id)->update(['status' => 'in_review', 'updated_at' => now()]);
            }
            Tasks::close('assign_reviewers', $id);
            if (! DB::table('tasks')->where('kind', 'decide')->where('ref_id', $id)->whereNull('completed_at')->exists()) {
                Tasks::open('deputy_dean', 'decide', $id, 'Decide on proposal: '.$v->name_en, "/approvals/$id", $v->ptj_id);
            }
        });

        return redirect()->route('approvals.show', $id)->with('status', 'Reviewers assigned.');
    }

    // FR-63: a blocking comment is resolved only after the proposer has answered it.
    public function resolve(Request $request, int $id, int $comment)
    {
        $this->version($request, $id);
        $d = $request->validate(['reason' => 'required|string|min:5|max:500']);
        $c = DB::table('review_comments')->where('id', $comment)->where('mc_version_id', $id)->first() ?? abort(404);
        abort_unless($c->status === 'answered', 422, 'Only an answered comment can be resolved.');
        DB::table('review_comments')->where('id', $comment)->update(['status' => 'resolved', 'resolve_reason' => $d['reason'], 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);
        $this->audit->record('comment.resolved', ['type' => 'McVersion', 'id' => $id], context: ['comment' => $comment], reason: $d['reason']);

        return redirect()->route('approvals.show', $id)->with('status', 'Comment resolved.');
    }

    // FR-64, FR-66: endorsement verifies the category and records the second stage.
    public function endorse(Request $request, int $id)
    {
        $v = $this->version($request, $id);
        abort_unless($request->user()->hasAnyRole($this->route('approval.route.endorsement')), 403, 'The endorsing role is set in the settings.');
        abort_unless($v->status === 'in_review', 403, 'Only a proposal in review can be endorsed.');
        $request->validate(['category_verified' => 'accepted']);
        $err = [];
        if (DB::table('review_comments')->where('mc_version_id', $id)->where('blocking', true)->where('status', '!=', 'resolved')->exists()) {
            $err[] = 'A blocking comment is not resolved yet.';
        }
        if (! DB::table('review_assignments')->where('mc_version_id', $id)->where('kind', 'reviewer')->where('status', 'submitted')->exists()) {
            $err[] = 'No academic review has been submitted yet.';
        }
        if ($err) {
            return redirect()->route('approvals.show', $id)->withErrors(['endorse' => implode(' ', $err)]);
        }

        DB::transaction(function () use ($id, $v, $request) {
            DB::table('approval_decisions')->insert(['mc_version_id' => $id, 'stage' => 'endorsement', 'decision' => 'endorsed', 'decided_by' => $request->user()->id, 'decided_role' => 'deputy_dean', 'verified_category' => $v->category, 'comments' => $request->input('comments'), 'created_at' => now()]);
            DB::table('mc_versions')->where('id', $id)->update(['status' => 'endorsed', 'updated_at' => now()]);
            DB::table('micro_credentials')->where('id', $v->micro_credential_id)->update(['category_status' => 'verified', 'category_confirmed_by' => $request->user()->id, 'category_confirmed_at' => now()]);
            $this->audit->record('proposal.endorsed', ['type' => 'McVersion', 'id' => $id], before: ['status' => 'in_review'], after: ['status' => 'endorsed', 'verified_category' => $v->category]); // EV-12
            Tasks::close('decide', $id);
            Tasks::open('deputy_dean', 'senate', $id, 'Record Senate decision: '.$v->name_en, "/approvals/$id/senate", $v->ptj_id);
            $this->notifier->send(User::find($v->owner_id), 'proposal.endorsed', ['name' => User::find($v->owner_id)->name, 'title' => $v->name_en], dedupe: "endorsed-$id");
        });

        return redirect()->route('approvals.show', $id)->with('status', 'Endorsed. The category is verified. A task to record the Senate decision is created.');
    }

    public function sendBack(Request $request, int $id, string $how)
    {
        $v = $this->version($request, $id);
        abort_unless(in_array($how, ['return', 'reject'], true), 404);
        abort_unless(in_array($v->status, ['in_review', 'submitted'], true), 403, 'Only a proposal in review can be returned or refused.');
        $d = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        $return = $how === 'return';

        DB::transaction(function () use ($id, $v, $d, $request, $return) {
            DB::table('approval_decisions')->insert(['mc_version_id' => $id, 'stage' => 'endorsement', 'decision' => $return ? 'returned' : 'not_supported', 'decided_by' => $request->user()->id, 'decided_role' => 'deputy_dean', 'comments' => $d['reason'], 'created_at' => now()]);
            DB::table('mc_versions')->where('id', $id)->update(['status' => $return ? 'returned' : 'not_approved', 'updated_at' => now()]); // a returned version unlocks
            $this->audit->record($return ? 'proposal.returned' : 'proposal.not_supported', ['type' => 'McVersion', 'id' => $id], before: ['status' => $v->status], after: ['status' => $return ? 'returned' : 'not_approved'], reason: $d['reason']); // EV-11, EV-13
            Tasks::close('assign_reviewers', $id);
            Tasks::close('decide', $id);
            if ($v->supersedes_id && ! $return) {
                $this->offerings->releaseForChange($v->micro_credential_id, 'The change was not supported.');
            }
            $owner = User::find($v->owner_id);
            $this->notifier->send($owner, $return ? 'proposal.returned' : 'decision.made', $return ? ['name' => $owner->name, 'title' => $v->name_en, 'reason' => $d['reason']] : ['name' => $owner->name, 'item' => $v->name_en, 'decision' => 'Not supported: '.$d['reason']], dedupe: "sendback-$id-".now()->timestamp);
        });

        return redirect()->route('approvals.show', $id)->with('status', $return ? 'Returned for amendment. The proposal is unlocked for its lecturer.' : 'Recorded as not supported. The cycle ends.');
    }

    public function pack(Request $request, int $id)
    {
        $this->version($request, $id, act: false);
        $assign = DB::table('review_assignments as a')->join('users as u', 'u.id', '=', 'a.reviewer_id')->leftJoin('reviews as r', 'r.review_assignment_id', '=', 'a.id')->where('a.mc_version_id', $id)->where('a.status', '!=', 'declined')
            ->get(['a.*', 'u.name as reviewer', 'r.recommendation', 'r.summary']);

        return view('approval.pack', ProposalSummary::load($id) + [
            'assign' => $assign, 'checks' => $this->checker->check($id), 'labels' => self::STATUS,
            'comments' => DB::table('review_comments')->where('mc_version_id', $id)->orderBy('id')->get(),
            'endorsement' => DB::table('approval_decisions as d')->join('users as u', 'u.id', '=', 'd.decided_by')->where('d.mc_version_id', $id)->where('d.stage', 'endorsement')->where('d.decision', 'endorsed')->orderByDesc('d.id')->first(['d.*', 'u.name as by']),
        ]);
    }

    public function senateForm(Request $request, int $id)
    {
        $v = $this->version($request, $id, act: false);
        $e = DB::table('approval_decisions')->where('mc_version_id', $id)->where('decision', 'endorsed')->orderByDesc('id')->first();

        return view('approval.senate', ['v' => $v, 'endorsedOn' => $e?->created_at, 'labels' => self::STATUS]);
    }

    // D-11: the Senate does not use MCMS; the deputy dean records its decision with the meeting reference.
    public function senate(Request $request, int $id)
    {
        $v = $this->version($request, $id);
        abort_unless($request->user()->hasAnyRole($this->route('approval.route.senate')), 403, 'The role that records the decision is set in the settings.');
        abort_unless($v->status === 'endorsed', 403, 'The Senate decision is recorded after endorsement.');
        $endorsed = DB::table('approval_decisions')->where('mc_version_id', $id)->where('decision', 'endorsed')->orderByDesc('id')->value('created_at');
        $d = $request->validate([
            'meeting_ref' => 'required|string|max:60', 'meeting_date' => 'required|date|before_or_equal:today|after_or_equal:'.\Carbon\Carbon::parse($endorsed)->toDateString(),
            'decision' => 'required|in:approved,approved_conditions,not_approved,returned,deferred', 'conditions' => 'nullable|required_if:decision,approved_conditions|string|max:2000', 'comments' => 'required|string|min:5|max:2000',
            'transition_rule' => [\Illuminate\Validation\Rule::requiredIf($v->supersedes_id && in_array($request->input('decision'), ['approved', 'approved_conditions'], true)), 'nullable', 'in:finish_old,move_new,equivalence'],
            'transition_note' => 'nullable|required_if:transition_rule,equivalence|string|max:1000',
        ], ['meeting_date.after_or_equal' => 'The meeting date cannot be before the endorsement.', 'transition_rule.required' => 'Record what happens to participants enrolled on the old version.']);
        $status = ['approved' => 'approved', 'approved_conditions' => 'approved', 'not_approved' => 'not_approved', 'returned' => 'returned', 'deferred' => 'endorsed'][$d['decision']];

        DB::transaction(function () use ($id, $v, $d, $request, $status) {
            $decisionId = DB::table('approval_decisions')->insertGetId(['mc_version_id' => $id, 'stage' => 'senate', 'decision' => $d['decision'], 'decided_by' => $request->user()->id, 'decided_role' => 'deputy_dean', 'meeting_ref' => $d['meeting_ref'], 'meeting_date' => $d['meeting_date'], 'comments' => $d['comments'], 'conditions' => $d['conditions'] ?? null, 'created_at' => now()]);
            if ($d['decision'] !== 'deferred') {
                DB::table('mc_versions')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
                Tasks::close('senate', $id);
            }
            if ($status === 'approved') {
                DB::table('micro_credentials')->where('id', $v->micro_credential_id)->update(['category_status' => 'confirmed', 'updated_at' => now()]); // the category is fixed by approval (FR-31)
                DB::table('mc_versions')->where('id', $id)->update(['effective_date' => today()]);
                if ($v->supersedes_id) {
                    DB::table('version_transition_rules')->insert(['mc_version_id' => $id, 'from_version_id' => $v->supersedes_id, 'rule' => $d['transition_rule'], 'approval_decision_id' => $decisionId, 'note' => $d['transition_note'] ?? null, 'created_at' => now()]);
                    DB::table('mc_versions')->where('id', $v->supersedes_id)->update(['status' => 'superseded', 'updated_at' => now()]);
                    DB::table('transfer_lines')->where('mc_version_id', $v->supersedes_id)->update(['reevaluate' => true]); // an earlier equivalence is reviewed before reuse (FR-ctversion)
                    \App\Modules\Pathways\Plans::flagMicroCredential((int) $v->micro_credential_id, 'A material change to the micro-credential was approved: review the mapping.'); // EV-55
                    DB::table('change_requests')->where('to_version_id', $id)->update(['status' => 'approved']);
                    $this->audit->record('version.superseded', ['type' => 'McVersion', 'id' => $v->supersedes_id], after: ['by_version' => $id, 'transition_rule' => $d['transition_rule']], reason: $d['transition_note'] ?? null); // EV-15, EV-16
                    // EV-16: participants on the old version are rebound if the rule says so, and told of the change, the date and the rule (FR-80, FR-81).
                    $affected = DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->where('o.mc_version_id', $v->supersedes_id)
                        ->whereIn('e.status', ['registered', 'pending_payment', 'enrolled'])->get(['e.id', 'e.user_id', 'o.cohort_name']);
                    foreach ($affected as $e) {
                        if ($d['transition_rule'] === 'move_new') {
                            DB::table('enrolments')->where('id', $e->id)->update(['mc_version_id' => $id, 'updated_at' => now()]);
                        }
                        $pu = User::find($e->user_id);
                        $this->notifier->send($pu, 'version.changed', ['name' => $pu->name, 'title' => $v->name_en.' '.$e->cohort_name, 'version' => $v->version_no, 'date' => today()->format('d/m/Y'), 'rule' => config('proposals.transition_rules')[$d['transition_rule']]], dedupe: "version-$id-{$e->id}");
                    }
                    $this->audit->record('transition.applied', ['type' => 'McVersion', 'id' => $id], after: ['rule' => $d['transition_rule'], 'participants' => $affected->count()]);
                }
            }
            if ($v->supersedes_id && in_array($status, ['approved', 'not_approved'], true)) {
                $this->offerings->releaseForChange($v->micro_credential_id, 'The change was decided.');
            }
            $this->audit->record('senate.decision_recorded', ['type' => 'McVersion', 'id' => $id], before: ['status' => $v->status], after: ['status' => $status, 'decision' => $d['decision'], 'meeting' => $d['meeting_ref']], reason: $d['comments']); // EV-14
            $owner = User::find($v->owner_id);
            $this->notifier->send($owner, 'decision.made', ['name' => $owner->name, 'item' => $v->name_en, 'decision' => str_replace('_', ' ', $d['decision']).' (Senate '.$d['meeting_ref'].')'], dedupe: 'senate-'.$id.'-'.now()->timestamp);
        });

        return redirect()->route('approvals.show', $id)->with('status', 'Senate decision recorded. No credit, Quality Verification or transfer status is set by it.');
    }

    // ---- helpers

    private function route(string $key): string
    {
        return (string) $this->settings->get($key);
    }

    /** The version, with a scope check and the rule that nobody decides their own proposal (FR-14). */
    private function version(Request $request, int $id, bool $act = true): object
    {
        $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $id)
            ->first(['v.*', 'm.category', 'm.ptj_id', 'm.owner_id']) ?? abort(404);
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        abort_unless($ids === null || in_array($v->ptj_id, $ids, true), 403, 'This proposal belongs to another PTj.');
        abort_if($act && $v->owner_id === $request->user()->id, 403, 'You cannot decide on your own proposal.');

        return $v;
    }

    private function candidates(string $role, int $ownerId, $already)
    {
        return User::where('status', 'active')->where('id', '!=', $ownerId)->whereNotIn('id', $already)
            ->whereHas('roleAssignments', fn ($q) => $q->where('role', $role))->orderBy('name')->get()->filter(fn ($u) => $u->hasAnyRole($role));
    }
}
