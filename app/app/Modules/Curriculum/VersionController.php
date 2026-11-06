<?php

declare(strict_types=1);

namespace App\Modules\Curriculum;

use App\Models\User;
use App\Modules\Core\Tasks;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// LEC-01 my micro-credentials, LEC-02 micro-credential and its versions, LEC-06 request a change; DD acknowledgement of a minor change (MD-02).
final class VersionController
{
    public const OPEN = ['draft', 'submitted', 'in_review', 'returned', 'endorsed'];

    public function __construct(private AuditRecorder $audit, private Notifier $notifier, private \App\Modules\Offering\OfferingStatus $offerings) {}

    public function index(Request $request)
    {
        return view('curriculum.mcs.index', ['rows' => DB::table('micro_credentials as m')->join('mc_versions as v', function ($j) {
            $j->on('v.micro_credential_id', '=', 'm.id')->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = m.id)');
        })->where('m.owner_id', $request->user()->id)->orderByDesc('v.updated_at')->get(['m.id', 'm.category', 'v.name_en', 'v.version_no', 'v.status'])]);
    }

    public function show(Request $request, int $mc)
    {
        $m = $this->mine($request, $mc);
        $versions = DB::table('mc_versions as v')->where('v.micro_credential_id', $mc)->orderByDesc('v.version_no')->get();
        $decisions = DB::table('approval_decisions')->whereIn('mc_version_id', $versions->pluck('id'))->where('stage', 'senate')->orderBy('id')->get()->groupBy('mc_version_id');
        $rules = DB::table('version_transition_rules')->whereIn('mc_version_id', $versions->pluck('id'))->get()->keyBy('mc_version_id');

        $cohorts = DB::table('offerings as o')->whereIn('o.mc_version_id', $versions->pluck('id'))->orderByDesc('o.start_date')->get(['o.id', 'o.cohort_name', 'o.start_date', 'o.end_date', 'o.status']);
        $modules = ($cur = $versions->firstWhere('status', 'approved')) ? DB::table('mc_modules')->where('mc_version_id', $cur->id)->orderBy('seq')->get(['seq', 'name_en', 'slt_hours']) : collect();

        return view('curriculum.mcs.show', [
            'cohorts' => $cohorts, 'modules' => $modules,
            'm' => $m, 'versions' => $versions, 'decisions' => $decisions, 'rules' => $rules, 'rulesText' => config('proposals.transition_rules'),
            'current' => $versions->firstWhere('status', 'approved'), 'open' => $versions->first(fn ($v) => in_array($v->status, self::OPEN, true)),
            'changes' => DB::table('change_requests')->where('micro_credential_id', $mc)->orderByDesc('id')->get(), 'statusLabels' => \App\Modules\Approval\ApprovalController::STATUS + ['superseded' => 'Superseded'],
        ]);
    }

    public function changeForm(Request $request, int $mc)
    {
        $this->mine($request, $mc);

        return view('curriculum.mcs.change', ['mc' => $mc, 'triggers' => config('proposals.change_triggers'), 'current' => $this->current($mc), 'blocked' => $this->openVersion($mc)]);
    }

    // FR-75: a change that touches any listed item is material, automatically. The lecturer cannot classify it down.
    public function change(Request $request, int $mc)
    {
        $m = $this->mine($request, $mc);
        $current = $this->current($mc) ?? abort(403, 'Only an approved version can be changed.');
        abort_if($this->openVersion($mc), 403, 'Another version is still in progress.');
        $triggers = array_values(array_intersect((array) $request->input('triggers'), array_keys(config('proposals.change_triggers'))));
        $material = $triggers !== [];
        $d = $request->validate(['reason' => 'required|string|min:5|max:2000', 'impact_note' => ($material ? 'nullable' : 'required').'|string|min:5|max:2000']);

        $crId = DB::transaction(function () use ($m, $mc, $current, $triggers, $material, $d, $request) {
            $qv = $material && $current->qv_status === 'verified'; // FR-78
            $to = $material ? $this->copy($current->id, 'revision', 'material', $d['reason'], $request->user()) : null;
            $cr = DB::table('change_requests')->insertGetId([
                'micro_credential_id' => $mc, 'from_version_id' => $current->id, 'to_version_id' => $to, 'class' => $material ? 'material' : 'minor', 'triggers' => json_encode($triggers),
                'reason' => $d['reason'], 'impact_note' => $d['impact_note'] ?? null, 'requires_qv' => $qv, 'requested_by' => $request->user()->id,
                'status' => $material ? 'new_version' : 'recorded', 'created_at' => now(),
            ]);
            if ($material) {
                $this->audit->record('change.material_recorded', ['type' => 'McVersion', 'id' => $current->id], after: ['triggers' => $triggers, 'new_version' => $to, 'requires_qv' => $qv], reason: $d['reason']); // EV-07
                $this->offerings->holdForChange($mc, 'A material change waits for approval (version '.DB::table('mc_versions')->find($to)->version_no.').');
                if ($qv) {
                    DB::table('qv_records')->where('micro_credential_id', $mc)->where('status', 'verified')->update(['reverification' => true]);
                    Tasks::open('deputy_dean', 'qv_reverify', $to, 'Quality Verification must be repeated: '.$current->name_en, "/approvals/$to", $m->ptj_id);
                }
            } else {
                $this->audit->record('change.minor_logged', ['type' => 'McVersion', 'id' => $current->id], after: ['change' => $cr], reason: $d['reason']); // EV-08
                Tasks::open('deputy_dean', 'ack_change', $cr, 'Acknowledge minor change: '.$current->name_en, "/changes/$cr", $m->ptj_id);
                foreach ($this->deans($m->ptj_id) as $dd) {
                    $this->notifier->send($dd, 'change.minor', ['name' => $dd->name, 'owner' => $request->user()->name, 'title' => $current->name_en, 'summary' => $d['reason']], dedupe: "minor-$cr");
                }
            }

            return [$cr, $to];
        });

        return $crId[1]
            ? redirect()->route('proposals.show', $crId[1])->with('status', 'This is a material change, so it makes a new version. It goes through review and approval again and takes effect only when approved.')
            : redirect()->route('mcs.show', $mc)->with('status', 'This is a minor change. It is recorded and waits for the deputy dean to acknowledge it.');
    }

    // Re-offer: start again from the approved version (FR-34).
    public function reoffer(Request $request, int $mc)
    {
        $this->mine($request, $mc);
        $current = $this->current($mc) ?? abort(403, 'Only an approved version can be re-offered.');
        abort_if($this->openVersion($mc), 403, 'Another version is still in progress.');
        $d = $request->validate(['summary' => 'required|string|min:5|max:2000']);
        $to = DB::transaction(fn () => $this->copy($current->id, 'reoffer', 'reoffer', $d['summary'], $request->user()));

        return redirect()->route('proposals.show', $to)->with('status', 'A re-offer proposal is started from the approved version. Check it and submit it for approval.');
    }

    // ---- deputy dean: acknowledge a minor change (FR-77)

    public function changeShow(Request $request, int $id)
    {
        [$cr, $v] = $this->changeOf($request, $id);

        return view('approval.change', ['cr' => $cr, 'v' => $v, 'triggers' => config('proposals.change_triggers')]);
    }

    public function acknowledge(Request $request, int $id)
    {
        [$cr, $v] = $this->changeOf($request, $id);
        abort_unless($cr->class === 'minor' && $cr->status === 'recorded', 403, 'Nothing to acknowledge.');
        DB::transaction(function () use ($cr, $request) {
            DB::table('change_requests')->where('id', $cr->id)->update(['status' => 'acknowledged', 'acknowledged_by' => $request->user()->id, 'acknowledged_at' => now(), 'cqi_logged_at' => now()]);
            $this->audit->record('change.acknowledged', ['type' => 'McVersion', 'id' => $cr->from_version_id], context: ['change' => $cr->id, 'logged_for_cqi' => true]);
            Tasks::close('ack_change', $cr->id);
        });

        return redirect()->route('changes.show', $id)->with('status', 'Acknowledged and logged for the quality record of the offering.');
    }

    // ---- helpers

    private function mine(Request $request, int $mc): object
    {
        $m = DB::table('micro_credentials')->find($mc) ?? abort(404);
        abort_unless($m->owner_id === $request->user()->id, 403, 'This micro-credential belongs to another lecturer.');

        return $m;
    }

    private function current(int $mc): ?object
    {
        return DB::table('mc_versions')->where('micro_credential_id', $mc)->where('status', 'approved')->orderByDesc('version_no')->first();
    }

    private function openVersion(int $mc): ?object
    {
        return DB::table('mc_versions')->where('micro_credential_id', $mc)->whereIn('status', self::OPEN)->first();
    }

    private function deans(int $ptj)
    {
        return User::whereIn('id', DB::table('role_assignments')->where('role', 'deputy_dean')->where(fn ($q) => $q->where('ptj_id', $ptj)->orWhere('scope_type', 'university'))->pluck('user_id'))
            ->where('status', 'active')->get()->filter(fn ($u) => $u->hasAnyRole('deputy_dean'));
    }

    private function changeOf(Request $request, int $id): array
    {
        $cr = DB::table('change_requests')->find($id) ?? abort(404);
        $v = DB::table('mc_versions as v')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->join('users as u', 'u.id', '=', 'm.owner_id')->where('v.id', $cr->from_version_id)->first(['v.name_en', 'v.version_no', 'm.ptj_id', 'u.name as owner']);
        $ids = $request->user()->scopedPtjIds('deputy_dean');
        abort_unless($ids === null || in_array($v->ptj_id, $ids, true), 403, 'This change belongs to another PTj.');

        return [$cr, $v];
    }

    /** A new draft version with the content of an earlier one. The new version is never approved by the copy (FR-76). */
    private function copy(int $fromId, string $type, string $class, string $summary, User $by): int
    {
        $f = (array) DB::table('mc_versions')->find($fromId);
        $new = array_diff_key($f, array_flip(['id', 'created_at', 'updated_at', 'submitted_at', 'submit_response', 'effective_date']));
        $new = array_merge($new, [
            'version_no' => 1 + (int) DB::table('mc_versions')->where('micro_credential_id', $f['micro_credential_id'])->max('version_no'), 'proposal_type' => $type, 'status' => 'draft', 'supersedes_id' => $fromId,
            'change_class' => $class, 'change_summary' => $summary, 'ip_confirmed' => false, 'updated_by' => $by->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $id = DB::table('mc_versions')->insertGetId($new);

        $mod = [];
        foreach (DB::table('mc_modules')->where('mc_version_id', $fromId)->orderBy('seq')->get() as $m) {
            $mod[$m->id] = DB::table('mc_modules')->insertGetId(['mc_version_id' => $id, 'seq' => $m->seq, 'name_en' => $m->name_en, 'name_ms' => $m->name_ms, 'slt_hours' => $m->slt_hours, 'is_assessment' => $m->is_assessment, 'separately_recognisable' => $m->separately_recognisable]);
        }
        $lo = [];
        foreach (DB::table('learning_outcomes')->where('mc_version_id', $fromId)->orderBy('sort_order')->get() as $l) {
            $lo[$l->id] = DB::table('learning_outcomes')->insertGetId(['mc_version_id' => $id, 'code' => $l->code, 'text_en' => $l->text_en, 'text_ms' => $l->text_ms, 'sort_order' => $l->sort_order]);
        }
        foreach (DB::table('assessment_components')->where('mc_version_id', $fromId)->orderBy('id')->get() as $c) {
            $cid = DB::table('assessment_components')->insertGetId(['mc_version_id' => $id, 'mc_module_id' => $mod[$c->mc_module_id] ?? null, 'name_en' => $c->name_en, 'weight_percent' => $c->weight_percent, 'csv_key' => $c->csv_key, 'max_mark' => $c->max_mark, 'pass_mark' => $c->pass_mark]);
            foreach (DB::table('assessment_component_outcomes')->where('assessment_component_id', $c->id)->pluck('learning_outcome_id') as $oid) {
                DB::table('assessment_component_outcomes')->insert(['assessment_component_id' => $cid, 'learning_outcome_id' => $lo[$oid]]);
            }
        }
        foreach (DB::table('proposal_readiness')->where('mc_version_id', $fromId)->get() as $r) {
            DB::table('proposal_readiness')->insert(['mc_version_id' => $id, 'item' => $r->item, 'status' => $r->status, 'note' => $r->note]);
        }
        $this->audit->record('version.created', ['type' => 'McVersion', 'id' => $id], after: ['version_no' => $new['version_no'], 'type' => $type, 'from' => $fromId], reason: $summary);

        return $id;
    }
}
