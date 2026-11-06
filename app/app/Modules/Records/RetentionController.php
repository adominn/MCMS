<?php

declare(strict_types=1);

namespace App\Modules\Records;

use App\Modules\Core\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// CODL-13: the CODL administrator sets retention (in the settings), sees what is due and places holds; the configured approver approves disposal.
final class RetentionController
{
    public function __construct(private Retention $retention, private AuditRecorder $audit, private Settings $settings) {}

    public function index()
    {
        return view('records.retention', ['rows' => $this->retention->candidates(), 'holds' => DB::table('retention_holds as h')->join('users as u', 'u.id', '=', 'h.placed_by')->orderByDesc('h.id')->get(['h.*', 'u.name']),
            'history' => DB::table('disposal_records as d')->join('users as u', 'u.id', '=', 'd.approved_by')->orderByDesc('d.id')->limit(20)->get(['d.*', 'u.name']), 'approver' => $this->settings->get('approval.route.disposal')]);
    }

    public function hold(Request $request)
    {
        $d = $request->validate(['category' => 'required|in:'.implode(',', array_keys(Retention::CATEGORIES)), 'reason' => 'required|string|max:1000']);
        abort_if($this->retention->held($d['category']), 422, 'A hold is already on this category.');
        $id = DB::table('retention_holds')->insertGetId($d + ['placed_by' => $request->user()->id, 'placed_at' => now()]);
        $this->audit->record('retention.hold_placed', ['type' => 'RetentionHold', 'id' => $id], after: $d, reason: $d['reason']);

        return back()->with('status', 'Hold placed. Nothing in this category can be disposed of.');
    }

    public function lift(Request $request, int $id)
    {
        $h = DB::table('retention_holds')->where('id', $id)->whereNull('lifted_at')->first() ?? abort(404);
        DB::table('retention_holds')->where('id', $id)->update(['lifted_by' => $request->user()->id, 'lifted_at' => now()]);
        $this->audit->record('retention.hold_lifted', ['type' => 'RetentionHold', 'id' => $id]);

        return back()->with('status', 'Hold lifted.');
    }

    /** Only the approver named in the settings, and only after the period and with no hold (FR-retention). */
    public function dispose(Request $request, string $category)
    {
        abort_unless($request->user()->hasAnyRole((string) $this->settings->get('approval.route.disposal')), 403, 'The role that approves disposal is set in the settings.');
        $r = $this->retention->dispose($category, $request->user()->id);

        return back()->with('status', "Disposal approved and logged: {$r->row_count} row(s) older than ".\Carbon\Carbon::parse($r->cutoff)->format('d/m/Y').'.');
    }
}
