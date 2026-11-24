<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditCleanupRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditCleanupRun extends Model
{
    /** @use HasFactory<AuditCleanupRunFactory> */
    use HasFactory;

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const MODE_DRY_RUN = 'dry_run';

    public const MODE_EXECUTE = 'execute';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'report' => 'array',
            'eligible_count' => 'integer',
            'deleted_count' => 'integer',
            'skipped_hold_count' => 'integer',
            'skipped_unverified_count' => 'integer',
            'estimated_bytes' => 'integer',
            'audit_archive_id' => 'integer',
            'requested_by' => 'integer',
        ];
    }

    /** @return BelongsTo<AuditArchive, $this> */
    public function archive(): BelongsTo
    {
        return $this->belongsTo(AuditArchive::class, 'audit_archive_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
