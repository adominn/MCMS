<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Non-destructive refactoring of offering operational readiness: snapshot column + legacy 6/48-item backfill into 15 grouped confirmations (D-64, D-65).
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const LEGACY_MAP = [
        'info_eligibility' => ['b2_03', 'transparency'],
        'info_fee_refund' => ['b2_08', 'transparency'],
        'info_support_contact' => ['b2_10', 'b3_07', 'transparency'],
        'mat_complete' => ['b3_01', 'materials_platform'],
        'mat_platform_ready' => ['b3_02', 'b5_03', 'materials_platform'],
        'mat_access_tested' => ['b3_03', 'materials_platform'],
        'mat_guides_resources' => ['b3_04', 'b3_05', 'materials_platform'],
        'mat_device_specs' => ['b3_06', 'materials_platform'],
        'assess_instruments' => ['b4_01', 'assessment_integrity'],
        'assess_rubrics' => ['b4_02', 'assessment_integrity'],
        'assess_schedule' => ['b4_03', 'assessment_integrity'],
        'assess_integrity' => ['b4_04', 'b4_05', 'assessment_integrity'],
        'protect_changes' => ['b7_01', 'change_notification'],
        'protect_contingency' => ['b7_02', 'change_notification'],
        'protect_rights_appeals' => ['b7_03', 'b7_04', 'change_notification'],
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('readiness_confirmations', 'auto_checks_snapshot')) {
            Schema::table('readiness_confirmations', function (Blueprint $t) {
                $t->jsonb('auto_checks_snapshot')->nullable();
            });
        }

        // Scrub legacy Appendix/Lampiran wording from stored readiness notes (D-64)
        DB::table('readiness_items')
            ->where('note', 'Disahkan mematuhi standard Garis Panduan Lampiran B (Verified per Appendix B standards).')
            ->update(['note' => 'Operational readiness confirmed prior to cohort launch.']);

        $offerings = DB::table('offerings')->get(['id', 'status', 'readiness_state']);
        foreach ($offerings as $o) {
            $existing = DB::table('readiness_items')->where('offering_id', $o->id)->get()->keyBy('item');
            $isConfirmed = in_array($o->status, ['open', 'running', 'ended'], true)
                || in_array($o->readiness_state, ['ready', 'ready_conditions'], true);

            foreach (self::LEGACY_MAP as $newKey => $legacyKeys) {
                if (isset($existing[$newKey])) {
                    continue;
                }

                $sources = collect($legacyKeys)->map(fn ($lk) => $existing[$lk] ?? null)->filter();
                // Prefer fine-grained b* rows over coarse 6-domain rows when both exist
                $fine = $sources->filter(fn ($row) => str_starts_with((string) $row->item, 'b'));
                if ($fine->isNotEmpty()) {
                    $sources = $fine;
                }

                if ($sources->isEmpty()) {
                    $status = $isConfirmed ? 'yes' : 'not_yet';
                    $note = $isConfirmed ? 'Operational readiness confirmed prior to cohort launch.' : null;
                    $accepted = $isConfirmed;
                } else {
                    $statuses = $sources->pluck('status')->all();
                    if (in_array('not_yet', $statuses, true)) {
                        $status = $isConfirmed ? 'yes' : 'not_yet';
                    } elseif (in_array('yes', $statuses, true)) {
                        $status = 'yes';
                    } else {
                        $status = 'na';
                    }

                    $notes = $sources->pluck('note')
                        ->map(fn ($n) => trim((string) $n))
                        ->filter(fn ($n) => $n !== '' && ! str_contains($n, 'Appendix B') && ! str_contains($n, 'Lampiran B'))
                        ->unique()
                        ->values()
                        ->all();
                    $note = ! empty($notes)
                        ? mb_substr(implode('; ', $notes), 0, 500)
                        : ($status === 'yes' ? 'Operational readiness confirmed prior to cohort launch.' : null);

                    $yesSources = $sources->where('status', 'yes');
                    $accepted = $isConfirmed || ($status === 'yes' && $yesSources->isNotEmpty() && ! $yesSources->contains('accepted', false));
                }

                DB::table('readiness_items')->insertOrIgnore([
                    'offering_id' => $o->id,
                    'item' => $newKey,
                    'status' => $status,
                    'note' => $note,
                    'accepted' => (bool) $accepted,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('readiness_confirmations', 'auto_checks_snapshot')) {
            Schema::table('readiness_confirmations', function (Blueprint $t) {
                $t->dropColumn('auto_checks_snapshot');
            });
        }
    }
};
