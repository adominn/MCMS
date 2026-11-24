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

    /** Where a notice leads: always a screen the recipient's active role can actually open (never a 403 dead end). */
    public static function linkFor(string $key, \App\Models\User $to): string
    {
        $dd = $to->hasAnyRole('deputy_dean', 'ptj_authority');
        $lec = $to->hasAnyRole('lecturer');
        $admin = $to->hasAnyRole('codl_admin');
        $senate = $to->hasAnyRole('academic_authority');
        $qa = $to->hasAnyRole('qa_officer');
        $custodian = $to->hasAnyRole('records_custodian', 'records_depositor');
        $depositor = $to->hasAnyRole('abc_depositor', 'records_depositor');
        $expert = $to->hasAnyRole('industry_expert', 'reviewer');
        $student = $to->hasAnyRole('student') && ! ($dd || $lec || $admin || $senate || $qa || $custodian || $depositor || $expert);
        $canApprovals = $dd || $admin || $senate || $qa;
        $canTransfers = $admin || $senate || $dd || $qa;
        $canMonitoring = $lec || $dd || $qa || $admin || $senate;
        $canQuality = $lec || $dd || $qa || $senate;
        $canPathways = $lec || $dd || $senate || $admin;
        $canTrace = $lec || $dd || $senate || $qa || $custodian || $depositor;
        $fallback = $to->currentRole()?->homeRouteName() ?? 'tasks';

        $route = match (true) {
            in_array($key, ['abc.deposited', 'acr.confirmed', 'credit.expired', 'credit.expiring'], true) => $depositor ? 'deposits' : ($student ? 'credit' : ($canTrace ? 'trace' : 'documents')),
            in_array($key, ['completion.confirmed', 'completion.window', 'results.verified'], true) => $student ? 'results.mine' : ($dd ? 'results' : ($lec ? 'offerings' : ($custodian ? 'credentials.admin' : $fallback))),
            $key === 'correction.recorded' => $student ? 'results.mine' : ($canTrace ? 'trace' : 'documents'),
            $key === 'results.staged' => $dd ? 'results' : ($lec ? 'offerings' : $fallback),
            str_starts_with($key, 'credential.') => ($custodian || $dd) ? 'credentials.admin' : ($student ? 'credentials.wallet' : 'documents'),
            str_starts_with($key, 'enrolment.') || $key === 'payment.confirmed' => $student ? 'enrolments' : (($lec || $dd) ? 'offerings' : $fallback),
            $key === 'plan.decided' => $student ? 'plan.mine' : ($canPathways ? 'pathways' : $fallback),
            $key === 'transfer.decided' => $student ? 'transfer' : ($canTransfers ? 'transfers' : $fallback),
            $key === 'transfer.evidence' => $canTransfers ? 'transfers' : ($student ? 'transfer' : 'documents'),
            in_array($key, ['proposal.submitted', 'proposal.endorsed', 'decision.made'], true) && $canApprovals => 'approvals',
            in_array($key, ['proposal.submitted', 'proposal.endorsed', 'proposal.returned', 'change.minor', 'version.changed', 'decision.made'], true) => $canApprovals ? 'approvals' : ($lec ? 'proposals' : $fallback),
            str_starts_with($key, 'review') => ($expert || $lec) ? 'reviews' : ($canApprovals ? 'approvals' : $fallback),
            str_starts_with($key, 'qv.') => $canQuality ? 'quality' : $fallback,
            $key === 'suspension.notice' => $student ? 'documents' : ($canMonitoring ? 'monitoring' : 'documents'),
            str_starts_with($key, 'results.') => $student ? 'results.mine' : ($dd ? 'results' : ($lec ? 'offerings' : $fallback)),
            default => $fallback,
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
