<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use Illuminate\Database\Eloquent\Model;

final class IntegrationRegistry extends Model
{
    protected $table = 'integration_registries';

    protected $fillable = [
        'key',
        'name',
        'provider',
        'category',
        'metadata',
        'enabled',
        'health_status',
        'last_success_at',
        'last_failure_at',
    ];

    protected $hidden = ['metadata', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'enabled' => 'boolean',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}
