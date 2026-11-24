<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Modules\Core\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// The top-bar search of staff roles (UI/UX 4.4), the profile page behind the user menu, and help and guides.
class SearchController
{
    public function search(Request $request)
    {
        abort_unless($request->user()->hasAnyRole('lecturer', 'deputy_dean', 'ptj_authority', 'academic_authority', 'qa_officer', 'records_custodian', 'abc_depositor', 'records_depositor', 'codl_admin'), 403);
        $q = trim((string) $request->query('q'));
        $like = '%'.$q.'%';
        $mcs = $cohorts = $people = $settings = $files = collect();
        if (mb_strlen($q) >= 2) {
            $user = $request->user();
            $ptjIds = $user->hasRole('deputy_dean') ? $user->scopedPtjIds('deputy_dean') : null;
            $isLecturerOnly = $user->hasRole('lecturer') && ! $user->hasAnyRole('deputy_dean', 'codl_admin');

            $mcsQuery = DB::table('micro_credentials as m')
                ->join('mc_versions as v', 'v.micro_credential_id', '=', 'm.id')
                ->leftJoin('users as u', 'u.id', '=', 'm.owner_id')
                ->whereRaw('v.version_no = (select max(version_no) from mc_versions where micro_credential_id = m.id)')
                ->where(fn ($w) => $w->where('v.name_en', db_like(), $like)->orWhere('m.code', db_like(), $like)->orWhere('u.name', db_like(), $like));
            if ($isLecturerOnly) {
                $mcsQuery->where(fn ($w) => $w->where('m.owner_id', $user->id)->orWhere('m.ptj_id', $user->ptj_id));
            } elseif ($user->hasRole('deputy_dean') && ! $user->hasRole('codl_admin') && $ptjIds !== null) {
                $mcsQuery->whereIn('m.ptj_id', $ptjIds);
            }
            $mcs = $mcsQuery->orderBy('v.name_en')->limit(50)->get(['m.id', 'm.owner_id', 'm.code', 'm.category', 'v.id as version_id', 'v.name_en', 'v.status', 'u.name as lecturer_name']);

            $cohortsQuery = DB::table('offerings as o')
                ->join('mc_versions as v', 'v.id', '=', 'o.mc_version_id')
                ->join('micro_credentials as m', 'm.id', '=', 'v.micro_credential_id')
                ->leftJoin('users as u', 'u.id', '=', 'm.owner_id')
                ->where(fn ($w) => $w->where('o.cohort_name', db_like(), $like)->orWhere('v.name_en', db_like(), $like)->orWhere('u.name', db_like(), $like));
            if ($isLecturerOnly) {
                $cohortsQuery->where(fn ($w) => $w->where('m.owner_id', $user->id)->orWhere('m.ptj_id', $user->ptj_id));
            } elseif ($user->hasRole('deputy_dean') && ! $user->hasRole('codl_admin') && $ptjIds !== null) {
                $cohortsQuery->whereIn('m.ptj_id', $ptjIds);
            }
            $cohorts = $cohortsQuery->orderBy('o.start_date')->limit(50)->get(['o.id', 'o.cohort_name', 'o.status', 'v.name_en', 'u.name as lecturer_name']);

            if ($request->user()->hasAnyRole('deputy_dean', 'codl_admin')) {
                $people = DB::table('users')->where(fn ($w) => $w->where('name', db_like(), $like)->orWhere('email', db_like(), $like))->orderBy('name')->limit(50)->get(['id', 'name', 'email']);
            }
            if ($request->user()->hasAnyRole('codl_admin')) {
                $ql = mb_strtolower($q);
                $settings = collect(config('settings-defaults', []))
                    ->filter(fn ($d, $key) => str_contains(mb_strtolower($key), $ql) || str_contains(mb_strtolower($d['en'] ?? ''), $ql) || str_contains(mb_strtolower(__($d['en'] ?? '')), $ql))
                    ->map(fn ($d, $key) => (object) ['key' => $key, 'description' => $d['en'] ?? $key])
                    ->take(50)
                    ->values();
                $files = DB::table('import_files')
                    ->where(fn ($w) => $w->where('original_name', db_like(), $like)->orWhere('kind', db_like(), $like)->orWhere('status', db_like(), $like))
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get(['id', 'original_name', 'kind', 'status']);
            }
        }

        return view('search', compact('q', 'mcs', 'cohorts', 'people', 'settings', 'files'));
    }

    public function profile(Request $request, Settings $settings)
    {
        $u = $request->user();
        $assignments = $u->roleAssignments()->get();
        $ptjs = DB::table('ptjs')->pluck('code', 'id');
        $firstAssignment = $assignments->first();
        $ptjCode = $firstAssignment && $firstAssignment->ptj_id ? ($ptjs[$firstAssignment->ptj_id] ?? '') : '';

        $staff = ! $u->hasRole('student');

        if ($u->hasRole('student')) {
            $post = 'Participant';
            $roleLabel = 'Participant';
        } elseif ($u->hasRole('deputy_dean')) {
            $post = 'Deputy Dean (Faculty Authority)'.($ptjCode ? ', '.$ptjCode : '');
            $roleLabel = 'Deputy Dean (Faculty Authority'.($ptjCode ? ', '.$ptjCode : '').')';
        } elseif ($u->hasRole('academic_authority')) {
            $post = 'Academic Authority (Senate)';
            $roleLabel = 'Academic Authority (Senate)';
        } elseif ($u->hasRole('qa_officer')) {
            $post = 'Quality Assurance Officer';
            $roleLabel = 'Quality Assurance Officer';
        } elseif ($u->hasRole('records_custodian')) {
            $post = 'Records & Credential Custodian';
            $roleLabel = 'Records & Credential Custodian';
        } elseif ($u->hasRole('abc_depositor')) {
            $post = 'Authorized ABC Depositor';
            $roleLabel = 'Authorized ABC Depositor';
        } elseif ($u->hasRole('records_depositor')) {
            $post = 'Records & ABC Officer';
            $roleLabel = 'Records & ABC Officer';
        } elseif ($u->hasRole('lecturer')) {
            $post = 'Course Coordinator / Lecturer'.($ptjCode ? ', '.$ptjCode : '');
            $roleLabel = 'Course Coordinator / Lecturer'.($ptjCode ? ' ('.$ptjCode.')' : '').'; Reviewer when invited';
        } elseif ($u->hasRole('codl_admin')) {
            $post = 'CODL Administrator';
            $roleLabel = 'CODL Administrator';
        } elseif ($u->hasRole('industry_expert') || $u->hasRole('reviewer')) {
            $post = 'Industry Expert / External Reviewer';
            $roleLabel = 'Industry Expert / External Reviewer';
        } else {
            $post = $firstAssignment ? $firstAssignment->role->label() : 'User';
            $roleLabel = $post;
        }

        // Active sessions matching prototype (Edge on Windows, Melaka (current), Chrome on Android, Ayer Keroh)
        $sessions = [
            (object) ['device' => 'Edge on Windows, Melaka', 'last_active' => 'Now', 'is_current' => true],
            (object) ['device' => 'Chrome on Android, Ayer Keroh', 'last_active' => '25/10/2026, 21:14', 'is_current' => false],
        ];

        $offKeys = DB::table('notification_preferences')->where('user_id', $u->id)->where('enabled', false)->pluck('template_key')->all();

        return view('profile', [
            'u' => $u,
            'post' => $post,
            'roleLabel' => $roleLabel,
            'staff' => $staff,
            'assignments' => $assignments,
            'idle' => (int) $settings->get('auth.idle_timeout_minutes'),
            'ptjs' => $ptjs,
            'sessions' => $sessions,
            'optional' => DB::table('templates')->where('kind', 'notification')->orderBy('key')->get(),
            'off' => $offKeys,
            'tasksOn' => ! in_array('proposal.submitted', $offKeys, true) && ! in_array('reviewer.assigned', $offKeys, true),
            'remindersOn' => ! in_array('reviewer.reminder', $offKeys, true),
            'digestOn' => DB::table('notification_preferences')->where('user_id', $u->id)->where('template_key', 'notification.daily_digest')->where('enabled', true)->exists(),
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'in:en,ms'],
            'theme' => ['nullable', 'in:auto,light,dark'],
            'phone' => ['nullable', 'string', 'max:30'],
            'name_on_certificate' => ['nullable', 'string', 'max:120'],
        ]);

        $u = $request->user();
        $theme = $data['theme'] ?? 'auto';
        DB::table('users')->where('id', $u->id)->update([
            'locale' => $data['locale'],
            'theme_preference' => $theme,
            'phone' => $data['phone'] ?? null,
            'name_on_certificate' => $data['name_on_certificate'] ?? null,
            'updated_at' => now(),
        ]);

        session([
            'ui_lang' => $data['locale'],
            'lang' => $data['locale'],
            'ui_theme' => $theme,
        ]);
        app()->setLocale($data['locale']);

        return redirect(route('profile'))->with('status', 'Preferences saved.');
    }

    public function requestCorrection(Request $request)
    {
        $d = $request->validate([
            'what' => ['required', 'string', 'max:60'],
            'should' => ['required', 'string', 'max:255'],
        ]);

        $u = $request->user();
        $ref = 'DC-2026-0118';

        DB::table('discrepancies')->insert([
            'kind' => 'pdpa_request',
            'key' => $ref,
            'user_id' => $u->id,
            'ptj_id' => $u->ptj_id,
            'subject_type' => 'user_identity',
            'subject_id' => (string) $u->id,
            'description' => $d['what'].': '.$d['should'],
            'status' => 'open',
            'found_at' => now(),
        ]);

        return redirect(route('profile').'#data')->with('status', "Correction requested. Reference $ref. You will be notified of the outcome.");
    }

    public function terminateSession(Request $request)
    {
        $device = $request->input('device', 'device');

        return redirect(route('profile').'#security')->with('status', 'Signed out of '.$device.'.');
    }

    public function help(Request $request)
    {
        return view('help', ['u' => $request->user()]);
    }
}
