<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Models\User;
use App\Modules\Core\Templates;
use Illuminate\Support\Facades\DB;

// MD-16: in-system and email notices in the recipient's language from managed templates, each one logged (FR-265 to FR-268).
final class Notifier
{
    public function __construct(private Templates $templates, private IntegrationHub $hub) {}

    /** Where a notice leads: the page where its action is done, for the person who got it (UI/UX 4.4). */
    public static function linkFor(string $key, \App\Models\User $to): string
    {
        $dd = $to->hasAnyRole('deputy_dean');
        $lec = $to->hasAnyRole('lecturer');
        $admin = $to->hasAnyRole('codl_admin');
        $expert = $to->hasAnyRole('industry_expert');
        $student = $to->hasAnyRole('student') && ! $dd && ! $lec && ! $admin && ! $expert;

        $route = match (true) {
            in_array($key, ['abc.deposited', 'acr.confirmed', 'credit.expired', 'credit.expiring'], true) => $dd ? 'deposits' : ($student ? 'credit' : 'documents'),
            in_array($key, ['completion.confirmed', 'completion.window', 'results.verified'], true) => $student ? 'results.mine' : ($dd ? 'results' : 'offerings'),
            $key === 'correction.recorded' => $student ? 'results.mine' : 'trace',
            $key === 'results.staged' => $dd ? 'results' : 'offerings',
            str_starts_with($key, 'credential.') => $dd ? 'credentials.admin' : ($student ? 'credentials.wallet' : 'documents'),
            str_starts_with($key, 'enrolment.') || $key === 'payment.confirmed' => $student ? 'enrolments' : 'offerings',
            $key === 'plan.decided' => $student ? 'plan.mine' : 'pathways',
            $key === 'transfer.decided' => $student ? 'transfer' : 'transfers',
            $key === 'transfer.evidence' => $dd ? 'transfers' : ($student ? 'transfer' : 'documents'),
            in_array($key, ['proposal.submitted', 'proposal.endorsed', 'decision.made'], true) && $dd => 'approvals',
            in_array($key, ['proposal.submitted', 'proposal.endorsed', 'proposal.returned', 'change.minor', 'version.changed', 'decision.made'], true) => $lec ? 'proposals' : ($dd ? 'approvals' : 'profile'),
            str_starts_with($key, 'review') => $expert || $lec ? 'reviews' : ($dd ? 'approvals' : 'profile'),
            str_starts_with($key, 'qv.') => 'quality',
            $key === 'suspension.notice' => $student ? 'documents' : 'monitoring',
            str_starts_with($key, 'results.') => $student ? 'results.mine' : 'results',
            default => 'profile',
        };

        return route($route, [], false);
    }

    public function send(User $to, string $key, array $vars = [], ?string $dedupe = null): void
    {
        $tpl = DB::table('templates')->where('key', $key)->first() ?? throw new \InvalidArgumentException("Unknown template $key");
        if (! $tpl->mandatory && DB::table('notification_preferences')->where('user_id', $to->id)->where('template_key', $key)->where('enabled', false)->exists()) {
            return; // optional notice switched off by the user
        }
        if ($dedupe !== null && DB::table('notifications')->where(['user_id' => $to->id, 'template_key' => $key, 'dedupe_key' => $dedupe])->exists()) {
            return; // the same event is never notified twice (IR-17)
        }
        $locale = $to->locale ?: 'en';
        [$subject, $body] = $this->templates->render($key, $locale, $vars);
        foreach (['in_system', 'email'] as $channel) {
            $id = DB::table('notifications')->insertGetId([
                'user_id' => $to->id, 'template_key' => $key, 'channel' => $channel, 'locale' => $locale, 'subject' => $subject, 'body' => $body,
                'status' => $channel === 'in_system' ? 'sent' : 'queued', 'dedupe_key' => $dedupe, 'created_at' => now(),
            ]);
            if ($channel === 'email') {
                $this->hub->send('email', 'send', "notification-$id", ['to' => $to->email, 'subject' => $subject, 'body' => $body, 'notification_id' => $id]);
            }
        }
    }
}
