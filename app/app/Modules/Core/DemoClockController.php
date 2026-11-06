<?php

declare(strict_types=1);

namespace App\Modules\Core;

use Illuminate\Http\Request;

// CODL-14: move the demo date forward and run the daily jobs. Not available outside a test environment.
final class DemoClockController
{
    public function __construct(private Settings $settings) {}

    public function show()
    {
        abort_unless(DemoClock::enabled(), 404);

        return view('admin.demo-clock', ['offset' => (int) $this->settings->get('demo.clock_offset_days'), 'today' => today()]);
    }

    public function move(Request $request)
    {
        abort_unless(DemoClock::enabled(), 404);
        $d = $request->validate(['days' => 'required|integer|between:0,4000', 'reset' => 'nullable|boolean']);
        \Carbon\Carbon::setTestNow(); // the setting is stamped with the real time, so that it takes effect at once
        $new = $request->boolean('reset') ? 0 : (int) $this->settings->get('demo.clock_offset_days') + (int) $d['days'];
        $this->settings->set('demo.clock_offset_days', (string) $new, $request->boolean('reset') ? 'Demo clock reset' : 'Demo clock moved forward by '.$d['days'].' days', $request->user());

        return back()->with('status', $request->boolean('reset') ? 'The demo clock is back to the real date.' : 'The demo clock moved forward by '.$d['days'].' days. Run the daily jobs to apply the new date.');
    }

    public function jobs()
    {
        abort_unless(DemoClock::enabled(), 404);
        $r = DemoClock::runDailyJobs();

        return back()->with('status', 'Daily jobs run: '.collect($r)->map(fn ($n, $k) => "$k ($n)")->implode(', ').'.');
    }
}
