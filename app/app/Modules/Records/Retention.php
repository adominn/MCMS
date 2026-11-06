<?php

declare(strict_types=1);

namespace App\Modules\Records;

use App\Modules\Core\Settings;
use Illuminate\Support\Facades\DB;

// MD-15 retention (FR-retention, FR-hold). Only non-academic logs can be disposed of here; academic records, credentials and the audit trail are never listed (dr-nodelete, OI-07).
final class Retention
{
    /** category => [table, date column, setting key, label] */
    public const CATEGORIES = [
        'notifications' => ['notifications', 'created_at', 'retention.notifications_years', 'Notification log'],
        'report_runs' => ['report_runs', 'ran_at', 'retention.report_runs_years', 'Report run log'],
        'integration_calls' => ['integration_calls', 'created_at', 'retention.integration_years', 'Integration call log'],
    ];

    public function __construct(private Settings $settings, private AuditRecorder $audit) {}

    /** JB-19: what has passed its period and is not held. @return list<array{category: string, label: string, years: int, cutoff: string, count: int, held: bool}> */
    public function candidates(): array
    {
        $out = [];
        foreach (self::CATEGORIES as $k => [$table, $col, $key, $label]) {
            $years = (int) $this->settings->get($key);
            $cutoff = today()->subYears($years)->toDateString();
            $out[] = ['category' => $k, 'label' => $label, 'years' => $years, 'cutoff' => $cutoff, 'count' => DB::table($table)->where($col, '<', $cutoff)->count(), 'held' => $this->held($k)];
        }

        return $out;
    }

    public function held(string $category): bool
    {
        return DB::table('retention_holds')->where('category', $category)->whereNull('lifted_at')->exists();
    }

    /** Disposal is approved, logged, and refused while a hold is on or before the period has passed. The disposal record is the only way a row leaves. */
    public function dispose(string $category, int $approverId): object
    {
        [$table, $col, $key] = self::CATEGORIES[$category] ?? abort(404);
        abort_if($this->held($category), 422, 'A hold is on this category, so nothing can be disposed of.');
        $years = (int) $this->settings->get($key);
        $cutoff = today()->subYears($years)->toDateString();

        return DB::transaction(function () use ($table, $col, $category, $cutoff, $years, $approverId) {
            $n = DB::table($table)->where($col, '<', $cutoff)->delete();
            $id = DB::table('disposal_records')->insertGetId(['category' => $category, 'cutoff' => $cutoff, 'row_count' => $n, 'retention_years' => $years, 'approved_by' => $approverId, 'approved_at' => now()]);
            $this->audit->record('disposal.approved', ['type' => 'DisposalRecord', 'id' => $id], after: ['category' => $category, 'rows' => $n, 'cutoff' => $cutoff], actorId: $approverId); // EV-61

            return DB::table('disposal_records')->find($id);
        });
    }
}
