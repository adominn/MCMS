<?php

declare(strict_types=1);

namespace App\Modules\Learning;

use App\Models\User;
use App\Modules\Core\Tasks;
use App\Modules\Notifications\Notifier;
use App\Modules\Offering\OfferingStatus;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// CODL-07 UCreds exchange, DOC-14 import validation report, DOC-15 enrolment export (MD-06). The Manual adapter: files go by hand.
final class UcredsController
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier, private ImportValidator $validator, private OfferingStatus $status) {}

    /** The public catalogue of UCreds as reference data (D-34, Decision Log v0.7 Appendix A). */
    public function courses(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $domain = (string) $request->query('domain');
        $rows = DB::table('ucreds_courses')->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('title', db_like(), "%$q%")->orWhere('subject_code', db_like(), "%$q%")->orWhereRaw(DB::connection()->getDriverName() === 'pgsql' ? 'course_id::text = ?' : 'CAST(course_id AS CHAR) = ?', [$q])))
            ->when($domain !== '', fn ($b) => $b->where('domain', $domain))->orderBy('kind', 'desc')->orderBy('course_id')->paginate(25)->withQueryString();

        return view('ucreds.courses', ['rows' => $rows, 'q' => $q, 'domain' => $domain, 'domains' => DB::table('ucreds_courses')->distinct()->orderBy('domain')->pluck('domain')]);
    }

    public function index(Request $request)
    {
        $this->status->refresh();

        return view('ucreds.index', [
            'offerings' => $this->offerings()->get(),
            'imports' => DB::table('import_files as f')->join('offerings as o', 'o.id', '=', 'f.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('users as u', 'u.id', '=', 'f.run_by')
                ->orderByDesc('f.id')->limit(30)->get(['f.*', 'o.cohort_name', 'v.name_en', 'u.name as by']),
            'exports' => DB::table('export_files as f')->join('offerings as o', 'o.id', '=', 'f.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('users as u', 'u.id', '=', 'f.run_by')
                ->orderByDesc('f.id')->limit(15)->get(['f.*', 'o.cohort_name', 'v.name_en', 'u.name as by']),
        ]);
    }

    // FR-111: the enrolment list in the published template. Same input, same bytes (IR-17).
    public function export(Request $request, int $offering)
    {
        $o = $this->offering($offering);
        $rows = DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->where('e.offering_id', $offering)->where('e.status', 'enrolled')
            ->whereNotNull('u.institution_id')->orderBy('u.institution_id')->get(['u.institution_id', 'u.name', 'e.reference']);
        $csv = implode(',', Template::ENROL)."\r\n";
        foreach ($rows as $r) {
            $csv .= implode(',', array_map(fn ($v) => $this->field((string) $v), [$r->institution_id, $r->name, $r->reference, Template::offeringRef($offering), $o->cohort_name, substr((string) $o->start_date, 0, 10), substr((string) $o->end_date, 0, 10)]))."\r\n";
        }
        $sha = hash('sha256', $csv);
        $path = "ucreds/exports/enrol-{$offering}-$sha.csv";
        Storage::disk('local')->put($path, $csv);
        if (! DB::table('export_files')->where('offering_id', $offering)->where('sha256', $sha)->exists()) { // a repeated export of the same data is not logged twice
            DB::table('export_files')->insert(['offering_id' => $offering, 'template_version' => Template::VERSION, 'path' => $path, 'sha256' => $sha, 'row_count' => $rows->count(), 'run_by' => $request->user()->id, 'created_at' => now()]);
            $this->audit->record('export.logged', ['type' => 'Offering', 'id' => $offering], after: ['file' => "enrol-{$offering}.csv", 'rows' => $rows->count(), 'sha256' => $sha]); // EV-24
        }

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="enrol-'.Template::offeringRef($offering).'.csv"']);
    }

    public function exportManifest(Request $request, int $offering)
    {
        $o = $this->offering($offering);
        $rows = DB::table('enrolments as e')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->where('e.offering_id', $offering)
            ->where('e.status', 'enrolled')
            ->whereNotNull('u.institution_id')
            ->orderBy('u.institution_id')
            ->get(['u.institution_id', 'u.name', 'e.reference']);

        $ref = Template::offeringRef($offering);

        return view('ucreds.manifest', [
            'o' => $o,
            'rows' => $rows,
            'ref' => $ref,
            'template' => 'enrol template ' . Template::VERSION,
        ]);
    }

    // "Check file": store, hash, validate. Nothing becomes a result yet.
    public function check(Request $request)
    {
        $d = $request->validate(['offering_id' => 'required|integer', 'kind' => 'required|in:results,badges', 'file' => 'required|file|mimes:csv,txt|max:5120']);
        $o = $this->offering((int) $d['offering_id']);
        $content = file_get_contents($request->file('file')->getRealPath());
        $sha = hash('sha256', $content);
        $result = $this->validator->check($d['kind'], $content, $o);
        $dup = DB::table('import_files')->where('sha256', $sha)->whereIn('status', ['accepted', 'partly_accepted'])->orderByDesc('id')->first();
        $fileError = $dup ? 'This file was already imported on '.\Carbon\Carbon::parse($dup->staged_at)->format('d/m/Y H:i').' (import '.$dup->id.'). The same file is not imported twice.' : $result['file_error'];
        $path = "ucreds/imports/$sha.csv";
        Storage::disk('local')->put($path, $content); // kept for the retention period (FR-116)

        $id = DB::transaction(function () use ($d, $o, $request, $sha, $path, $result, $dup, $fileError) {
            $id = DB::table('import_files')->insertGetId([
                'kind' => $d['kind'], 'offering_id' => $o->id, 'template_version' => Template::VERSION, 'original_name' => $request->file('file')->getClientOriginalName(), 'path' => $path, 'sha256' => $sha,
                'row_count' => $result['total'], 'staged_count' => 0, 'rejected_count' => count($result['errors']), 'duplicate_of_id' => $dup->id ?? null, 'run_by' => $request->user()->id,
                'status' => $fileError ? 'rejected' : 'checked', 'file_error' => $fileError, 'created_at' => now(),
            ]);
            foreach ($result['errors'] as $e) {
                DB::table('import_row_errors')->insert(['import_file_id' => $id] + $e);
            }
            $this->audit->record($fileError ? 'import.rejected' : 'import.checked', ['type' => 'ImportFile', 'id' => $id], after: ['rows' => $result['total'], 'ready' => count($result['rows']), 'rejected' => count($result['errors'])], reason: $fileError); // EV-24, EV-26

            return $id;
        });

        return redirect()->route('ucreds.report', $id);
    }

    public function report(int $id)
    {
        $f = DB::table('import_files as f')->join('offerings as o', 'o.id', '=', 'f.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->where('f.id', $id)->first(['f.*', 'o.cohort_name', 'v.name_en']) ?? abort(404);

        return view('ucreds.report', ['f' => $f, 'rowErrs' => DB::table('import_row_errors')->where('import_file_id', $id)->orderBy('line')->get(), 'ready' => max(0, $f->row_count - $f->rejected_count)]);
    }

    // DOC-14 as a CSV the person can download and fix (FR-113).
    public function errors(int $id)
    {
        DB::table('import_files')->find($id) ?? abort(404);
        $csv = "line,learner_id,message\r\n";
        foreach (DB::table('import_row_errors')->where('import_file_id', $id)->orderBy('line')->get() as $e) {
            $csv .= implode(',', [$e->line, $this->field((string) $e->learner_id), $this->field($e->message)])."\r\n";
        }

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"import-$id-errors.csv\""]);
    }

    // JB-02 ImportResultsJob, run in the request until the queue workers arrive with Redis: only the good rows are staged.
    public function stage(Request $request, int $id)
    {
        $f = DB::table('import_files')->find($id) ?? abort(404);
        abort_unless($f->status === 'checked', 403, 'Only a checked file can be staged.');
        $o = $this->offering($f->offering_id);
        $result = $this->validator->check($f->kind, Storage::disk('local')->get($f->path), $o); // the cohort may have changed since the check
        abort_if($result['file_error'], 403, $result['file_error']);

        DB::transaction(function () use ($f, $o, $result, $request) {
            $staged = $f->kind === 'results' ? $this->stageResults($f, $o, $result['rows'], $request->user()) : $this->stageBadges($o, $result['rows']);
            DB::table('import_row_errors')->where('import_file_id', $f->id)->delete();
            foreach ($result['errors'] as $e) {
                DB::table('import_row_errors')->insert(['import_file_id' => $f->id] + $e);
            }
            DB::table('import_files')->where('id', $f->id)->update(['staged_count' => $staged, 'rejected_count' => count($result['errors']), 'row_count' => $result['total'], 'status' => $result['errors'] ? 'partly_accepted' : 'accepted', 'staged_at' => now()]);
            $this->audit->record($f->kind === 'results' ? 'results.staged' : 'badges.imported', ['type' => 'ImportFile', 'id' => $f->id], after: ['staged' => $staged, 'rejected' => count($result['errors'])]); // EV-25, EV-27
            $this->tellLecturer($o, $f->kind, $staged, $result['errors']);
        });

        return redirect()->route('ucreds.report', $id)->with('status', 'Staged '.count($result['rows']).' rows. Nothing becomes an achievement until the deputy dean verifies the results.');
    }

    // ---- helpers

    /** Imported results are Provisional and create no achievement, badge or credential (FR-114, BR-20). */
    private function stageResults(object $f, object $o, array $rows, User $by): int
    {
        $components = Template::components($o->mc_version_id);
        foreach ($rows as $row) {
            foreach ($components as $c) {
                $existing = DB::table('assessment_results')->where('enrolment_id', $row['_enrolment_db_id'])->where('assessment_component_id', $c->id)->first();
                $values = ['score' => $row[Template::key($c)], 'source' => 'import', 'import_file_id' => $f->id, 'entered_by' => $by->id, 'updated_at' => now()];
                $existing
                    ? DB::table('assessment_results')->where('id', $existing->id)->update($values)
                    : DB::table('assessment_results')->insert($values + ['enrolment_id' => $row['_enrolment_db_id'], 'assessment_component_id' => $c->id, 'status' => 'provisional', 'created_at' => now()]);
            }
            DB::table('enrolments')->where('id', $row['_enrolment_db_id'])->update(['progress_percent' => (int) $row['progress_percent'], 'modules_completed' => (int) $row['modules_completed']]);
        }
        if ($rows) {
            Tasks::open('lecturer', 'results_entry', $o->id, 'Complete the results: '.$o->name_en.' '.$o->cohort_name, "/offerings/{$o->id}/results", userId: $o->owner_id, area: 'results');
        }

        return count($rows);
    }

    private function stageBadges(object $o, array $rows): int
    {
        foreach ($rows as $row) {
            $moduleId = DB::table('mc_modules')->where('mc_version_id', $o->mc_version_id)->where('seq', (int) $row['module_no'])->value('id');
            $ach = DB::table('module_achievements')->where('enrolment_id', $row['_enrolment_db_id'])->where('mc_module_id', $moduleId)->value('id');
            DB::table('digital_badges')->where('module_achievement_id', $ach)->update(['external_id' => $row['badge_id'], 'external_url' => $row['badge_url'], 'external_date' => $row['badge_date']]);
        }

        return count($rows);
    }

    // The lecturer is told which marks are missing (FL-07).
    private function tellLecturer(object $o, string $kind, int $staged, array $errors): void
    {
        $owner = User::find($o->owner_id);
        $detail = $errors ? count($errors).' rows were rejected: '.collect($errors)->take(6)->map(fn ($e) => 'line '.$e['line'].' ('.($e['learner_id'] ?? '?').'): '.$e['message'])->implode(' ') : 'No row was rejected.';
        $this->notifier->send($owner, 'results.staged', ['name' => $owner->name, 'title' => $o->name_en.' '.$o->cohort_name, 'staged' => $staged, 'kind' => $kind, 'detail' => $detail], dedupe: 'staged-'.$o->id.'-'.now()->timestamp);
    }

    private function offerings()
    {
        return DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->whereIn('o.status', ['running', 'ended', 'open', 'ready'])->orderByDesc('o.start_date')
            ->select('o.id', 'o.cohort_name', 'o.status', 'v.name_en');
    }

    private function offering(int $id): object
    {
        return DB::table('offerings as o')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')->where('o.id', $id)
            ->first(['o.*', 'v.name_en', 'm.owner_id']) ?? abort(404);
    }

    private function field(string $v): string
    {
        return preg_match('/[",\r\n]/', $v) ? '"'.str_replace('"', '""', $v).'"' : $v;
    }
}
