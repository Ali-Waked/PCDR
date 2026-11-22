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
 * Safe accountability record. No submitter, contact, beneficiary or narrative
 * fields beyond the single sanitized summary are part of this model.
 */
final class AccountabilityCase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'accountability_cases';

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'category',
        'channel',
        'status',
        'summary',
        'preferred_contact_method',
        'language_preference',
        'is_sensitive',
        'assigned_to',
        'due_at',
        'closed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
            'due_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(AccountabilityEvent::class, 'case_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
