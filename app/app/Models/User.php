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

    public function hasAnyRole(string ...$roles): bool
    {
        return $this->activeRoles()->contains(fn (Role $r) => in_array($r->value, $roles, true));
    }

    public function hasRole(string $role): bool
    {
        return $this->hasAnyRole($role);
    }
}
