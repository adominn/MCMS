<?php

declare(strict_types=1);

namespace App\Modules\Achievement;

use App\Models\User;
use App\Modules\Core\Settings;
use App\Modules\Notifications\Notifier;
use Illuminate\Support\Facades\DB;

// JB-11 CompletionWindowRemindersJob (FR-133): a modular UMC with staged completion has a window to finish all modules.
// The period is the offering's own, else umc.completion_window_months. With neither (OI-13 is open) no reminder is sent.
final class CompletionWindow
{
    public const DAYS_BEFORE = 30;

    public function __construct(private Settings $settings, private Notifier $notifier) {}

    public function remind(): int
    {
        $default = $this->settings->get('umc.completion_window_months');
        $sent = 0;
        $rows = DB::table('enrolments as e')->join('offerings as o', 'o.id', '=', 'e.offering_id')->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
            ->where('e.status', 'enrolled')->where('o.staged_completion', true)->get(['e.id', 'e.user_id', 'e.created_at', 'o.completion_window_months', 'o.cohort_name', 'v.name_en']);
        foreach ($rows as $r) {
            $months = $r->completion_window_months ?? $default;
            if (! $months) {
                continue;
            }
            $ends = \Carbon\Carbon::parse($r->created_at)->addMonths((int) $months);
            if (now()->lt($ends->copy()->subDays(self::DAYS_BEFORE)) || now()->gt($ends)) {
                continue;
            }
            $u = User::find($r->user_id);
            $this->notifier->send($u, 'completion.window', ['name' => $u->name, 'title' => $r->name_en.' '.$r->cohort_name, 'date' => $ends->format('d/m/Y')], dedupe: "window-{$r->id}");
            $sent++;
        }

        return $sent;
    }
}
