<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use Illuminate\Support\Facades\DB;

// FR-25: the mode of each adapter, its last run and recent errors, and the calls that still failed.
final class AdapterController
{
    public function __construct(private IntegrationHub $hub) {}

    public function index()
    {
        return view('admin.adapters', ['adapters' => $this->hub->status(), 'failed' => DB::table('integration_calls')->where('status', 'failed')->orderByDesc('id')->get()]);
    }

    public function retry(int $id)
    {
        $r = $this->hub->retryNow($id);

        return back()->with('status', $r->status === 'ok' ? 'The call went through.' : 'The call failed again: '.$r->last_error);
    }
}
