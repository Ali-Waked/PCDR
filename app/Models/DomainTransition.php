<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainTransition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'resource', 'owner_id', 'status_field', 'from_status', 'to_status',
        'actor_id', 'reason', 'evidence_media_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'owner_id' => 'integer',
            'actor_id' => 'integer',
            'created_at' => 'datetime',
        ];
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
