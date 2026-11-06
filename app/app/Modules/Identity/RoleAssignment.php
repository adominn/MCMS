<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use Illuminate\Database\Eloquent\Model;

final class RoleAssignment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['role' => Role::class, 'valid_from' => 'date', 'valid_to' => 'date'];
    }
}
