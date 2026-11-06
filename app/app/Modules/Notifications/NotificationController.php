<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class NotificationController
{
    public function index(Request $request)
    {
        $show = in_array($request->query('show'), ['unread', 'read'], true) ? $request->query('show') : 'all';
        $all = DB::table('notifications')->where('user_id', $request->user()->id)->where('channel', 'in_system')->orderByDesc('id')->limit(200)->get();
        $emailed = DB::table('notifications')->where('user_id', $request->user()->id)->where('channel', 'email')->whereIn('status', ['sent', 'queued'])->pluck('subject')->flip();

        return view('notifications.index', [
            'show' => $show, 'all' => $all, 'user' => $request->user(), 'emailed' => $emailed,
            'items' => DB::table('notifications')->where('user_id', $request->user()->id)->where('channel', 'in_system')->orderByDesc('id')->limit(50)->get(),
            'optional' => DB::table('templates')->where('kind', 'notification')->orderBy('key')->get(),
            'off' => DB::table('notification_preferences')->where('user_id', $request->user()->id)->where('enabled', false)->pluck('template_key')->all(),
        ]);
    }

    // A click on a notice marks it read and goes to where its action is done.
    public function open(Request $request, int $id)
    {
        $n = DB::table('notifications')->where('id', $id)->where('user_id', $request->user()->id)->first() ?? abort(404);
        DB::table('notifications')->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);

        return redirect(Notifier::linkFor($n->template_key, $request->user()));
    }

    public function readAll(Request $request)
    {
        DB::table('notifications')->where('user_id', $request->user()->id)->where('channel', 'in_system')->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'All notices are marked as read.');
    }

    public function read(Request $request, int $id)
    {
        DB::table('notifications')->where('id', $id)->where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    // Mandatory notices cannot be switched off (FR-267).
    public function preferences(Request $request)
    {
        $on = (array) $request->input('on', []);
        $tasksOn = $request->has('tasks') ? $request->boolean('tasks') : in_array('reviewer.assigned', $on, true);
        $remindersOn = $request->has('reminders') ? $request->boolean('reminders') : in_array('reviewer.reminder', $on, true);
        $digestOn = $request->boolean('digest', false);

        $taskKeys = ['proposal.submitted', 'reviewer.assigned', 'reviewer.declined', 'cqi.assigned'];
        $reminderKeys = ['reviewer.reminder', 'cqi.reminder'];

        foreach (DB::table('templates')->where('kind', 'notification')->where('mandatory', false)->pluck('key') as $key) {
            $isEnabled = in_array($key, $on, true);
            if ($request->has('tasks') && in_array($key, $taskKeys, true)) {
                $isEnabled = $tasksOn;
            }
            if ($request->has('reminders') && in_array($key, $reminderKeys, true)) {
                $isEnabled = $remindersOn;
            }
            DB::table('notification_preferences')->upsert([['user_id' => $request->user()->id, 'template_key' => $key, 'enabled' => $isEnabled]], ['user_id', 'template_key'], ['enabled']);
        }

        if ($request->has('digest')) {
            DB::table('notification_preferences')->upsert([['user_id' => $request->user()->id, 'template_key' => 'notification.daily_digest', 'enabled' => $digestOn]], ['user_id', 'template_key'], ['enabled']);
        }

        return redirect(route('profile').'#notif')->with('status', 'Notification preferences saved.');
    }

    public function log()
    {
        return view('admin.notification-log', ['rows' => DB::table('notifications as n')->join('users as u', 'u.id', '=', 'n.user_id')->orderByDesc('n.id')->limit(100)
            ->get(['n.id', 'n.created_at', 'u.name', 'n.template_key', 'n.channel', 'n.locale', 'n.status'])]);
    }
}
