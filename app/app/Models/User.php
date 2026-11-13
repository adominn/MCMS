<?php

namespace App\Models;

use App\Modules\Identity\Role;
use App\Modules\Identity\RoleAssignment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'last_login_at' => 'datetime'];
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    // Roles in force today (a role has an optional start and end date).
    public function activeRoles()
    {
        return $this->roleAssignments()
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', today()))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', today()))
            ->get()->pluck('role')->unique();
    }

    // PTj ids the user may act in for a role; null means the whole University (FR-12).
    public function scopedPtjIds(string $role): ?array
    {
        $a = $this->roleAssignments()->where('role', $role)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', today()))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', today()))->get();

        return $a->contains(fn ($x) => in_array($x->scope_type, [null, 'university'], true)) ? null : $a->where('scope_type', 'ptj')->pluck('ptj_id')->filter()->all();
    }

    /** Active roles scoped to the session's active role when acting in a switched role context. @return \Illuminate\Support\Collection<int, Role> */
    public function effectiveRoles(): \Illuminate\Support\Collection
    {
        $activeRoles = $this->activeRoles();

        if (auth()->check() && (int) auth()->id() === (int) $this->id && session()->has('active_role')) {
            $sessionRole = (string) session('active_role');
            $matched = $activeRoles->first(fn (Role $r) => $r->value === $sessionRole);
            if ($matched) {
                return collect([$matched]);
            }
        }

        return $activeRoles;
    }

    public function hasAnyRole(string ...$roles): bool
    {
        return $this->effectiveRoles()->contains(fn (Role $r) => in_array($r->value, $roles, true));
    }

    public function hasRole(string $role): bool
    {
        return $this->hasAnyRole($role);
    }

    /** Currently active role for this session, falling back to the primary active role. */
    public function currentRole(): ?Role
    {
        $activeRoles = $this->activeRoles();
        $sessionRole = (string) session('active_role');

        if ($sessionRole !== '') {
            $matched = $activeRoles->first(fn (Role $r) => $r->value === $sessionRole);
            if ($matched) {
                return $matched;
            }
        }

        return $activeRoles->first();
    }

    /** Role-appropriate home URL for logos, error actions, and portal return links. */
    public function homeUrl(): string
    {
        return route($this->currentRole()?->homeRouteName() ?? 'tasks');
    }
}
