<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'instance_id', 'from_status', 'to_status', 'action', 'actor_id',
        'reason', 'context', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'instance_id' => 'integer',
            'actor_id' => 'integer',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        static::updating(function (): bool {
            return false;
        });

        static::deleting(function (): bool {
            return false;
        });
    }
}
