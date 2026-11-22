<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AutomationDefinition extends Model
{
    use SoftDeletes;

    protected $table = 'automation_definitions';

    protected $fillable = [
        'key',
        'category',
        'trigger_event',
        'enabled',
        'conditions',
        'actions',
        'owner_id',
        'version',
        'retry_limit',
        'timeout_seconds',
        'last_executed_at',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'conditions' => 'array',
            'actions' => 'array',
            'version' => 'integer',
            'retry_limit' => 'integer',
            'timeout_seconds' => 'integer',
            'last_executed_at' => 'datetime',
        ];
    }
}
