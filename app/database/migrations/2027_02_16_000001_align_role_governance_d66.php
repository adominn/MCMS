<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Decision D-66: non-destructive governance alignment across proposals, credit transfers, stackable pathways, disposal, and legacy open tasks.
return new class extends Migration
{
    public function up(): void
    {
        // 1. Widen approval_decisions.stage cleanly to support 'codl_verification' (17 chars; previously varchar(12)).
        Schema::table('approval_decisions', function (Blueprint $table) {
            $table->string('stage', 30)->change();
        });

        // 2. Backup metadata table for safe, lossless down() rollback.
        if (! Schema::hasTable('governance_migration_d66_backups')) {
            Schema::create('governance_migration_d66_backups', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 30);
                $table->unsignedBigInteger('target_id');
                $table->text('payload')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        // 3. Append new versioned setting values when settings have already been seeded (never mutate existing setting_versions).
        if (Schema::hasTable('settings') && Schema::hasTable('setting_versions') && DB::table('settings')->exists()) {
            $targetSettings = [
                'approval.route.senate' => 'academic_authority',
                'approval.route.transfer' => 'codl_admin',
                'approval.route.stackable' => 'academic_authority',
                'approval.route.disposal' => 'academic_authority',
            ];

            foreach ($targetSettings as $key => $targetVal) {
                $setting = DB::table('settings')->where('key', $key)->first();
                if (! $setting) {
                    continue;
                }

                $latest = DB::table('setting_versions')
                    ->where('setting_id', $setting->id)
                    ->where('effective_from', '<=', now())
                    ->orderByDesc('effective_from')
                    ->orderByDesc('id')
                    ->first();

                $currentVal = $latest ? json_decode((string) $latest->value, true) : null;
                if ($currentVal === $targetVal) {
                    continue;
                }

                $newVersionId = DB::table('setting_versions')->insertGetId([
                    'setting_id' => $setting->id,
                    'value' => json_encode($targetVal),
                    'effective_from' => now(),
                    'set_by' => null,
                    'reason' => 'D-66 role governance alignment',
                    'supersedes_id' => $latest?->id,
                ]);

                DB::table('governance_migration_d66_backups')->insert([
                    'kind' => 'setting_version',
                    'target_id' => $newVersionId,
                    'payload' => json_encode(['setting_key' => $key, 'previous_value' => $currentVal]),
                    'created_at' => now(),
                ]);
            }
        }

        // 4. Reassign open legacy tasks away from deputy_dean without touching completed audit history.
        if (Schema::hasTable('tasks')) {
            $openTransfers = DB::table('tasks')
                ->whereNull('completed_at')
                ->where('assignee_role', 'deputy_dean')
                ->where('kind', 'transfer_decide')
                ->get(['id', 'assignee_role', 'kind', 'title']);

            foreach ($openTransfers as $t) {
                DB::table('governance_migration_d66_backups')->insert([
                    'kind' => 'task',
                    'target_id' => $t->id,
                    'payload' => json_encode(['assignee_role' => $t->assignee_role, 'kind' => $t->kind, 'title' => $t->title]),
                    'created_at' => now(),
                ]);
                DB::table('tasks')->where('id', $t->id)->update([
                    'assignee_role' => 'codl_admin',
                    'kind' => 'transfer_eval',
                ]);
            }

            $openCustodianTasks = DB::table('tasks')
                ->whereNull('completed_at')
                ->where('assignee_role', 'deputy_dean')
                ->whereIn('kind', ['acr_confirm', 'discrepancy_fix', 'credential_correction'])
                ->get(['id', 'assignee_role', 'kind', 'title']);

            foreach ($openCustodianTasks as $t) {
                DB::table('governance_migration_d66_backups')->insert([
                    'kind' => 'task',
                    'target_id' => $t->id,
                    'payload' => json_encode(['assignee_role' => $t->assignee_role, 'kind' => $t->kind, 'title' => $t->title]),
                    'created_at' => now(),
                ]);
                DB::table('tasks')->where('id', $t->id)->update([
                    'assignee_role' => 'records_custodian',
                ]);
            }
        }

        // 5. Normalize any orphan mc_versions with status='pending_approval' -> 'endorsed'.
        if (Schema::hasTable('mc_versions')) {
            $orphans = DB::table('mc_versions')->where('status', 'pending_approval')->get(['id', 'status']);
            foreach ($orphans as $v) {
                DB::table('governance_migration_d66_backups')->insert([
                    'kind' => 'mc_version',
                    'target_id' => $v->id,
                    'payload' => json_encode(['status' => $v->status]),
                    'created_at' => now(),
                ]);
                DB::table('mc_versions')->where('id', $v->id)->update([
                    'status' => 'endorsed',
                    'updated_at' => now(),
                ]);
            }

            // Reassign any open deputy_dean proposal task whose version is already endorsed/codl_verified.
            $endorsedIds = DB::table('mc_versions')->where('status', 'endorsed')->pluck('id')->all();
            if (! empty($endorsedIds)) {
                $staleEndorsedTasks = DB::table('tasks')
                    ->whereNull('completed_at')
                    ->where('assignee_role', 'deputy_dean')
                    ->whereIn('kind', ['proposal_decide', 'decide'])
                    ->whereIn('ref_id', $endorsedIds)
                    ->get(['id', 'assignee_role', 'kind', 'title']);
                foreach ($staleEndorsedTasks as $t) {
                    DB::table('governance_migration_d66_backups')->insert([
                        'kind' => 'task',
                        'target_id' => $t->id,
                        'payload' => json_encode(['assignee_role' => $t->assignee_role, 'kind' => $t->kind, 'title' => $t->title]),
                        'created_at' => now(),
                    ]);
                    DB::table('tasks')->where('id', $t->id)->update([
                        'assignee_role' => 'codl_admin',
                        'kind' => 'codl_verify',
                        'title' => str_replace(['Record Approval Decision:', 'Decide on Micro-Credential Proposal:'], 'Verify endorsed proposal:', (string) $t->title),
                    ]);
                }
            }
        }

        // 6. Append updated proposal.endorsed template versions if already seeded.
        if (Schema::hasTable('templates') && Schema::hasTable('template_versions')) {
            $tpl = DB::table('templates')->where('key', 'proposal.endorsed')->first();
            if ($tpl) {
                foreach ([
                    'en' => ['Endorsed: {title}', 'Hello {name}, "{title}" has been endorsed by the Deputy Dean and forwarded to CODL for verification and Senate approval.'],
                    'ms' => ['Disokong: {title}', 'Salam {name}, "{title}" telah disokong oleh Timbalan Dekan dan dimajukan kepada CODL untuk semakan serta kelulusan Senat.'],
                ] as $loc => [$subj, $body]) {
                    $latest = DB::table('template_versions')->where('template_id', $tpl->id)->where('locale', $loc)->orderByDesc('version')->first();
                    if ($latest && $latest->body !== $body) {
                        $tvId = DB::table('template_versions')->insertGetId([
                            'template_id' => $tpl->id,
                            'locale' => $loc,
                            'version' => ((int) $latest->version) + 1,
                            'subject' => $subj,
                            'body' => $body,
                            'set_by' => null,
                            'reason' => 'D-66 role governance alignment',
                            'created_at' => now(),
                        ]);
                        DB::table('governance_migration_d66_backups')->insert([
                            'kind' => 'template_version',
                            'target_id' => $tvId,
                            'payload' => json_encode(['locale' => $loc]),
                            'created_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('governance_migration_d66_backups')) {
            $backups = DB::table('governance_migration_d66_backups')->orderByDesc('id')->get();
            foreach ($backups as $b) {
                $data = json_decode((string) $b->payload, true) ?: [];
                if ($b->kind === 'setting_version' && Schema::hasTable('setting_versions')) {
                    DB::table('setting_versions')->where('id', $b->target_id)->delete();
                } elseif ($b->kind === 'template_version' && Schema::hasTable('template_versions')) {
                    DB::table('template_versions')->where('id', $b->target_id)->delete();
                } elseif ($b->kind === 'task' && Schema::hasTable('tasks')) {
                    $restore = [
                        'assignee_role' => $data['assignee_role'] ?? 'deputy_dean',
                        'kind' => $data['kind'] ?? null,
                    ];
                    if (array_key_exists('title', $data)) {
                        $restore['title'] = $data['title'];
                    }
                    DB::table('tasks')->where('id', $b->target_id)->update($restore);
                } elseif ($b->kind === 'mc_version' && Schema::hasTable('mc_versions')) {
                    DB::table('mc_versions')->where('id', $b->target_id)->update([
                        'status' => $data['status'] ?? 'pending_approval',
                    ]);
                }
            }
            Schema::dropIfExists('governance_migration_d66_backups');
        }

        if (Schema::hasTable('approval_decisions')) {
            $hasLongStage = DB::table('approval_decisions')->whereRaw('LENGTH(stage) > 12')->exists();
            if (! $hasLongStage) {
                Schema::table('approval_decisions', function (Blueprint $table) {
                    $table->string('stage', 12)->change();
                });
            }
        }
    }
};
