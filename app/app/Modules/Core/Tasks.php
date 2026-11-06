<?php

declare(strict_types=1);

namespace App\Modules\Core;

use Illuminate\Support\Facades\DB;

// Tasks that wait on a person or a role; closed when the action they ask for is done (IR-07, FR-71).
final class Tasks
{
    public static function open(string $role, string $kind, int $refId, string $title, string $link, ?int $ptjId = null, ?int $userId = null, string $area = 'proposals'): void
    {
        DB::table('tasks')->insert([
            'assignee_role' => $role, 'area' => $area, 'title' => $title, 'subject_ref' => 'P-'.str_pad((string) $refId, 4, '0', STR_PAD_LEFT),
            'ptj_id' => $ptjId, 'user_id' => $userId, 'kind' => $kind, 'ref_id' => $refId, 'link' => $link, 'created_at' => now(),
        ]);
    }

    public static function close(string $kind, int $refId, ?int $userId = null): void
    {
        DB::table('tasks')->where('kind', $kind)->where('ref_id', $refId)->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->whereNull('completed_at')->update(['completed_at' => now()]);
    }
}
