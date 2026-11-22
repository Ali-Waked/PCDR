<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Non-identifying referral between two authorized operational references.
 * Beneficiary, household, medical, protection and disability data are absent.
 */
final class Referral extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'referrals';

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'source_type',
        'source_id',
        'destination_type',
        'destination_id',
        'reason',
        'status',
        'assigned_to',
        'created_by',
        'due_at',
        'response_at',
        'outcome',
        'closure_reason',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'destination_id' => 'integer',
            'assigned_to' => 'integer',
            'created_by' => 'integer',
            'due_at' => 'datetime',
            'response_at' => 'datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReferralEvent::class, 'referral_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
