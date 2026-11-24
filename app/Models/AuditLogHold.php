<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditLogHoldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogHold extends Model
{
    /** @use HasFactory<AuditLogHoldFactory> */
    use HasFactory;

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_SUBJECT = 'subject';

    public const SCOPE_ACTOR = 'actor';

    public const SCOPE_PERIOD = 'period';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'actor_id' => 'integer',
            'created_by' => 'integer',
            'released_by' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
