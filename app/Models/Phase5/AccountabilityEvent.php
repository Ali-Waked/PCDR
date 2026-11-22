<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only accountability history. */
final class AccountabilityEvent extends Model
{
    public $timestamps = false;

    protected $table = 'accountability_events';

    /** @var list<string> */
    protected $fillable = [
        'case_id',
        'action',
        'from_status',
        'to_status',
        'actor_id',
        'note',
        'is_sensitive',
        'created_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'case_id' => 'integer',
            'actor_id' => 'integer',
            'is_sensitive' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(AccountabilityCase::class, 'case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function booted(): void
    {
        self::updating(fn (): bool => false);
        self::deleting(fn (): bool => false);
    }
}
