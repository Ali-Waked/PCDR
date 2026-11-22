<?php

namespace App\Models;

use App\Support\ScopeValue;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['role_id', 'resource_key', 'scope_type', 'scope_value'])]
class RoleScope extends Model
{
    public const TYPE_ALL = 'all';

    public const TYPE_SELF = 'self';

    public const TYPE_ASSIGNED = 'assigned';

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    protected function casts(): array
    {
        return ['scope_value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (RoleScope $scope): void {
            $scope->scope_value_hash = ScopeValue::hash($scope->scope_value ?? []);
        });
    }
}
