<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Modules\Notifications\Adapters\EmailAdapter;
use App\Modules\Records\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

// MD-16 integration hub: calls to external systems are logged, safe to repeat, retried with growing delay,
// and never block the business process that made them (FR-270, FR-271, IR-17).
final class IntegrationHub
{
    public const DELAYS_MINUTES = [1, 5, 30]; // after attempts 1, 2 and 3; a fourth failure ends the retries

    public function __construct(private AuditRecorder $audit) {}

    public function send(string $adapter, string $operation, string $reference, array $payload = []): object
    {
        if ($existing = DB::table('integration_calls')->where('adapter', $adapter)->where('external_ref', $reference)->first()) {
            return $existing; // repeated request: no second call, no second record
        }
        $id = DB::table('integration_calls')->insertGetId([
            'adapter' => $adapter, 'operation' => $operation, 'external_ref' => $reference,
            'payload' => json_encode($payload), 'status' => 'pending', 'created_at' => now(),
        ]);

        return $this->attempt($id);
    }

    /** Logs a call that was made inline, so the adapters page shows its last run and errors. The payload holds no secrets. */
    public function record(string $adapter, string $operation, string $reference, bool $ok, ?string $error, array $payload = []): void
    {
        DB::table('integration_calls')->updateOrInsert(['adapter' => $adapter, 'external_ref' => $reference], [
            'operation' => $operation, 'payload' => json_encode($payload), 'status' => $ok ? 'ok' : 'failed', 'attempts' => 1, 'last_error' => $error, 'next_attempt_at' => null, 'last_attempt_at' => now(), 'created_at' => now(),
        ]);
    }

    public function attempt(int $id): object
    {
        $c = DB::table('integration_calls')->find($id);
        $attempts = $c->attempts + 1;
        $payload = json_decode($c->payload, true);
        try {
            $this->handler($c->adapter, $c->operation)($payload);
            $upd = ['status' => 'ok', 'last_error' => null, 'next_attempt_at' => null];
            if (isset($payload['notification_id'])) {
                DB::table('notifications')->where('id', $payload['notification_id'])->update(['status' => 'sent']);
            }
        } catch (Throwable $e) {
            $final = $attempts > count(self::DELAYS_MINUTES);
            $upd = [
                'status' => $final ? 'failed' : 'retrying', 'last_error' => Str::limit($e->getMessage(), 300),
                'next_attempt_at' => $final ? null : now()->addMinutes(self::DELAYS_MINUTES[$attempts - 1]),
            ];
            if (isset($payload['notification_id'])) {
                DB::table('notifications')->where('id', $payload['notification_id'])->update(['status' => $final ? 'failed' : 'queued']);
            }
            if ($final) {
                $this->audit->record('integration.call_failed', ['type' => 'IntegrationCall', 'id' => $id], after: ['adapter' => $c->adapter, 'error' => $upd['last_error']], actorId: null);
            }
        }
        DB::table('integration_calls')->where('id', $id)->update($upd + ['attempts' => $attempts, 'last_attempt_at' => now()]);

        return DB::table('integration_calls')->find($id);
    }

    // Run every minute by the scheduler.
    public function retryDue(): int
    {
        $ids = DB::table('integration_calls')->where('status', 'retrying')->where('next_attempt_at', '<=', now())->pluck('id');
        $ids->each(fn ($id) => $this->attempt($id));

        return $ids->count();
    }

    // A person in charge resolves a call that still failed (FR-271).
    public function retryNow(int $id): object
    {
        return $this->attempt($id);
    }

    public function status(): array
    {
        $out = [];
        foreach (config('integrations.modes') as $adapter => $mode) {
            $calls = DB::table('integration_calls')->where('adapter', $adapter);
            $out[$adapter] = [
                'label' => config("integrations.labels.$adapter"), 'mode' => $mode,
                'last_run' => (clone $calls)->max('last_attempt_at'),
                'errors' => (clone $calls)->whereNotNull('last_error')->orderByDesc('last_attempt_at')->limit(3)->pluck('last_error')->all(),
            ];
        }

        return $out;
    }

    private function handler(string $adapter, string $operation): callable
    {
        return match ("$adapter.$operation") {
            'email.send' => fn (array $p) => app(EmailAdapter::class)->send($p['to'], $p['subject'], $p['body']),
            default => throw new \InvalidArgumentException("No handler for $adapter.$operation"),
        };
    }
}
