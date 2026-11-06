<?php

declare(strict_types=1);

namespace App\Modules\Enrolment;

use App\Models\User;
use App\Modules\Core\Tasks;
use App\Modules\Notifications\IntegrationHub;
use App\Modules\Notifications\Notifier;
use App\Modules\Offering\OfferingStatus;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ST-03 register (four steps), ST-04 registrations and payments, ST-05 registration, DOC-01 receipt (MD-05).
final class EnrolmentController
{
    public function __construct(private Enrolments $enrol, private Recognition $recognition, private PaymentAdapter $payment, private AuditRecorder $audit, private Notifier $notifier, private IntegrationHub $hub, private OfferingStatus $status) {}

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $enrolments = DB::table('enrolments as e')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')
            ->leftJoin('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
            ->where('e.user_id', $userId)
            ->orderByDesc('e.id')
            ->select([
                'e.id', 'e.reference', 'e.status', 'e.fee_amount', 'e.created_at as registered_at', 'e.progress_percent',
                'o.id as offering_id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.delivery_mode',
                'v.id as mc_version_id', 'v.name_en', 'v.name_ms', 'm.code as mc_code', 'v.proposed_credit', 'v.planned_slt',
                'm.category as mc_category',
            ])
            ->get();

        $versionIds = $enrolments->pluck('mc_version_id')->unique()->filter();
        $moduleCounts = DB::table('mc_modules')
            ->whereIn('mc_version_id', $versionIds)
            ->select('mc_version_id', DB::raw('count(*) as count'))
            ->groupBy('mc_version_id')
            ->pluck('count', 'mc_version_id');

        $enrolmentIds = $enrolments->pluck('id');
        $succeededPayments = DB::table('payments')
            ->whereIn('enrolment_id', $enrolmentIds)
            ->where('status', 'succeeded')
            ->get()
            ->keyBy('enrolment_id');

        $allPayments = DB::table('payments as p')
            ->join('enrolments as e', 'e.id', '=', 'p.enrolment_id')
            ->join('offerings as o', 'o.id', '=', 'e.offering_id')
            ->join('mc_versions as v', 'v.id', '=', 'e.mc_version_id')
            ->where('e.user_id', $userId)
            ->orderByDesc('p.id')
            ->select([
                'p.*',
                'e.reference as enrolment_ref',
                'v.name_en as course_name',
                'o.cohort_name',
            ])
            ->get();

        $rows = $enrolments->map(function ($e) use ($moduleCounts, $succeededPayments) {
            $e->module_count = $moduleCounts[$e->mc_version_id ?? 0] ?? 0;
            $e->payment = $succeededPayments[$e->id] ?? null;
            $fee = (float) ($e->fee_amount ?: 0);
            if ($fee <= 0) {
                $e->payment_status = 'free';
                $e->paid_amount = 0.0;
                $e->outstanding = 0.0;
            } elseif ($e->payment) {
                $e->payment_status = 'paid';
                $e->paid_amount = (float) $e->payment->amount;
                $e->outstanding = 0.0;
            } elseif ($e->status === 'pending_payment') {
                $e->payment_status = 'pending';
                $e->paid_amount = 0.0;
                $e->outstanding = $fee;
            } else {
                $e->payment_status = 'unpaid';
                $e->paid_amount = 0.0;
                $e->outstanding = 0.0;
            }
            return $e;
        });

        $totalFee = $rows->sum(fn ($r) => (float) ($r->fee_amount ?: 0));
        $totalPaid = $allPayments->where('status', 'succeeded')->sum(fn ($p) => (float) $p->amount);
        $totalOutstanding = $rows->sum('outstanding');
        $activeCount = $rows->whereIn('status', ['enrolled', 'registered', 'pending_payment'])->count();

        return view('student.enrolments', [
            'rows' => $rows,
            'payments' => $allPayments,
            'summary' => [
                'total_registrations' => $rows->count(),
                'active_count' => $activeCount,
                'total_fee' => $totalFee,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
            ],
            'labels' => config('enrolment.status'),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $e = $this->mine($request, $id);
        $o = $this->offering($e->offering_id);

        $achieved = DB::table('module_achievements')->where('enrolment_id', $id)->get()->keyBy('mc_module_id');
        $badges = DB::table('digital_badges')
            ->whereIn('module_achievement_id', $achieved->pluck('id'))
            ->get()
            ->keyBy('module_achievement_id');

        $modules = DB::table('mc_modules')
            ->where('mc_version_id', $e->mc_version_id)
            ->orderBy('seq')
            ->get(['id', 'seq', 'name_en', 'name_ms', 'slt_hours', 'separately_recognisable'])
            ->map(function ($m) use ($achieved, $badges) {
                $ach = $achieved->get($m->id);
                $m->achieved_at = $ach->achieved_at ?? null;
                $m->achievement_id = $ach->id ?? null;
                if ($ach && $badges->has($ach->id)) {
                    $m->badge = $badges->get($ach->id);
                } else {
                    $m->badge = null;
                }
                return $m;
            });

        $payment = DB::table('payments')->where('enrolment_id', $id)->where('status', 'succeeded')->first();
        $allPayments = DB::table('payments')->where('enrolment_id', $id)->orderByDesc('id')->get();
        $log = DB::table('enrolment_status_log')->where('enrolment_id', $id)->orderBy('id')->get();

        return view('student.enrolment', [
            'modules' => $modules,
            'e' => $e,
            'o' => $o,
            'labels' => config('enrolment.status'),
            'deadline' => $this->enrol->withdrawalDeadline($o),
            'canWithdraw' => $e->status === 'enrolled' && today()->lte($this->enrol->withdrawalDeadline($o)),
            'payment' => $payment,
            'allPayments' => $allPayments,
            'log' => $log,
        ]);
    }

    // ---- the registration steps

    public function wizard(Request $request, int $offering)
    {
        $this->status->refresh();
        $o = $this->offering($offering);
        $u = $request->user();
        $mine = DB::table('enrolments')->where('user_id', $u->id)->where('offering_id', $offering)->whereNotIn('status', ['withdrawn', 'cancelled'])->first();
        $step = $mine ? ($mine->status === 'pending_payment' ? 3 : 4) : ($request->session()->get("reg.$offering") ? 2 : 1);
        $ack = $this->recognition->acknowledged($offering);

        return view('student.register', [
            'o' => $o, 'step' => $step, 'e' => $mine, 'reasons' => $mine ? [] : $this->enrol->reasons($u, $o), 'ack' => $ack[$request->session()->get('lang', 'en')] ?? $ack['en'], 'ackMs' => $ack['ms'],
            'pdpa' => ! DB::table('pdpa_acknowledgements')->where('user_id', $u->id)->where('notice_version', config('enrolment.pdpa_version'))->exists(), 'pdpaText' => config('enrolment.pdpa'),
            'token' => (string) Str::uuid(), 'platform' => $o->platform_ref,
            'payment' => $mine ? DB::table('payments')->where('enrolment_id', $mine->id)->orderByDesc('id')->first() : null,
        ]);
    }

    public function eligibility(Request $request, int $offering)
    {
        $o = $this->offering($offering);
        $d = $request->validate(['phone' => ['required', 'regex:/^\+?[0-9][0-9 \-]{6,18}$/'], 'prerequisites_ok' => $o->prerequisites ? 'accepted' : 'nullable'],
            ['phone.regex' => 'Enter a phone number we can reach you on.', 'prerequisites_ok.accepted' => 'Confirm that you meet the prerequisites.']);
        if ($reasons = $this->enrol->reasons($request->user(), $o)) {
            return redirect()->route('register', $offering)->withErrors(['eligibility' => implode(' ', $reasons)]);
        }
        $request->user()->update(['phone' => $d['phone']]);
        $request->session()->put("reg.$offering", true);

        return redirect()->route('register', $offering);
    }

    // FR-99, FR-100: the wording and the personal data notice are acknowledged, and the wording is stored with the enrolment.
    public function acknowledge(Request $request, int $offering)
    {
        abort_unless($request->session()->get("reg.$offering"), 403, 'Start with the first step.');
        $u = $request->user();
        $first = ! DB::table('pdpa_acknowledgements')->where('user_id', $u->id)->where('notice_version', config('enrolment.pdpa_version'))->exists();
        $request->validate(['ack_statement' => 'accepted', 'pdpa' => $first ? 'accepted' : 'nullable'], ['ack_statement.accepted' => 'Tick that you have read the recognition statement.', 'pdpa.accepted' => 'Tick that you have read the personal data notice.']);

        $id = DB::transaction(function () use ($u, $offering, $first) {
            $o = DB::table('offerings')->where('id', $offering)->lockForUpdate()->first(); // two people cannot take the last place
            $o = $this->offering($offering);
            abort_if($this->enrol->reasons($u, $o), 403, 'You can no longer register for this cohort.');
            $ack = $this->recognition->acknowledged($offering);
            $id = DB::table('enrolments')->insertGetId([
                'reference' => 'TMP'.Str::random(10), 'user_id' => $u->id, 'offering_id' => $offering, 'mc_version_id' => $o->mc_version_id, 'status' => 'registered', 'fee_amount' => $o->fee,
                'ack_text' => "EN:\n{$ack['en']}\n\nMS:\n{$ack['ms']}", 'ack_versions' => json_encode($ack['versions']), 'status_changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('enrolments')->where('id', $id)->update(['reference' => 'E-'.now()->year.'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT)]);
            DB::table('enrolment_status_log')->insert(['enrolment_id' => $id, 'from_status' => null, 'to_status' => 'registered', 'changed_by' => $u->id, 'changed_at' => now()]);
            if ($first) {
                DB::table('pdpa_acknowledgements')->insert(['user_id' => $u->id, 'notice_version' => config('enrolment.pdpa_version'), 'acknowledged_at' => now()]);
            }
            $this->audit->record('student.registered', ['type' => 'Enrolment', 'id' => $id], after: ['offering' => $offering, 'fee' => $o->fee], context: ['pdpa_first' => $first]); // EV-20
            if ((float) $o->fee > 0) {
                $this->enrol->setStatus($id, 'pending_payment', null, $u->id);
                Tasks::open('student', 'payment', $id, 'Finish payment for '.$o->name_en.' '.$o->cohort_name, route('register', $offering, false), userId: $u->id, area: 'offerings');
                $this->notifier->send($u, 'enrolment.registered', ['name' => $u->name, 'title' => $o->name_en.' '.$o->cohort_name, 'fee' => number_format((float) $o->fee, 2)], dedupe: "registered-$id");
            } else {
                $this->enrol->confirm($id, $u->id);
            }

            return $id;
        });
        $request->session()->forget("reg.$offering");

        return redirect()->route('register', $offering);
    }

    // FR-101: payment through the adapter; the payment reference is the idempotency key (IR-17).
    public function pay(Request $request, int $offering)
    {
        $u = $request->user();
        $e = DB::table('enrolments')->where('user_id', $u->id)->where('offering_id', $offering)->where('status', 'pending_payment')->first();
        if (! $e) {
            return redirect()->route('register', $offering); // nothing to pay: never charge twice
        }
        $d = $request->validate(['card' => ['required', 'regex:/^[0-9 ]{13,23}$/'], 'attempt' => 'required|uuid'], ['card.regex' => 'Enter the card number (digits only).']);
        $card = preg_replace('/\D/', '', $d['card']);
        abort_unless(strlen($card) >= 13 && strlen($card) <= 19, 422, 'Enter a valid card number.');
        $reference = "PAY-{$e->id}-{$d['attempt']}";
        if ($done = DB::table('payments')->where('reference', $reference)->first()) { // the same attempt sent twice
            return $this->paid($done, $offering);
        }

        try {
            $provider = $this->payment->charge((string) $e->fee_amount, $card, $reference);
            $row = ['status' => 'succeeded', 'provider_ref' => $provider, 'failure_reason' => null];
        } catch (PaymentDeclined $ex) {
            $row = ['status' => 'failed', 'provider_ref' => null, 'failure_reason' => $ex->getMessage()];
        }
        $pid = DB::transaction(function () use ($e, $reference, $card, $row, $u) {
            $pid = DB::table('payments')->insertGetId(['enrolment_id' => $e->id, 'amount' => $e->fee_amount, 'card_last4' => substr($card, -4), 'reference' => $reference, 'created_at' => now()] + $row);
            $this->hub->record('payment', 'charge', $reference, $row['status'] === 'succeeded', $row['failure_reason'], ['enrolment' => $e->reference, 'amount' => $e->fee_amount, 'last4' => substr($card, -4)]);
            if ($row['status'] === 'succeeded') {
                $this->audit->record('payment.confirmed', ['type' => 'Enrolment', 'id' => $e->id], after: ['reference' => $reference, 'amount' => $e->fee_amount]); // EV-21
                $this->enrol->confirm($e->id, $u->id);
                Tasks::close('payment', $e->id, $u->id);
                $this->notifier->send($u, 'payment.confirmed', ['name' => $u->name, 'reference' => $e->reference, 'amount' => number_format((float) $e->fee_amount, 2), 'payment' => $row['provider_ref']], dedupe: "paid-{$e->id}");
            } else {
                $this->audit->record('payment.failed', ['type' => 'Enrolment', 'id' => $e->id], after: ['reference' => $reference], reason: $row['failure_reason']);
            }

            return $pid;
        });

        return $this->paid(DB::table('payments')->find($pid), $offering);
    }

    public function withdraw(Request $request, int $id)
    {
        $e = $this->mine($request, $id);
        $o = $this->offering($e->offering_id);
        abort_unless($e->status === 'enrolled', 403, 'Only an enrolled registration can be withdrawn.');
        abort_unless(today()->lte($this->enrol->withdrawalDeadline($o)), 403, 'The withdrawal deadline has passed.');
        $d = $request->validate(['reason' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($e, $o, $d, $request) {
            $this->enrol->setStatus($e->id, 'withdrawn', $d['reason'], $request->user()->id);
            Tasks::close('payment', $e->id, $request->user()->id);
            $this->audit->record('enrolment.withdrawn', ['type' => 'Enrolment', 'id' => $e->id], before: ['status' => 'enrolled'], after: ['status' => 'withdrawn'], reason: $d['reason']); // EV-23
            $this->notifier->send($request->user(), 'enrolment.withdrawn', ['name' => $request->user()->name, 'title' => $o->name_en.' '.$o->cohort_name], dedupe: "withdrawn-{$e->id}");
        });

        return redirect()->route('enrolments.show', $id)->with('status', 'You have withdrawn. Refunds are handled outside MCMS: ask the CODL office.');
    }

    // DOC-01: the receipt, laid out for A4.
    public function confirmation(Request $request, int $id)
    {
        $e = $this->mine($request, $id);
        $o = $this->offering($e->offering_id);
        $p = DB::table('payments')->where('enrolment_id', $id)->where('status', 'succeeded')->first();
        $deadline = $this->enrol->withdrawalDeadline($o);

        return view('student.confirmation', [
            'e' => $e,
            'o' => $o,
            'p' => $p,
            'u' => $request->user(),
            'deadline' => $deadline,
        ]);
    }

    public function receipt(Request $request, int $id)
    {
        $e = $this->mine($request, $id);

        return view('student.receipt', ['e' => $e, 'o' => $this->offering($e->offering_id), 'p' => DB::table('payments')->where('enrolment_id', $id)->where('status', 'succeeded')->first(), 'u' => $request->user()]);
    }

    // ---- helpers

    private function paid(object $p, int $offering)
    {
        return $p->status === 'succeeded'
            ? redirect()->route('register', $offering)->with('status', 'Payment received. You are enrolled.')
            : redirect()->route('register', $offering)->withErrors(['payment' => "Payment declined. Reference {$p->reference}. The registration stays pending and nothing was charged."]);
    }

    private function mine(Request $request, int $id): object
    {
        $e = DB::table('enrolments')->find($id) ?? abort(404);
        abort_unless($e->user_id === $request->user()->id, 403, 'This registration belongs to someone else.');

        return $e;
    }

    private function offering(int $id): object
    {
        return DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('o.id', $id)
            ->first(['o.*', 'v.name_en', 'v.name_ms', 'm.category']) ?? abort(404);
    }
}
