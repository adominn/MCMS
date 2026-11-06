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
        $deposits = DB::table('abc_deposits as d')->join('acrs as a', 'a.id', '=', 'd.acr_id')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->where('a.user_id', $request->user()->id)->whereNotNull('d.credit_status')
            ->get(['d.*', 'a.reference as acr_reference', 'a.mc_version_id', 'v.name_en'])->map(function ($d) {
                $d->why = $this->ledger->whyNot($d);
                $d->expired = $d->credit_status === 'expired' || $d->credit_expiry < today()->toDateString();
                $d->busy = $this->activeLine($d->id);
                if ($d->busy) {
                    $d->why = 'This credit is already in an application that is still being evaluated ('.$d->busy->reference.'). Wait for its decision.';
                }
                $d->selectable = ! $d->why || ($d->expired && $d->credit_status !== 'provisional' && $d->credit_status !== 'redeemed' && ! $d->busy); // expired credit is selectable with a flag (FR-ctavailable)

                return $d;
            });

        return view('transfer.create', ['deposits' => $deposits, 'programmes' => DB::table('programmes')->orderBy('code')->get(), 'courses' => DB::table('parent_courses')->orderBy('code')->get()]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['programme_id' => 'required|integer|exists:programmes,id', 'institution' => 'nullable|string|max:120', 'documents' => 'nullable|string|max:2000', 'deposits' => 'required|array|min:1', 'deposits.*' => 'integer',
            'course' => 'required|array', 'credits' => 'required|array'], ['deposits.required' => 'Choose the credit you want to use.']);
        $lines = [];
        foreach (array_unique(array_map('intval', $d['deposits'])) as $did) {
            $dep = DB::table('abc_deposits as d')->join('acrs as a', 'a.id', '=', 'd.acr_id')->join('mc_versions as v', 'v.id', '=', 'a.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->where('d.id', $did)->where('a.user_id', $request->user()->id)->first(['d.*', 'a.mc_version_id', 'v.version_no', 'm.category']) ?? abort(404);
            if ($dep->category === 'mce') {
                throw ValidationException::withMessages(['deposits' => 'A Micro-Credential Eksekutif cannot be used directly for credit transfer.']); // BR-32
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
            $course = DB::table('parent_courses')->where('id', (int) ($d['course'][$did] ?? 0))->where('programme_id', $d['programme_id'])->first();
            if (! $course) {
                throw ValidationException::withMessages(['course' => 'Choose a course of the target programme for each credit.']);
            }
            $cr = (float) ($d['credits'][$did] ?? 0);
            if ($cr <= 0 || $cr > (float) $dep->credits) {
                throw ValidationException::withMessages(['credits' => 'Credits requested must be more than 0 and not more than the credit you hold ('.(float) $dep->credits.').']);
            }
            $lines[] = [$dep, $course, $cr, $expired];
        }
        $app = DB::transaction(function () use ($d, $lines, $request) {
            $prog = DB::table('programmes')->find($d['programme_id']);
            $id = DB::table('transfer_applications')->insertGetId(['reference' => 'TMP'.bin2hex(random_bytes(8)), 'user_id' => $request->user()->id, 'programme_id' => $prog->id, 'institution' => ($d['institution'] ?? null) ?: 'UTeM', 'documents' => $d['documents'] ?? null,
                'applied_by' => $request->user()->id, 'applied_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $ref = 'TR-'.now()->year.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            DB::table('transfer_applications')->where('id', $id)->update(['reference' => $ref]);
            foreach ($lines as [$dep, $course, $cr, $expired]) {
                DB::table('transfer_lines')->insert(['transfer_application_id' => $id, 'acr_id' => $dep->acr_id, 'abc_deposit_id' => $dep->id, 'mc_version_id' => $dep->mc_version_id, 'parent_course_id' => $course->id, 'credits_requested' => $cr,
                    'reevaluate' => $expired, 'target_version' => $prog->curriculum_version, 'source_version_no' => $dep->version_no, 'created_at' => now(), 'updated_at' => now()]);
            }
            Tasks::open('deputy_dean', 'transfer_eval', $id, "Evaluate credit transfer $ref ({$request->user()->name})", "/transfers/$id", $prog->ptj_id, area: 'transfers');
            $this->audit->record('transfer.applied', ['type' => 'TransferApplication', 'id' => $id], after: ['reference' => $ref, 'lines' => count($lines)]); // EV-51

            return $id;
        });

        return redirect()->route('transfer.show', $app)->with('status', 'Application submitted. The deputy dean of the receiving faculty will evaluate it. Applying does not mean the credit is accepted or that you are admitted.');
    }

    public function show(Request $request, int $id)
    {
        $a = DB::table('transfer_applications as t')->join('programmes as p', 'p.id', '=', 't.programme_id')->where('t.id', $id)->where('t.user_id', $request->user()->id)->first(['t.*', 'p.code as programme', 'p.name as programme_name']) ?? abort(404);

        return view('transfer.show', ['a' => $a, 'lines' => Transfers::lines($id), 'decision' => DB::table('transfer_decisions')->where('transfer_application_id', $id)->orderByDesc('id')->first(), 'status' => config('transfer.status')]);
    }

    /** The participant answers a request for more evidence (More evidence needed returns to Submitted). */
    public function evidence(Request $request, int $id)
    {
        $a = DB::table('transfer_applications')->where('id', $id)->where('user_id', $request->user()->id)->first() ?? abort(404);
        abort_unless($a->status === 'more_evidence', 422, 'No evidence is requested for this application.');
        $d = $request->validate(['documents' => 'required|string|max:2000']);
        DB::transaction(function () use ($a, $d) {
            DB::table('transfer_applications')->where('id', $a->id)->update(['status' => 'submitted', 'documents' => trim($a->documents."\n".$d['documents']), 'updated_at' => now()]);
            Tasks::close('transfer_evidence', $a->id);
            $ptj = DB::table('programmes')->where('id', $a->programme_id)->value('ptj_id');
            Tasks::open('deputy_dean', 'transfer_eval', $a->id, "Evaluate credit transfer {$a->reference}: evidence added", "/transfers/{$a->id}", $ptj, area: 'transfers');
        });

        return back()->with('status', 'Evidence added. The application is back with the evaluator.');
    }

    private function activeLine(int $depositId): ?object
    {
        return DB::table('transfer_lines as l')->join('transfer_applications as t', 't.id', '=', 'l.transfer_application_id')->where('l.abc_deposit_id', $depositId)->whereIn('t.status', self::ACTIVE)->first(['t.reference']);
    }
}
