<?php

declare(strict_types=1);

namespace App\Modules\Learning;

use Illuminate\Support\Facades\DB;

// FR-113: file-level errors reject the whole file; row-level errors are listed and the good rows can be staged.
final class ImportValidator
{
    /**
     * @return array{file_error: ?string, rows: list<array<string,mixed>>, errors: list<array{line:int, learner_id:?string, message:string}>, total: int}
     */
    public function check(string $kind, string $content, object $offering): array
    {
        $out = ['file_error' => null, 'rows' => [], 'errors' => [], 'total' => 0];
        if (! in_array($offering->status, ['running', 'ended'], true)) {
            return ['file_error' => 'Results and badges are imported for a cohort that is running or ended. This one is '.config('offerings.status')[$offering->status].'.'] + $out;
        }
        if (! mb_check_encoding($content, 'UTF-8')) {
            return ['file_error' => 'The file is not valid UTF-8.'] + $out;
        }
        $content = ltrim($content, "\xEF\xBB\xBF");
        $h = fopen('php://temp', 'r+');
        fwrite($h, $content);
        rewind($h);
        $head = fgetcsv($h);
        if (! $head || $head === [null]) {
            return ['file_error' => 'The file is empty. It needs a header row.'] + $out;
        }
        $head = array_map(fn ($c) => trim((string) $c), $head);
        $need = $kind === 'results' ? Template::results($offering->mc_version_id) : Template::BADGES;
        $missing = array_values(array_diff($need, $head));
        $extra = array_values(array_diff($head, $need));
        if ($missing || $extra || count($head) !== count(array_unique($head))) {
            $why = [];
            foreach ($missing as $c) {
                $why[] = "column $c is missing";
            }
            foreach ($extra as $c) {
                $why[] = "column $c is not part of the template";
            }
            if (count($head) !== count(array_unique($head))) {
                $why[] = 'a column appears twice';
            }

            return ['file_error' => ucfirst(implode('; ', $why)).". This import needs template $kind ".Template::VERSION.' with the columns: '.implode(', ', $need).'.'] + $out;
        }

        $ref = Template::offeringRef($offering->id);
        $enrolments = DB::table('enrolments as e')->join('users as u', 'u.id', '=', 'e.user_id')->where('e.offering_id', $offering->id)->whereIn('e.status', ['enrolled', 'completed', 'not_completed'])
            ->get(['e.id', 'e.reference', 'u.institution_id'])->keyBy('reference');
        $components = Template::components($offering->mc_version_id)->keyBy(fn ($c) => Template::key($c));
        $moduleCount = DB::table('mc_modules')->where('mc_version_id', $offering->mc_version_id)->count();
        $seen = [];

        for ($line = 2; ($r = fgetcsv($h)) !== false; $line++) {
            if ($r === [null]) {
                continue;
            }
            $out['total']++;
            if (count($r) !== count($head)) {
                $out['errors'][] = ['line' => $line, 'learner_id' => null, 'message' => 'The row has '.count($r).' columns; the header has '.count($head).'.'];

                continue;
            }
            $row = array_combine($head, array_map(fn ($v) => trim((string) $v), $r));
            $err = [];
            $e = $enrolments[$row['enrolment_id']] ?? null;
            if ($row['learner_id'] === '' || ! DB::table('users')->where('institution_id', $row['learner_id'])->exists()) {
                $err[] = 'unknown matric number';
            } elseif (! $e || $e->institution_id !== $row['learner_id']) {
                $err[] = 'enrolment_id does not belong to this learner in this cohort';
            }
            if ($kind === 'results') {
                $row['offering'] !== $ref && $err[] = "offering must be $ref";
                $this->whole($row['progress_percent'], 0, 100) ?: $err[] = 'progress_percent must be a whole number from 0 to 100';
                $this->whole($row['modules_completed'], 0, $moduleCount) ?: $err[] = "modules_completed must be a whole number from 0 to $moduleCount";
                foreach ($components as $key => $c) {
                    $v = $row[$key];
                    if ($v === '') {
                        $err[] = "$key is empty";
                    } elseif (! preg_match('/^\d+(\.\d{1,2})?$/', $v)) {
                        $err[] = "$key is not a number with at most two decimals";
                    } elseif ((float) $v > (float) $c->max_mark) {
                        $err[] = "$key is out of range (0 to ".rtrim(rtrim((string) $c->max_mark, '0'), '.').')';
                    }
                }
                if ($e && DB::table('assessment_results')->where('enrolment_id', $e->id)->where('status', 'verified')->exists()) {
                    $err[] = 'results are already verified; use a result change request';
                }
            } else {
                $this->whole($row['module_no'], 1, $moduleCount) ?: $err[] = "module_no must be a whole number from 1 to $moduleCount";
                $row['badge_id'] === '' || strlen($row['badge_id']) > 60 ? $err[] = 'badge_id is required (60 characters at most)' : null;
                str_starts_with($row['badge_url'], 'https://') && filter_var($row['badge_url'], FILTER_VALIDATE_URL) ?: $err[] = 'badge_url must be an https link';
                $d = \DateTime::createFromFormat('Y-m-d', $row['badge_date']);
                $d && $d->format('Y-m-d') === $row['badge_date'] ?: $err[] = 'badge_date must be an ISO 8601 date (YYYY-MM-DD)';
                if ($e && $this->whole($row['module_no'], 1, $moduleCount) && ! $this->achieved($e->id, $offering->mc_version_id, (int) $row['module_no'])) {
                    $err[] = 'no verified achievement for this module: the badge is flagged and not accepted';
                }
            }
            $dupKey = $row['enrolment_id'].($kind === 'badges' ? '#'.$row['module_no'] : '');
            if (isset($seen[$dupKey])) {
                $err[] = "duplicate of line {$seen[$dupKey]}";
            }
            $seen[$dupKey] ??= $line;

            if ($err) {
                $out['errors'][] = ['line' => $line, 'learner_id' => $row['learner_id'] ?: null, 'message' => implode('; ', $err).'.'];
            } else {
                $out['rows'][] = $row + ['_line' => $line, '_enrolment_db_id' => $e->id];
            }
        }
        fclose($h);

        return $out;
    }

    private function whole(string $v, int $min, int $max): bool
    {
        return preg_match('/^\d+$/', $v) === 1 && (int) $v >= $min && (int) $v <= $max;
    }

    private function achieved(int $enrolmentId, int $versionId, int $moduleNo): bool
    {
        $m = DB::table('mc_modules')->where('mc_version_id', $versionId)->where('seq', $moduleNo)->value('id');

        return $m && DB::table('module_achievements')->where('enrolment_id', $enrolmentId)->where('mc_module_id', $m)->exists();
    }
}
