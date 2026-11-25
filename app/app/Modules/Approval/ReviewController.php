<?php

declare(strict_types=1);

namespace App\Modules\Approval;

use App\Models\User;
use App\Modules\Core\Tasks;
use App\Modules\Curriculum\ProposalSummary;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// LEC-07 reviews for colleagues, LEC-08 review a proposal, EXT-01/EXT-02 industry expert comments (MD-03).
final class ReviewController
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier) {}

    public function index(Request $request)
    {
        // Keep one latest assignment row per proposal on the main list; prior superseded rounds are shown inside the review page
        $rows = DB::table('review_assignments as a')
            ->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')
            ->where('a.reviewer_id', $request->user()->id)
            ->orderByDesc('a.id')
            ->get(['a.id', 'a.mc_version_id', 'a.kind', 'a.status', 'a.due_on', 'v.name_en'])
            ->unique('mc_version_id')
            ->sortBy('due_on')
            ->values();

        $facilitatorInvites = $request->user()->hasAnyRole('lecturer')
            ? DB::table('offering_facilitators as ofac')
                ->join('offerings as o', 'o.id', '=', 'ofac.offering_id')
                ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
                ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->leftJoin('users as inv', 'inv.id', '=', 'ofac.invited_by')
                ->leftJoin('ptjs as p', 'p.id', '=', 'm.ptj_id')
                ->where('ofac.user_id', $request->user()->id)
                ->orderByDesc('ofac.created_at')
                ->get([
                    'ofac.id', 'ofac.offering_id', 'ofac.status', 'ofac.created_at', 'ofac.responded_at', 'ofac.decline_reason',
                    'o.cohort_name', 'o.start_date', 'o.end_date', 'v.name_en', 'm.category', 'p.code as ptj_code',
                    'inv.name as invited_by_name',
                ])
            : collect();

        $activeTab = $request->query('tab') === 'facilitator' && $request->user()->hasAnyRole('lecturer')
            ? 'facilitator'
            : 'reviews';

        return view('reviews.index', [
            'rows' => $rows,
            'facilitatorInvites' => $facilitatorInvites,
            'activeTab' => $activeTab,
        ]);
    }

    public function respondFacilitator(Request $request, int $invitation)
    {
        $inv = DB::table('offering_facilitators as ofac')
            ->join('offerings as o', 'o.id', '=', 'ofac.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('ofac.id', $invitation)
            ->where('ofac.user_id', $request->user()->id)
            ->first(['ofac.*', 'o.cohort_name', 'o.facilitators', 'v.name_en', 'm.owner_id']) ?? abort(404);

        abort_unless($inv->status === 'invited', 422, 'You have already responded to this facilitator invitation.');

        $d = $request->validate([
            'decision' => 'required|in:accept,decline',
            'decline_reason' => 'nullable|string|max:500',
        ]);

        $accepted = $d['decision'] === 'accept';

        DB::transaction(function () use ($inv, $d, $accepted, $request) {
            DB::table('offering_facilitators')->where('id', $inv->id)->update([
                'status' => $accepted ? 'accepted' : 'declined',
                'responded_at' => now(),
                'decline_reason' => $accepted ? null : (trim((string) ($d['decline_reason'] ?? '')) ?: null),
            ]);

            if ($accepted) {
                $cur = trim((string) ($inv->facilitators ?? ''));
                $myName = $request->user()->name;
                if ($cur === '') {
                    $next = $myName;
                } elseif (! str_contains($cur, $myName)) {
                    $next = $cur.', '.$myName;
                } else {
                    $next = $cur;
                }
                DB::table('offerings')->where('id', $inv->offering_id)->update(['facilitators' => $next, 'updated_at' => now()]);
            }

            $notifyId = $inv->invited_by ?: $inv->owner_id;
            if ($notifyId && $notifyId !== $request->user()->id) {
                DB::table('notifications')->insert([
                    'user_id' => $notifyId,
                    'channel' => 'in_system',
                    'template_key' => $accepted ? 'facilitator.accepted' : 'facilitator.declined',
                    'subject' => ($accepted ? 'Facilitator accepted: ' : 'Facilitator declined: ').$inv->name_en.' '.$inv->cohort_name,
                    'body' => $request->user()->name.($accepted ? ' accepted your invitation to facilitate ' : ' declined your invitation to facilitate ').$inv->name_en.' ('.$inv->cohort_name.').',
                    'link' => '/offerings/'.$inv->offering_id,
                    'created_at' => now(),
                ]);
            }

            $this->audit->record(
                $accepted ? 'facilitator.accepted' : 'facilitator.declined',
                ['type' => 'Offering', 'id' => $inv->offering_id],
                after: ['facilitator_id' => $request->user()->id, 'status' => $accepted ? 'accepted' : 'declined'],
                reason: $d['decline_reason'] ?? null
            );
        });

        return redirect()->route('reviews', ['tab' => 'facilitator'])->with('status', $accepted
            ? __('Invitation accepted. This cohort is now available under Cohorts and results.')
            : __('Invitation declined. The course coordinator has been notified.'));
    }

    public function show(Request $request, int $assignment)
    {
        $a = $this->mine($request, $assignment);
        $data = ['a' => $a, 'items' => config('proposals.review_items'), 'status' => $a->status];
        $data['priorReviews'] = DB::table('review_assignments as ra')
            ->join('reviews as r', 'r.review_assignment_id', '=', 'ra.id')
            ->where('ra.mc_version_id', $a->mc_version_id)
            ->where('ra.reviewer_id', $request->user()->id)
            ->where('ra.id', '!=', $a->id)
            ->orderBy('ra.id')
            ->get(['ra.id', 'ra.status', 'ra.due_on', 'ra.scope', 'r.recommendation', 'r.summary', 'r.ratings', 'r.submitted_at']);

        if (in_array($a->status, ['in_progress', 'submitted', 'superseded'], true)) { // the proposal stays closed until the conflict-of-interest answer is given
            $data += ProposalSummary::load($a->mc_version_id) + [
                'review' => DB::table('reviews')->where('review_assignment_id', $a->id)->first(),
                'mine' => DB::table('review_comments')->where('review_assignment_id', $a->id)->orderBy('id')->get(),
            ];
        } else {
            // Non-confidential invitation metadata so the reviewer can check for a conflict of interest with the proposer
            $data['v'] = DB::table('mc_versions as v')
                ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->join('users as u', 'u.id', '=', 'm.owner_id')
                ->join('ptjs as p', 'p.id', '=', 'm.ptj_id')
                ->where('v.id', $a->mc_version_id)
                ->first(['v.id', 'v.name_en', 'v.name_ms', 'v.version_no', 'm.category', 'u.name as owner_name', 'p.code as ptj_code', 'p.name as ptj_name']);
        }
        $data['assignedBy'] = $a->assigned_by ? DB::table('users')->where('id', $a->assigned_by)->value('name') : null;

        return view('reviews.show', $data);
    }

    // FR-17: declare before opening. A conflict removes the reviewer and tells the person who assigned them.
    public function coi(Request $request, int $assignment)
    {
        $a = $this->mine($request, $assignment);
        abort_unless($a->status === 'invited', 403, 'The declaration is already made.');
        $d = $request->validate([
            'conflict' => 'required|in:none,conflict',
            'coi_reason' => 'nullable|string|max:500',
        ]);
        $conflict = $d['conflict'] === 'conflict';
        $coiReason = $conflict ? (trim((string) ($d['coi_reason'] ?? '')) ?: null) : null;

        DB::transaction(function () use ($a, $conflict, $coiReason, $request) {
            DB::table('review_assignments')->where('id', $a->id)->update(['coi_declared_at' => now(), 'has_conflict' => $conflict, 'status' => $conflict ? 'declined' : 'in_progress', 'updated_at' => now()]);
            $this->audit->record('review.coi_declared', ['type' => 'McVersion', 'id' => $a->mc_version_id], after: ['conflict' => $conflict], reason: $coiReason); // EV-03
            if ($conflict) {
                Tasks::close('review', $a->id, $request->user()->id);
                $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $a->mc_version_id)->first(['v.name_en', 'm.ptj_id']);
                $by = User::find($a->assigned_by);
                $this->notifier->send($by, 'reviewer.declined', ['name' => $by->name, 'reviewer' => $request->user()->name, 'title' => $v->name_en], dedupe: "declined-{$a->id}");
                Tasks::open('deputy_dean', 'assign_reviewers', $a->mc_version_id, 'Reviewer declined, assign another: '.$v->name_en, "/approvals/{$a->mc_version_id}", $v->ptj_id);
            }
        });

        return $conflict ? redirect()->route('reviews')->with('status', 'Recorded. You are removed from this review and the deputy dean is told.') : redirect()->route('reviews.show', $assignment);
    }

    // FR-61, FR-62: rate each item, comment where it needs amendment, then recommend.
    public function submit(Request $request, int $assignment)
    {
        $a = $this->mine($request, $assignment);
        abort_unless($a->status === 'in_progress' && $a->kind === 'reviewer', 403, 'This review cannot be submitted.');
        $umc = DB::table('micro_credentials as m')->join('mc_versions as v', 'v.micro_credential_id', '=', 'm.id')->where('v.id', $a->mc_version_id)->value('m.category') === 'umc';
        $items = collect(config('proposals.review_items'))->when(! $umc, fn ($c) => $c->except('parent_mapping'));
        $rules = ['recommendation' => 'required|in:support,amend,not_support', 'summary' => 'required|string|min:10|max:2000'];
        foreach ($items->keys() as $k) {
            $rules["rating.$k"] = 'required|in:satisfactory,needs_amendment';
            $rules["comment.$k"] = 'nullable|required_if:rating.'.$k.',needs_amendment|string|max:1000';
        }
        $d = $request->validate($rules, ['comment.*.required_if' => 'A "Needs amendment" rating needs a comment.', 'rating.*.required' => 'Rate every item.']);

        DB::transaction(function () use ($a, $d, $items, $request) {
            $ratings = [];
            foreach ($items->keys() as $k) {
                $ratings[$k] = ['rating' => $d['rating'][$k], 'comment' => $d['comment'][$k] ?? null, 'blocking' => $d['rating'][$k] === 'needs_amendment' && isset($request->input('blocking', [])[$k])];
                if ($d['rating'][$k] === 'needs_amendment') {
                    DB::table('review_comments')->insert(['mc_version_id' => $a->mc_version_id, 'review_assignment_id' => $a->id, 'author_id' => $request->user()->id, 'item' => $k, 'text' => $items[$k].': '.$d['comment'][$k], 'blocking' => $ratings[$k]['blocking'], 'created_at' => now()]);
                }
            }
            DB::table('reviews')->insert(['review_assignment_id' => $a->id, 'ratings' => json_encode($ratings), 'recommendation' => $d['recommendation'], 'summary' => $d['summary'], 'submitted_at' => now()]);
            DB::table('review_assignments')->where('id', $a->id)->update(['status' => 'submitted', 'updated_at' => now()]);
            $this->audit->record('review.submitted', ['type' => 'McVersion', 'id' => $a->mc_version_id], after: ['recommendation' => $d['recommendation']], reason: $d['summary']); // EV-10
            Tasks::close('review', $a->id, $request->user()->id);
            $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $a->mc_version_id)->first(['v.name_en', 'm.ptj_id', 'm.owner_id']);
            if (DB::table('review_comments')->where('mc_version_id', $a->mc_version_id)->where('status', 'open')->exists()
                && ! DB::table('tasks')->where('kind', 'respond')->where('ref_id', $a->mc_version_id)->whereNull('completed_at')->exists()) {
                Tasks::open('lecturer', 'respond', $a->mc_version_id, 'Respond to review comments: '.$v->name_en, "/proposals/{$a->mc_version_id}?tab=comments", userId: $v->owner_id);
            }
            $by = User::find($a->assigned_by);
            $this->notifier->send($by, 'review.submitted', ['name' => $by->name, 'reviewer' => $request->user()->name, 'title' => $v->name_en, 'recommendation' => str_replace('_', ' ', $d['recommendation'])], dedupe: "review-{$a->id}");
        });

        return redirect()->route('reviews')->with('status', 'Review submitted. Thank you.');
    }

    // FR-18: an industry expert only comments on competencies and relevance; no rating, no recommendation.
    public function comment(Request $request, int $assignment)
    {
        $a = $this->mine($request, $assignment);
        abort_unless($a->kind === 'expert' && $a->status === 'in_progress', 403, 'Only an industry expert with an open invitation can comment here.');
        abort_unless(in_array(DB::table('mc_versions')->where('id', $a->mc_version_id)->value('status'), ['submitted', 'in_review', 'endorsed'], true), 403, 'Comments close when the decision is recorded.');
        $d = $request->validate(['text' => 'required|string|min:5|max:2000']);
        DB::table('review_comments')->insert(['mc_version_id' => $a->mc_version_id, 'review_assignment_id' => $a->id, 'author_id' => $request->user()->id, 'item' => 'relevance', 'text' => $d['text'], 'blocking' => false, 'created_at' => now()]);
        $this->audit->record('review.expert_commented', ['type' => 'McVersion', 'id' => $a->mc_version_id]);

        return redirect()->route('reviews.show', $assignment)->with('status', 'Comment sent to the proposer.');
    }

    // The proposer answers a comment (FR-63).
    public function respond(Request $request, int $id, int $comment)
    {
        $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $id)->first(['v.*', 'm.owner_id']) ?? abort(404);
        abort_unless($v->owner_id === $request->user()->id, 403, 'This proposal belongs to another lecturer.');
        $d = $request->validate(['response' => 'required|string|min:3|max:2000']);
        $c = DB::table('review_comments')->where('id', $comment)->where('mc_version_id', $id)->first() ?? abort(404);
        abort_unless($c->status === 'open', 422, 'This comment is already answered.');
        DB::table('review_comments')->where('id', $comment)->update(['status' => 'answered', 'response' => $d['response'], 'responded_by' => $request->user()->id, 'responded_at' => now()]);
        $this->audit->record('comment.answered', ['type' => 'McVersion', 'id' => $id], context: ['comment' => $comment]);
        if (! DB::table('review_comments')->where('mc_version_id', $id)->where('blocking', true)->where('status', 'open')->exists()) {
            Tasks::close('respond', $id, $request->user()->id);
        }

        return redirect()->route('proposals.show', ['id' => $id, 'tab' => 'comments'])->with('status', 'Your response is sent.');
    }

    // Batch-respond to multiple open comments at once (FR-63).
    public function respondAll(Request $request, int $id)
    {
        $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.id', $id)->first(['v.*', 'm.owner_id']) ?? abort(404);
        abort_unless($v->owner_id === $request->user()->id, 403, 'This proposal belongs to another lecturer.');
        $raw = (array) $request->input('responses', []);

        $toSave = [];
        foreach ($raw as $cid => $text) {
            $trimmed = trim((string) $text);
            if ($trimmed === '') {
                continue;
            }
            if (mb_strlen($trimmed) < 3 || mb_strlen($trimmed) > 2000) {
                return back()->withErrors(['responses' => __('Each response must be between 3 and 2000 characters.')]);
            }
            $toSave[(int) $cid] = $trimmed;
        }

        if ($toSave === []) {
            return back()->withErrors(['responses' => __('Enter at least one response before submitting.')]);
        }

        $saved = 0;
        DB::transaction(function () use ($id, $toSave, $request, &$saved) {
            $openIds = DB::table('review_comments')
                ->where('mc_version_id', $id)
                ->where('status', 'open')
                ->whereIn('id', array_keys($toSave))
                ->pluck('id');

            foreach ($openIds as $cid) {
                DB::table('review_comments')->where('id', $cid)->update([
                    'status' => 'answered',
                    'response' => $toSave[$cid],
                    'responded_by' => $request->user()->id,
                    'responded_at' => now(),
                ]);
                $this->audit->record('comment.answered', ['type' => 'McVersion', 'id' => $id], context: ['comment' => $cid]);
                $saved++;
            }
            if (! DB::table('review_comments')->where('mc_version_id', $id)->where('blocking', true)->where('status', 'open')->exists()) {
                Tasks::close('respond', $id, $request->user()->id);
            }
        });

        $msg = $saved === 1 ? __('1 response sent.') : __(':count responses sent.', ['count' => $saved]);

        return redirect()->route('proposals.show', ['id' => $id, 'tab' => 'comments'])->with('status', $msg);
    }

    private function mine(Request $request, int $assignment): object
    {
        $a = DB::table('review_assignments')->where('id', $assignment)->first() ?? abort(404);
        abort_unless($a->reviewer_id === $request->user()->id, 403, 'This invitation is for someone else.');

        return $a;
    }
}
