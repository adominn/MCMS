<?php

declare(strict_types=1);

namespace App\Modules\Core;

use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// FR-24: PTj, academic programmes and parent courses, by form or by CSV import.
final class ReferenceDataController
{
    public const ENTITIES = [
        'ptj' => ['table' => 'ptjs', 'label' => 'PTj (faculties and centres)', 'cols' => ['code' => 'Code', 'name' => 'Name'],
            'rules' => ['code' => 'required|string|max:20', 'name' => 'required|string|max:200']],
        'programme' => ['table' => 'programmes', 'label' => 'Academic programmes', 'cols' => ['code' => 'Code', 'name' => 'Name', 'mqf_level' => 'MQF level', 'mode' => 'Mode', 'accreditation_status' => 'Accreditation', 'total_credits' => 'Graduating credits'],
            'rules' => ['code' => 'required|string|max:30', 'name' => 'required|string|max:200', 'mqf_level' => 'required|integer|between:1,8', 'mode' => 'required|in:conventional,odl', 'accreditation_status' => 'required|in:accredited,pending,none', 'total_credits' => 'required|integer|min:1|max:400']],
        'course' => ['table' => 'parent_courses', 'label' => 'Parent courses', 'cols' => ['code' => 'Code', 'name' => 'Name', 'programme_code' => 'Programme code', 'credit' => 'Credit', 'slt_hours' => 'SLT (hours)', 'clos' => 'CLOs (separated by ;)'],
            'rules' => ['code' => 'required|string|max:30', 'name' => 'required|string|max:200', 'programme_code' => 'required|exists:programmes,code', 'credit' => 'required|integer|min:1|max:30', 'slt_hours' => 'required|integer|min:1|max:2000', 'clos' => 'required|string|max:2000']],
    ];

    public function __construct(private AuditRecorder $audit, private \App\Modules\Notifications\Notifier $notifier) {}

    public function index(string $entity)
    {
        $e = self::ENTITIES[$entity] ?? abort(404);

        return view('admin.reference', ['entity' => $entity, 'e' => $e, 'rows' => ($entity === 'course' ? DB::table('parent_courses')->leftJoin('programmes', 'programmes.id', '=', 'parent_courses.programme_id')->select('parent_courses.*', 'programmes.code as programme_code', 'programmes.name as programme_name')->orderBy('parent_courses.code') : DB::table($e['table'])->orderBy('code'))->get(), 'nav' => self::ENTITIES]);
    }

    public function store(Request $request, string $entity)
    {
        $e = self::ENTITIES[$entity] ?? abort(404);
        $row = $request->validate($e['rules']);
        $this->save($e['table'], [$row]);
        $this->audit->record('refdata.saved', ['type' => $e['table'], 'id' => $row['code']], after: $row);

        return back()->with('status', 'Saved '.$row['code'].'.');
    }

    // A wrong header rejects the whole file; a bad row is listed and the good rows are kept (as FR-113 for imports).
    public function import(Request $request, string $entity)
    {
        $e = self::ENTITIES[$entity] ?? abort(404);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $h = fopen($request->file('file')->getRealPath(), 'r');
        $head = array_map(fn ($c) => trim((string) $c), fgetcsv($h) ?: []);
        if ($head !== array_keys($e['cols'])) {
            return back()->withErrors(['file' => 'The first row must be exactly: '.implode(',', array_keys($e['cols'])).'. Nothing was imported.']);
        }
        $good = [];
        $errors = [];
        for ($n = 2; ($r = fgetcsv($h)) !== false; $n++) {
            if ($r === [null]) {
                continue;
            }
            $v = Validator::make(array_combine($head, array_pad(array_map('trim', $r), count($head), '')), $e['rules']);
            $v->fails() ? $errors[] = "Row $n: ".$v->errors()->first() : $good[] = $v->validated();
        }
        fclose($h);
        DB::transaction(function () use ($e, $good, $entity) {
            $this->save($e['table'], $good);
            $this->audit->record('refdata.imported', ['type' => $e['table'], 'id' => $entity], after: ['rows' => count($good)]);
        });

        return back()->with('status', count($good).' rows imported.')->withErrors($errors ? ['rows' => $errors] : []);
    }

    private function save(string $table, array $rows): void
    {
        if ($table === 'parent_courses') {
            $rows = array_map(fn ($r) => ['programme_id' => DB::table('programmes')->where('code', $r['programme_code'])->value('id')] + array_diff_key($r, ['programme_code' => 1]), $rows);
        }
        $rows = array_map(fn ($r) => $r + ['created_at' => now(), 'updated_at' => now()], $rows);
        if ($rows) {
            DB::table($table)->upsert($rows, ['code'], array_diff(array_keys($rows[0]), ['code', 'created_at']));
            if ($table === 'parent_courses') {
                foreach ($rows as $r) {
                    \App\Modules\Pathways\Plans::flagCourse($r['code'], 'The target course '.$r['code'].' changed: review the mapping.'); // a material change to the target flags the mapping (FR-spcurrchange)
                }
            }

            // Guide Cl. 11.5 / REQ-C11-012: Updating parent_courses or programmes automatically opens periodic_reviews for all linked active UMCs and notifies P1/P2/P4
            if (in_array($table, ['parent_courses', 'programmes'], true)) {
                $courseIds = [];
                if ($table === 'parent_courses') {
                    $codes = array_column($rows, 'code');
                    $courseIds = DB::table('parent_courses')->whereIn('code', $codes)->pluck('id')->all();
                } else {
                    $progCodes = array_column($rows, 'code');
                    $progIds = DB::table('programmes')->whereIn('code', $progCodes)->pluck('id')->all();
                    $courseIds = DB::table('parent_courses')->whereIn('programme_id', $progIds)->pluck('id')->all();
                }

                if (! empty($courseIds)) {
                    $activeUmcs = DB::table('mc_versions as v')
                        ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                        ->where('v.status', 'approved')
                        ->whereIn('v.parent_course_id', $courseIds)
                        ->select('m.id', 'm.owner_id', 'm.ptj_id', 'v.name_en')
                        ->distinct()
                        ->get();

                    foreach ($activeUmcs as $umc) {
                        $existing = DB::table('periodic_reviews')
                            ->where('micro_credential_id', $umc->id)
                            ->whereNull('completed_on')
                            ->first();

                        if (! $existing) {
                            DB::table('periodic_reviews')->insert([
                                'micro_credential_id' => $umc->id,
                                'due_on' => today()->toDateString(),
                                'note' => 'Triggered by parent course/programme update',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            DB::table('periodic_reviews')->where('id', $existing->id)->update([
                                'due_on' => today()->toDateString(),
                                'updated_at' => now(),
                            ]);
                        }

                        // Notify P1 (Owner/Lecturer)
                        $owner = \App\Models\User::find($umc->owner_id);
                        if ($owner) {
                            $this->notifier->send($owner, 'review.due', [
                                'name' => $owner->name,
                                'title' => $umc->name_en.' (Parent Course Updated)',
                                'date' => today()->format('d/m/Y'),
                            ], dedupe: "review-parent-p1-{$umc->id}-{$owner->id}-".today()->toDateString());
                        }

                        // Notify P2 (PTj authority / Deputy Dean)
                        $p2Users = \App\Models\User::whereIn('id', DB::table('role_assignments')
                            ->whereIn('role', ['deputy_dean', 'ptj_authority'])
                            ->where(fn ($q) => $q->where('ptj_id', $umc->ptj_id)->orWhere('scope_type', 'university'))
                            ->pluck('user_id'))->get();
                        foreach ($p2Users as $p2) {
                            $this->notifier->send($p2, 'review.due', [
                                'name' => $p2->name,
                                'title' => $umc->name_en.' (Parent Course Updated)',
                                'date' => today()->format('d/m/Y'),
                            ], dedupe: "review-parent-p2-{$umc->id}-{$p2->id}-".today()->toDateString());
                        }

                        // Notify P4 (QA Officer)
                        $p4Users = \App\Models\User::whereIn('id', DB::table('role_assignments')
                            ->where('role', 'qa_officer')
                            ->where(fn ($q) => $q->where('ptj_id', $umc->ptj_id)->orWhere('scope_type', 'university'))
                            ->pluck('user_id'))->get();
                        foreach ($p4Users as $p4) {
                            $this->notifier->send($p4, 'review.due', [
                                'name' => $p4->name,
                                'title' => $umc->name_en.' (Parent Course Updated)',
                                'date' => today()->format('d/m/Y'),
                            ], dedupe: "review-parent-p4-{$umc->id}-{$p4->id}-".today()->toDateString());
                        }
                    }
                }
            }
        }
    }
}
