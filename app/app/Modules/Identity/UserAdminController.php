<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Models\User;
use App\Modules\Notifications\Notifier;
use App\Modules\Records\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// FR-13 (roles and scope), FR-23 (user list). Only the System Administrator reaches these routes.
final class UserAdminController
{
    public function __construct(private AuditRecorder $audit, private Notifier $notifier) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $users = User::query()->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('name', db_like(), "%$q%")->orWhere('email', db_like(), "%$q%")))
            ->orderBy('name')->paginate(1000)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'q' => $q]);
    }

    public function show(User $user)
    {
        return view('admin.users.show', [
            'person' => $user,
            'assignments' => $user->roleAssignments()->orderByDesc('id')->get(),
            'ptjs' => DB::table('ptjs')->orderBy('code')->get(),
            'roles' => Role::assignableCases(),
        ]);
    }

    public function assign(Request $request, User $user)
    {
        $d = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
            'scope_type' => ['required', Rule::in(['university', 'ptj', 'mc'])],
            'ptj_id' => ['nullable', 'required_if:scope_type,ptj', 'exists:ptjs,id'],
            'scope_ref' => ['nullable', 'required_if:scope_type,mc', 'string', 'max:60'],
            'valid_from' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        // D-51: A lecturer is strictly affiliated with exactly one home PTj.
        if ($d['role'] === Role::Lecturer->value && $d['scope_type'] === 'ptj' && !empty($d['ptj_id'])) {
            $existing = $user->roleAssignments()
                ->where('role', Role::Lecturer->value)
                ->where('scope_type', 'ptj')
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', today()))
                ->where('ptj_id', '!=', $d['ptj_id'])
                ->exists();
            if ($existing) {
                throw ValidationException::withMessages([
                    'ptj_id' => __('A lecturer must belong strictly to one home faculty (PTj). Revoke or terminate the existing faculty assignment before assigning a new one.'),
                ]);
            }
        }

        // Appendix H SoD: CODL admin cannot assign academic, QA, or registry roles to themselves (Cl. 15.2.3, REQ-C15-002)
        if ((int) $request->user()->id === (int) $user->id) {
            $academicOrRegistryRoles = [
                Role::DeputyDean->value,
                Role::PtjAuthority->value,
                Role::AcademicAuthority->value,
                Role::QaOfficer->value,
                Role::RecordsCustodian->value,
                Role::AbcDepositor->value,
                Role::RecordsDepositor->value,
            ];
            if (in_array($d['role'], $academicOrRegistryRoles, true)) {
                throw ValidationException::withMessages([
                    'role' => __('Administrators cannot assign academic, quality, or registry authority roles to their own account.'),
                ]);
            }
        }

        // D-52: Separation of Duties (SoD) - Deputy Dean/PTj and Lecturer roles are strictly mutually exclusive.
        if ($d['role'] === Role::Lecturer->value) {
            $hasActiveDd = $user->roleAssignments()
                ->whereIn('role', [Role::DeputyDean->value, Role::PtjAuthority->value])
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', today()))
                ->exists();
            if ($hasActiveDd) {
                throw ValidationException::withMessages([
                    'role' => __('A Deputy Dean cannot hold a lecturer role. Academic approval and proposal authoring roles are mutually exclusive (D-52).'),
                ]);
            }
        }

        if (in_array($d['role'], [Role::DeputyDean->value, Role::PtjAuthority->value], true)) {
            $hasActiveLecturer = $user->roleAssignments()
                ->where('role', Role::Lecturer->value)
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', today()))
                ->exists();
            if ($hasActiveLecturer) {
                throw ValidationException::withMessages([
                    'role' => __('A lecturer cannot be assigned a Deputy Dean role while holding an active lecturer appointment. Revoke or terminate the lecturer role first (D-52).'),
                ]);
            }
        }
        $a = DB::transaction(function () use ($d, $user, $request) {
            $a = $user->roleAssignments()->create([
                'role' => $d['role'], 'scope_type' => $d['scope_type'],
                'ptj_id' => $d['scope_type'] === 'ptj' ? $d['ptj_id'] : null,
                'scope_ref' => $d['scope_type'] === 'mc' ? $d['scope_ref'] : null,
                'valid_from' => $d['valid_from'], 'assigned_by' => $request->user()->id, 'reason' => $d['reason'],
            ]);
            $this->audit->record('role.assigned', $user, after: $a->only(['role', 'scope_type', 'ptj_id', 'scope_ref', 'valid_from']) + ['role' => $d['role']], reason: $d['reason']);
            $this->notifier->send($user, 'role.assigned', [
                'name' => $user->name, 'role' => Role::from($d['role'])->label(), 'scope' => $this->scopeText($d), 'from' => $d['valid_from'], 'reason' => $d['reason'],
            ], dedupe: 'assignment-'.$a->id);

            return $a;
        });

        return redirect()->route('users.show', $user)->with('status', 'Role assigned.');
    }

    public function revoke(Request $request, User $user, RoleAssignment $assignment)
    {
        abort_unless($assignment->user_id === $user->id, 404);
        $d = $request->validate(['reason' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($assignment, $user, $d) {
            $assignment->update(['valid_to' => today()->subDay()]); // the row stays; the role stops today
            $this->audit->record('role.revoked', $user, before: ['role' => $assignment->role->value], reason: $d['reason']);
            $this->notifier->send($user, 'role.revoked', ['name' => $user->name, 'role' => $assignment->role->label(), 'reason' => $d['reason']], dedupe: 'revoke-'.$assignment->id);
        });

        return redirect()->route('users.show', $user)->with('status', 'Role revoked. The record of it stays.');
    }

    public function setStatus(Request $request, User $user, string $to)
    {
        abort_unless(in_array($to, ['active', 'inactive'], true), 404);
        $d = $request->validate(['reason' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($user, $to, $d) {
            $user->update(['status' => $to]); // nothing linked to the user is altered (FR-23)
            $this->audit->record($to === 'inactive' ? 'user.deactivated' : 'user.reactivated', $user, before: ['status' => $to === 'inactive' ? 'active' : 'inactive'], after: ['status' => $to], reason: $d['reason']);
            $this->notifier->send($user, $to === 'inactive' ? 'account.deactivated' : 'account.reactivated', ['name' => $user->name, 'reason' => $d['reason']], dedupe: $to.'-'.now()->timestamp);
        });

        return redirect()->route('users.show', $user)->with('status', $to === 'inactive' ? 'Account deactivated.' : 'Account reactivated.');
    }

    private function scopeText(array $d): string
    {
        return match ($d['scope_type']) {
            'university' => 'whole University',
            'ptj' => 'PTj '.DB::table('ptjs')->where('id', $d['ptj_id'])->value('code'),
            'mc' => 'micro-credential '.$d['scope_ref'],
        };
    }
}
