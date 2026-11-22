<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WebhookEndpoint extends Model
{
    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'integration_id',
        'name',
        'url',
        'events',
        'secret_encrypted',
        'active',
        'created_by',
        'last_success_at',
        'last_failure_at',
    ];

    protected $hidden = ['secret_encrypted'];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(IntegrationRegistry::class, 'integration_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'secret_encrypted' => 'encrypted',
            'active' => 'boolean',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }
}
