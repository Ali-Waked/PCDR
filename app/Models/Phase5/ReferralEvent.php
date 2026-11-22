<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only referral history. */
final class ReferralEvent extends Model
{
    public $timestamps = false;

    protected $table = 'referral_events';

    /** @var list<string> */
    protected $fillable = [
        'referral_id',
        'action',
        'from_status',
        'to_status',
        'actor_id',
        'note',
        'created_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'referral_id' => 'integer',
            'actor_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'referral_id');
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
