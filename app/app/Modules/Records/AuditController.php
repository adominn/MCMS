<?php

declare(strict_types=1);

namespace App\Modules\Records;

use Illuminate\Support\Facades\DB;

final class AuditController
{
    public function __construct(private AuditRecorder $audit) {}

    public function index()
    {
        return view('audit.index', [
            'events' => DB::table('audit_events as e')->leftJoin('users as u', 'u.id', '=', 'e.actor_id')
                ->orderByDesc('e.seq')->limit(100)->get(['e.seq', 'e.occurred_at', 'u.name as actor', 'e.actor_role', 'e.action', 'e.subject_type', 'e.subject_id', 'e.reason']),
        ]);
    }

    // The person asked for the check, so it is itself recorded.
    public function verify()
    {
        $bad = $this->audit->verify();
        $this->audit->record('audit.integrity_checked', context: ['result' => $bad === null ? 'intact' : "broken at $bad"]);

        return redirect()->route('audit')->with($bad === null ? 'status' : 'broken', $bad === null ? 'Integrity check complete: the chain is intact. No entry has been changed or removed.' : "The chain breaks at entry $bad. Report this to the system owner.");
    }
}
