<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Modules\Core\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// IR-07: after sign-in the user lands on one task page listing what waits for them, across all their roles.
final class TaskController
{
    public const AREAS = ['proposals' => 'Proposals', 'offerings' => 'Offerings', 'results' => 'Results', 'credentials' => 'Credentials', 'deposits' => 'Deposits', 'transfers' => 'Transfers'];

    public function __construct(private Settings $settings) {}

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->hasAnyRole('student') && ! $user->hasAnyRole('lecturer', 'deputy_dean', 'codl_admin', 'industry_expert')) {
            return $this->student($user);
        }
        if ($user->hasAnyRole('industry_expert') && ! $user->hasAnyRole('student', 'lecturer', 'deputy_dean', 'codl_admin')) {
            return redirect()->route('reviews'); // the expert's home is the list of invitations (prototype: My review invitations)
        }
        $roles = $user->activeRoles();
        $q = trim((string) $request->query('q'));
        $area = array_key_exists((string) $request->query('area'), self::AREAS) ? $request->query('area') : null;
        $base = fn () => $this->visible($user)->whereNull('completed_at')
            ->when($q !== '', fn ($b) => $b->where('title', db_like(), "%$q%"));
        $counts = $base()->selectRaw('area, count(*) as n')->groupBy('area')->pluck('n', 'area');
        $days = (int) $this->settings->get('alerts.decision_overdue_days');

        return view('tasks', [
            'roles' => $roles, 'q' => $q, 'area' => $area, 'counts' => $counts, 'areas' => self::AREAS, 'total' => $counts->sum(),
            'tasks' => $base()->when($area, fn ($b) => $b->where('area', $area))->orderBy('created_at')->paginate(10)->withQueryString(),
            'overdueBefore' => now()->subDays($days), 'days' => $days,
            'kpis' => $user->hasAnyRole('lecturer') && ! $user->hasAnyRole('deputy_dean', 'codl_admin') ? $this->lecturerKpis($user) : null,
            'recent' => $this->visible($user)->whereNotNull('completed_at')->where('completed_at', '>=', now()->subDays(7))->orderByDesc('completed_at')->limit(5)->get(),
        ]);
    }

    /** The lecturer's figures at the top of the home (prototype "My micro-credentials"). */
    private function lecturerKpis(\App\Models\User $user): array
    {
        $v = DB::table('micro_credentials as m')->join('mc_versions as x', 'x.micro_credential_id', '=', 'm.id')->where('m.owner_id', $user->id)
            ->whereRaw('x.version_no = (select max(version_no) from mc_versions where micro_credential_id = m.id)');

        return [
            'Approved' => (clone $v)->where('x.status', 'approved')->count(),
            'In review or pending' => (clone $v)->whereIn('x.status', ['submitted', 'in_review', 'endorsed', 'pending_approval'])->count(),
            'Drafts' => (clone $v)->whereIn('x.status', ['draft', 'returned'])->count(),
            'Open or upcoming offerings' => DB::table('offerings as o')->join('mc_versions as x', 'x.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'x.micro_credential_id')
                ->where('m.owner_id', $user->id)->whereIn('o.status', ['open', 'ready', 'draft'])->count(),
        ];
    }

    /** The participant's home: what needs them, what is running, what waits, what they have achieved (the prototype's "My learning"). */
    private function student(\App\Models\User $user)
    {
        $mine = fn () => DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')->where('e.user_id', $user->id)
            ->select('e.id', 'e.status', 'e.mc_version_id', 'e.offering_id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'v.name_en', 'e.progress_percent', 'e.fee_amount', 'o.fee', 'e.created_at', 'e.updated_at');
        $inProgress = $mine()->where('e.status', 'enrolled')->where('o.start_date', '<=', today())->where('o.end_date', '>=', today())->get()->map(function ($e) {
            $e->modules = (int) DB::table('mc_modules')->where('mc_version_id', $e->mc_version_id)->count();
            $e->achieved = (int) DB::table('module_achievements')->where('enrolment_id', $e->id)->count();
            $e->percent = $e->progress_percent !== null ? (int) $e->progress_percent : ($e->modules > 0 ? (int) round($e->achieved / $e->modules * 100) : 0);

            return $e;
        });
        $waiting = $mine()->where('e.status', 'enrolled')->get()->filter(fn ($e) => \App\Modules\Achievement\Completion::check($e->id)['met'])->values();
        $achieved = DB::table('credentials as c')->join('enrolments as e', 'e.id', '=', 'c.enrolment_id')->where('e.user_id', $user->id)->orderByDesc('c.id')->get(['c.id', 'c.number', 'c.public_code', 'c.status', 'c.issued_at', 'c.content'])
            ->map(function ($c) {
                $x = json_decode($c->content, true) ?: [];
                $c->name = $x['name']['en'] ?? 'Credential';
                $c->category = $x['category'] ?? '';

                return $c;
            });

        // 1. Pending payment enrolments
        $pendingPayments = $mine()->where('e.status', 'pending_payment')->get()->map(function ($e) {
            $fee = (float) ($e->fee_amount ?: $e->fee ?: 0);

            return (object) [
                'id' => 'pay-'.$e->id,
                'title' => __('Finish payment for :course :cohort', ['course' => $e->name_en, 'cohort' => $e->cohort_name]),
                'subtitle' => __('RM :amount due. Your place is held until the registration window ends.', ['amount' => number_format($fee, 2)]),
                'link' => route('register', $e->offering_id),
                'action_label' => __('Pay now'),
                'dot_class' => 'warn',
                'created_at' => $e->created_at,
            ];
        });

        // 2. Pending ACR consent (verified Academic Credit Record awaiting participant deposit decision)
        $pendingConsent = DB::table('acrs as a')
            ->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')
            ->where('a.user_id', $user->id)
            ->whereIn('a.status', ['verified', 'consent_declined'])
            ->select('a.id', 'a.reference', 'v.name_en', 'a.created_at', 'a.confirmed_at')
            ->get()
            ->map(function ($a) {
                return (object) [
                    'id' => 'acr-'.$a->id,
                    'title' => __('Consent needed: deposit your :course credit in ABC', ['course' => $a->name_en]),
                    'subtitle' => __('ACR :ref confirmed. Give, decline or postpone.', ['ref' => $a->reference]),
                    'link' => route('credit.acr', $a->id),
                    'action_label' => __('Respond'),
                    'dot_class' => 'warn',
                    'created_at' => $a->confirmed_at ?: $a->created_at,
                ];
            });

        // 3. Pending feedback for completed enrolments
        $pendingFeedback = $mine()->where('e.status', 'completed')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('feedback_responses')->whereColumn('feedback_responses.enrolment_id', 'e.id'))
            ->get()
            ->map(function ($e) {
                return (object) [
                    'id' => 'feedback-'.$e->id,
                    'title' => __('Give course feedback: :course', ['course' => $e->name_en]),
                    'subtitle' => __('Cohort :cohort completed. Share feedback to help improve the micro-credential.', ['cohort' => $e->cohort_name]),
                    'link' => route('feedback.show', $e->id),
                    'action_label' => __('Feedback'),
                    'dot_class' => 'info',
                    'created_at' => $e->updated_at ?: $e->created_at,
                ];
            });

        // 4. Pending transfer evidence requests
        $pendingEvidence = DB::table('transfer_applications as t')
            ->where('t.user_id', $user->id)
            ->where('t.status', 'more_evidence')
            ->select('t.id', 't.reference', 't.evidence_request', 't.updated_at')
            ->get()
            ->map(function ($t) {
                return (object) [
                    'id' => 'transfer-'.$t->id,
                    'title' => __('Add evidence to your transfer application :ref', ['ref' => $t->reference]),
                    'subtitle' => $t->evidence_request ?: __('Additional evidence requested by faculty evaluation.'),
                    'link' => route('transfer.show', $t->id),
                    'action_label' => __('Add evidence'),
                    'dot_class' => 'warn',
                    'created_at' => $t->updated_at,
                ];
            });

        // 5. Database tasks explicitly assigned to the user
        $dbTasks = $this->visible($user)->whereNull('completed_at')->orderBy('created_at')->get()
            ->map(function ($t) {
                return (object) [
                    'id' => $t->id,
                    'title' => $t->title,
                    'subtitle' => __('Waiting since :date', ['date' => \Carbon\Carbon::parse($t->created_at)->format('d/m/Y')]),
                    'link' => $t->link ?: route('tasks.show', $t->id),
                    'action_label' => __('Open'),
                    'dot_class' => '',
                    'created_at' => $t->created_at,
                ];
            });

        $existingLinks = collect();
        $allActions = collect();

        foreach ([$pendingPayments, $pendingConsent, $pendingEvidence, $pendingFeedback] as $collection) {
            foreach ($collection as $item) {
                $existingLinks->push($item->link);
                $allActions->push($item);
            }
        }

        foreach ($dbTasks as $t) {
            if (! $existingLinks->contains($t->link)) {
                $allActions->push($t);
            }
        }

        $actions = $allActions->sortByDesc('created_at')->values()->take(6);

        return view('student.home', [
            'actions' => $actions,
            'inProgress' => $inProgress,
            'waiting' => $waiting,
            'achieved' => $achieved->take(5),
            'upcoming' => $mine()->where(fn ($w) => $w->whereIn('e.status', ['registered', 'pending_payment'])->orWhere(fn ($x) => $x->where('e.status', 'enrolled')->where('o.start_date', '>', today())))->get(),
            'track' => [
                'modules' => (int) DB::table('module_achievements as m')->join('enrolments as e', 'e.id', '=', 'm.enrolment_id')->where('e.user_id', $user->id)->count(),
                'credentials' => $achieved->whereIn('status', ['valid', 'corrected'])->count(),
                'consent' => (int) DB::table('acrs')->where('user_id', $user->id)->whereIn('status', ['verified', 'consent_declined'])->count(),
                'transfer' => (int) DB::table('transfer_applications')->where('user_id', $user->id)->whereIn('status', ['submitted', 'under_eval', 'more_evidence'])->count(),
            ],
        ]);
    }

    // Tasks of the user's roles, inside the PTj scope of each role (FR-12).
    public function visible(\App\Models\User $user)
    {
        return DB::table('tasks')->where(function ($q) use ($user) {
            $q->orWhere('user_id', $user->id); // a task for me by name
            foreach ($user->activeRoles() as $r) {
                $ids = $user->scopedPtjIds($r->value);
                $q->orWhere(fn ($w) => $w->where('assignee_role', $r->value)->whereNull('user_id')->when($ids !== null, fn ($x) => $x->where(fn ($y) => $y->whereNull('ptj_id')->orWhereIn('ptj_id', $ids))));
            }
        });
    }

    public function show(Request $request, int $id)
    {
        $t = $this->visible($request->user())->where('id', $id)->first() ?? abort(404);

        if ($t->link) {
            return redirect($t->link);
        }

        return view('tasks-show', ['t' => $t, 'area' => self::AREAS[$t->area]]);
    }
}
