<?php

declare(strict_types=1);

namespace App\Modules\Records;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// MD-15: append-only audit trail with a hash chain (FR-254, DR-03, AD-11). The database also refuses UPDATE and DELETE.
final class AuditRecorder
{
    public function record(string $action, Model|array|null $subject = null, array $before = [], array $after = [], ?string $reason = null, array $context = [], ?int $actorId = null): int
    {
        return DB::transaction(function () use ($action, $subject, $before, $after, $reason, $context, $actorId) {
            DB::table('audit_chain_heads')->insertOrIgnore(['chain' => 'main', 'last_seq' => 0, 'last_hash' => str_repeat('0', 64)]);
            $head = DB::table('audit_chain_heads')->where('chain', 'main')->lockForUpdate()->first(); // serialises writers

            [$type, $id] = $subject instanceof Model
                ? [class_basename($subject), (string) $subject->getKey()]
                : [$subject['type'] ?? null, isset($subject['id']) ? (string) $subject['id'] : null];

            $row = [
                'seq' => $head->last_seq + 1,
                'occurred_at' => now('UTC')->format('Y-m-d H:i:s.u'),
                'actor_id' => $actorId ?? Auth::id(),
                'actor_role' => session('active_role'),
                'action' => $action,
                'subject_type' => $type,
                'subject_id' => $id,
                'before' => self::canon($before),
                'after' => self::canon($after),
                'reason' => $reason,
                'context' => self::canon($context),
                'prev_hash' => $head->last_hash,
            ];
            $row['hash'] = self::hash($row);
            DB::table('audit_events')->insert($row);
            DB::table('audit_chain_heads')->where('chain', 'main')->update(['last_seq' => $row['seq'], 'last_hash' => $row['hash']]);

            return $row['seq'];
        });
    }

    // Returns null when the whole chain is intact, else the first sequence number that does not match.
    public function verify(): ?int
    {
        $prev = str_repeat('0', 64);
        $expect = 1;
        foreach (DB::table('audit_events')->orderBy('seq')->get() as $e) {
            $row = (array) $e;
            $row['occurred_at'] = \Carbon\Carbon::parse($row['occurred_at'], 'UTC')->format('Y-m-d H:i:s.u');
            $row['seq'] = (int) $row['seq'];
            if ($row['seq'] !== $expect || $row['prev_hash'] !== $prev || $row['hash'] !== self::hash($row)) {
                return (int) $row['seq'];
            }
            $prev = $row['hash'];
            $expect++;
        }

        return null;
    }

    private static function hash(array $r): string
    {
        $part = fn ($v) => self::canon(json_decode($v, true));

        return hash('sha256', implode('|', [
            $r['prev_hash'], $r['seq'], $r['occurred_at'], $r['actor_id'] ?? '', $r['actor_role'] ?? '', $r['action'],
            $r['subject_type'] ?? '', $r['subject_id'] ?? '', $part($r['before']), $part($r['after']), $r['reason'] ?? '', $part($r['context']),
        ]));
    }

    // Sorted keys so the text is the same before and after the database stores it as jsonb.
    private static function canon(array $v): string
    {
        $sort = function (array $a) use (&$sort): array {
            ksort($a);

            return array_map(fn ($x) => is_array($x) ? $sort($x) : $x, $a);
        };

        return json_encode($sort($v), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
