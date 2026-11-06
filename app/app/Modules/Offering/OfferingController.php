<?php

declare(strict_types=1);

namespace App\Modules\Offering;

use App\Models\User;
use App\Modules\Core\Tasks;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// LEC-11 create an offering, LEC-12 readiness checklist, DD-06 offerings and readiness, DD-07 confirm readiness (MD-04).
final class OfferingController
{
    private const TEXT_FIELDS = ['description', 'prerequisites', 'access_period', 'learning_support', 'completion_conditions'];

    public function __construct(private AuditRecorder $audit, private Notifier $notifier, private OfferingStatus $status) {}

    public function index(Request $request)
    {
        $this->status->refresh();
        $rows = $this->scoped($request)->orderByDesc('o.start_date')->get(['o.id', 'o.cohort_name', 'o.status', 'o.readiness_state', 'o.start_date', 'o.reg_opens_on', 'v.name_en', 'v.version_no']);

        return view('offerings.index', ['rows' => $rows, 'labels' => config('offerings.status'), 'dean' => $request->user()->hasAnyRole('deputy_dean')]);
    }

    public function create(Request $request)
    {
        return view('offerings.new', ['versions' => $this->approvedVersions($request)->get(['v.id', 'v.name_en', 'v.version_no', 'v.delivery_mode']), 'modes' => config('proposals.delivery_modes'), 'ucreds' => DB::table('ucreds_courses')->orderBy('course_id')->get(['course_id', 'title', 'subject_code'])]);
    }

    // FR-83, FR-84: only an approved version, inside the caller's scope.
    public function store(Request $request)
    {
        $d = $this->validated($request) + $request->validate(['mc_version_id' => 'required|integer']);
        $v = $this->approvedVersions($request)->where('v.id', $d['mc_version_id'])->first() ?? abort(403, 'Only an approved version, inside your scope, can be offered.');

        $id = DB::transaction(function () use ($d, $v, $request) {
            $id = DB::table('offerings')->insertGetId(array_diff_key($d, ['mc_version_id' => 1]) + ['mc_version_id' => $v->id, 'created_by' => $request->user()->id, 'staged_completion' => $request->boolean('staged_completion'), 'created_at' => now(), 'updated_at' => now()]);
            $prev = DB::table('offerings as o')->join('mc_versions as pv', 'pv.id', '=', 'o.mc_version_id')->where('pv.micro_credential_id', $v->micro_credential_id)->where('o.id', '!=', $id)->orderByDesc('o.id')->value('o.id');
            foreach (array_keys(config('offerings.readiness')) as $item) { // the checklist is repeated for every cohort, pre-filled from the last (FR-88)
                $p = $prev ? DB::table('readiness_items')->where('offering_id', $prev)->where('item', $item)->first() : null;
                DB::table('readiness_items')->insert(['offering_id' => $id, 'item' => $item, 'status' => $p->status ?? 'not_yet', 'note' => $p->note ?? null, 'accepted' => $p->accepted ?? false]);
            }
            $this->audit->record('offering.created', ['type' => 'Offering', 'id' => $id], after: ['version' => $v->id, 'cohort' => $d['cohort_name'], 'prefilled' => (bool) $prev]);

            return $id;
        });

        return redirect()->route('offerings.show', $id)->with('status', 'Cohort created. Answer the readiness checklist next; registration cannot open until the deputy dean confirms it.');
    }

    public function show(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        $this->status->refresh();
        $o = $this->find($request, $id);

        return view('offerings.show', [
            'o' => $o, 'items' => DB::table('readiness_items')->where('offering_id', $id)->get()->keyBy('item'), 'labels' => config('offerings.status'), 'items_cfg' => config('offerings.readiness'),
            'confirmations' => DB::table('readiness_confirmations as c')->join('users as u', 'u.id', '=', 'c.user_id')->where('c.offering_id', $id)->orderBy('c.id')->get(['c.*', 'u.name']),
            'dean' => $request->user()->hasAnyRole('deputy_dean') && $o->owner_id !== $request->user()->id, 'editable' => ! in_array($o->status, ['cancelled', 'ended', 'running', 'held'], true),
        ]);
    }

    // LEC-09: the participants of a cohort, filtered by status, exportable (FR-108).
    public function participants(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        $labels = config('enrolment.status');
        $status = array_key_exists((string) $request->query('status'), $labels) ? $request->query('status') : null;
        $base = DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->where('e.offering_id', $id);
        $rows = (clone $base)->when($status, fn ($b) => $b->where('e.status', $status))->orderBy('e.id')->get(['e.id', 'e.reference', 'u.name', 'u.email', 'e.status', 'e.created_at']);
        if ($request->query('export') === 'csv') {
            $this->audit->record('participants.exported', ['type' => 'Offering', 'id' => $id], context: ['rows' => $rows->count(), 'status' => $status]);

            return response()->streamDownload(function () use ($rows, $labels) {
                $h = fopen('php://output', 'w');
                fputcsv($h, ['Registration', 'Name', 'Email', 'Status', 'Registered']);
                foreach ($rows as $r) {
                    fputcsv($h, [$r->reference, $r->name, $r->email, $labels[$r->status], substr((string) $r->created_at, 0, 10)]);
                }
                fclose($h);
            }, 'participants-'.$id.'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('offerings.participants', ['o' => $o, 'rows' => $rows, 'labels' => $labels, 'status' => $status, 'counts' => (clone $base)->selectRaw('e.status, count(*) n')->groupBy('e.status')->pluck('n', 'status'),
            'taken' => (clone $base)->whereIn('e.status', config('enrolment.holds_place'))->count()]);
    }

    // The lecturer answers the items and sends the checklist (the lecturer's own confirmation, FR-86).
    public function readiness(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        abort_unless($request->user()->id === $o->owner_id && ! in_array($o->status, ['cancelled', 'ended', 'running', 'held'], true), 403, 'Only the lecturer can answer the checklist while the cohort is planned.');
        foreach (array_keys(config('offerings.readiness')) as $item) {
            if (! $request->has("item.$item")) {
                continue; // only the items on the form are changed
            }
            $s = $request->input("item.$item.status");
            $old = DB::table('readiness_items')->where(['offering_id' => $id, 'item' => $item])->first();
            $new = ['status' => in_array($s, ['yes', 'not_yet', 'na'], true) ? $s : 'not_yet', 'note' => mb_substr(trim((string) $request->input("item.$item.note")), 0, 500) ?: null];
            DB::table('readiness_items')->where(['offering_id' => $id, 'item' => $item])->update($new + ($old->status !== $new['status'] || $old->note !== $new['note'] ? ['accepted' => false] : []));
        }

        if ($request->input('action') === 'send') {
            $d = $request->validate(['outcome' => 'required|in:ready,ready_conditions,not_ready', 'conditions' => 'nullable|required_if:outcome,ready_conditions|string|max:1000']);
            $this->requireAnswered($id, $d['outcome']);
            DB::transaction(function () use ($id, $d, $request, $o) {
                DB::table('readiness_confirmations')->insert(['offering_id' => $id, 'role' => 'lecturer', 'user_id' => $request->user()->id, 'outcome' => $d['outcome'], 'conditions' => $d['conditions'] ?? null, 'created_at' => now()]);
                $this->audit->record('readiness.sent', ['type' => 'Offering', 'id' => $id], after: ['outcome' => $d['outcome']], reason: $d['conditions'] ?? null);
                if (! DB::table('tasks')->where('kind', 'readiness')->where('ref_id', $id)->whereNull('completed_at')->exists()) {
                    Tasks::open('deputy_dean', 'readiness', $id, 'Confirm readiness: '.$o->name_en.' '.$o->cohort_name, "/offerings/$id", $o->ptj_id, area: 'offerings');
                }
            });

            return redirect()->route('offerings.show', $id)->with('status', 'Checklist sent to the deputy dean.');
        }

        return redirect()->route('offerings.show', $id)->with('status', 'Saved.');
    }

    // FR-86, FR-87: the PTj confirmation opens the way to registration. Its own record (D-15).
    public function confirm(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        abort_unless($request->user()->hasAnyRole('deputy_dean') && $o->owner_id !== $request->user()->id, 403, 'Only a deputy dean of the PTj, not the lecturer of the cohort, confirms readiness.');
        abort_if(in_array($o->status, ['cancelled', 'ended', 'running', 'held'], true), 403, 'This cohort can no longer be confirmed.');
        abort_unless(DB::table('readiness_confirmations')->where('offering_id', $id)->where('role', 'lecturer')->exists(), 403, 'The lecturer has not sent the checklist yet.');
        $d = $request->validate(['outcome' => 'required|in:ready,ready_conditions,not_ready', 'conditions' => 'nullable|required_if:outcome,ready_conditions|string|max:1000']);

        foreach (array_keys(config('offerings.readiness')) as $item) {
            DB::table('readiness_items')->where(['offering_id' => $id, 'item' => $item])->update(['accepted' => isset($request->input('accept', [])[$item])]);
        }
        $this->requireAnswered($id, $d['outcome'], accepted: true);

        DB::transaction(function () use ($id, $d, $request, $o) {
            DB::table('readiness_confirmations')->insert(['offering_id' => $id, 'role' => 'ptj', 'user_id' => $request->user()->id, 'outcome' => $d['outcome'], 'conditions' => $d['conditions'] ?? null, 'created_at' => now()]);
            DB::table('offerings')->where('id', $id)->update(['readiness_state' => $d['outcome'], 'updated_at' => now()]);
            $this->audit->record('readiness.confirmed', ['type' => 'Offering', 'id' => $id], after: ['outcome' => $d['outcome']], reason: $d['conditions'] ?? null); // EV-17
            Tasks::close('readiness', $id);
            $owner = User::find($o->owner_id);
            $this->notifier->send($owner, 'decision.made', ['name' => $owner->name, 'item' => $o->name_en.' '.$o->cohort_name, 'decision' => 'Readiness: '.config('offerings.outcomes')[$d['outcome']]], dedupe: "readiness-$id-".now()->timestamp);
        });
        $this->status->refresh();

        return redirect()->route('offerings.show', $id)->with('status', $d['outcome'] === 'not_ready' ? 'Recorded as not ready. Registration stays closed.' : 'Readiness confirmed. Registration opens on its date, and the cohort shows in the public catalogue.');
    }

    public function cancel(Request $request, int $id)
    {
        $o = $this->find($request, $id);
        abort_unless($request->user()->hasAnyRole('deputy_dean') && $o->owner_id !== $request->user()->id, 403);
        abort_if(in_array($o->status, ['cancelled', 'ended', 'running'], true), 403, 'This cohort can no longer be cancelled.');
        $d = $request->validate(['reason' => 'required|string|min:5|max:500']);
        DB::table('offerings')->where('id', $id)->update(['status' => 'cancelled', 'cancel_reason' => $d['reason'], 'updated_at' => now()]);
        $this->audit->record('offering.cancelled', ['type' => 'Offering', 'id' => $id], reason: $d['reason']);
        $enrol = app(\App\Modules\Enrolment\Enrolments::class);
        foreach (DB::table('enrolments')->where('offering_id', $id)->whereIn('status', ['registered', 'pending_payment', 'enrolled'])->get() as $e) {
            $enrol->setStatus($e->id, 'cancelled', 'The cohort was cancelled: '.$d['reason'], $request->user()->id);
            $u = User::find($e->user_id);
            $this->notifier->send($u, 'enrolment.cancelled', ['name' => $u->name, 'title' => $o->name_en.' '.$o->cohort_name, 'reason' => 'The cohort was cancelled. Refunds are handled outside MCMS: ask the CODL office.'], dedupe: "cohort-cancel-{$e->id}");
        }
        Tasks::close('readiness', $id);

        return redirect()->route('offerings.show', $id)->with('status', 'Cohort cancelled.');
    }

    // ---- helpers

    private function requireAnswered(int $id, string $outcome, bool $accepted = false): void
    {
        if ($outcome !== 'ready') {
            return;
        }
        $items = DB::table('readiness_items')->where('offering_id', $id)->get();
        if ($items->contains('status', 'not_yet')) {
            throw ValidationException::withMessages(['outcome' => '"Ready" needs every checklist item answered Yes or Not applicable. Use "Ready with conditions" or "Not ready".']);
        }
        if ($accepted && $items->where('status', 'yes')->contains('accepted', false)) {
            throw ValidationException::withMessages(['outcome' => '"Ready" needs the evidence of every Yes item accepted. Tick "Accept evidence" for each, or choose another outcome.']);
        }
    }

    private function validated(Request $request): array
    {
        $d = $request->validate([
            'cohort_name' => 'required|string|max:60', 'start_date' => 'required|date|after:today', 'end_date' => 'required|date|after:start_date',
            'delivery_mode' => 'required|in:'.implode(',', array_keys(config('proposals.delivery_modes'))), 'capacity' => 'required|integer|min:1|max:5000',
            'reg_opens_on' => 'required|date', 'reg_closes_on' => 'required|date|after_or_equal:reg_opens_on|before_or_equal:start_date', 'fee' => 'required|numeric|min:0|max:100000',
            'facilitators' => 'nullable|string|max:1000', 'eligible_groups' => 'nullable|string|max:1000', 'platform_ref' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:3000', 'prerequisites' => 'nullable|string|max:1000', 'access_period' => 'nullable|string|max:200', 'learning_support' => 'nullable|string|max:1000', 'completion_conditions' => 'nullable|string|max:1000', 'completion_window_months' => 'nullable|integer|min:1|max:120',
        ]);
        $errors = [];
        foreach (self::TEXT_FIELDS as $f) {
            if ($hit = ForbiddenClaims::scan($d[$f] ?? null)) {
                $errors[$f] = 'This text contains '.implode('; ', $hit).'. Remove it: recognition is stated only through the approved wording.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $d;
    }

    private function approvedVersions(Request $request)
    {
        $user = $request->user();
        $ptjs = $user->scopedPtjIds('deputy_dean');

        return DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('v.status', 'approved')
            ->where(function ($q) use ($user, $ptjs) {
                $q->where('m.owner_id', $user->id);
                if ($user->hasAnyRole('deputy_dean')) {
                    $q->orWhereRaw($ptjs === null ? 'true' : 'm.ptj_id in ('.implode(',', array_map('intval', $ptjs) ?: [0]).')');
                }
            })->select('v.*', 'm.ptj_id');
    }

    private function scoped(Request $request)
    {
        $user = $request->user();
        $ptjs = $user->scopedPtjIds('deputy_dean');

        return DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where(function ($q) use ($user, $ptjs) {
                $q->where('m.owner_id', $user->id);
                if ($user->hasAnyRole('deputy_dean')) {
                    $q->orWhereRaw($ptjs === null ? 'true' : 'm.ptj_id in ('.implode(',', array_map('intval', $ptjs) ?: [0]).')');
                }
            });
    }

    private function find(Request $request, int $id): object
    {
        return $this->scoped($request)->where('o.id', $id)->first(['o.*', 'v.name_en', 'v.version_no', 'm.ptj_id', 'm.owner_id', 'm.category']) ?? abort(404);
    }
}
