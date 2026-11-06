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
        return view('reviews.index', ['rows' => DB::table('review_assignments as a')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')
            ->where('a.reviewer_id', $request->user()->id)->orderBy('a.due_on')->get(['a.id', 'a.kind', 'a.status', 'a.due_on', 'v.name_en'])]);
    }

    public function show(Request $request, int $assignment)
    {
        $a = $this->mine($request, $assignment);
        $data = ['a' => $a, 'items' => config('proposals.review_items'), 'status' => $a->status];
        if (in_array($a->status, ['in_progress', 'submitted'], true)) { // the proposal stays closed until the conflict-of-interest answer is given
            $data += ProposalSummary::load($a->mc_version_id) + [
                'review' => DB::table('reviews')->where('review_assignment_id', $a->id)->first(),
                'mine' => DB::table('review_comments')->where('review_assignment_id', $a->id)->orderBy('id')->get(),
            ];
        }
        $data['v'] = $data['v'] ?? DB::table('mc_versions')->find($a->mc_version_id);

        return view('reviews.show', $data);
    }

    // FR-17: declare before opening. A conflict removes the reviewer and tells the person who assigned them.
    public function coi(Request $request, int $assignment)
    {
        $a = $this->mine($request, $assignment);
        abort_unless($a->status === 'invited', 403, 'The declaration is already made.');
        $d = $request->validate(['conflict' => 'required|in:none,conflict']);
        $conflict = $d['conflict'] === 'conflict';

        DB::transaction(function () use ($a, $conflict, $request) {
            DB::table('review_assignments')->where('id', $a->id)->update(['coi_declared_at' => now(), 'has_conflict' => $conflict, 'status' => $conflict ? 'declined' : 'in_progress', 'updated_at' => now()]);
            $this->audit->record('review.coi_declared', ['type' => 'McVersion', 'id' => $a->mc_version_id], after: ['conflict' => $conflict]); // EV-03
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
            if (DB::table('review_comments')->where('mc_version_id', $a->mc_version_id)->where('blocking', true)->where('status', 'open')->exists()
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

    private function mine(Request $request, int $assignment): object
    {
        $a = DB::table('review_assignments')->where('id', $assignment)->first() ?? abort(404);
        abort_unless($a->reviewer_id === $request->user()->id, 403, 'This invitation is for someone else.');

        return $a;
    }
}
