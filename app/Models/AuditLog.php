<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    /** The cleanup service uses a short-lived token to authorize deletion. */
    private static ?string $cleanupDeletionToken = null;

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'context' => 'array',
            'created_at' => 'datetime',
            'archived_at' => 'datetime',
            'archive_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Historical event fields are immutable. Only archival lifecycle
        // metadata may be changed, and only through the archive service.
        static::updating(function (self $log): bool {
            $dirty = array_keys($log->getDirty());

            return $dirty !== [] && array_diff($dirty, ['archive_id', 'archived_at']) === [];
        });

        static::deleting(function (): bool {
            return self::$cleanupDeletionToken !== null;
        });
    }

    /**
     * Run destructive cleanup with a narrowly scoped authorization token.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withCleanupDeletion(callable $callback): mixed
    {
        $token = Str::random(64);
        self::$cleanupDeletionToken = $token;

        try {
            return $callback();
        } finally {
            if (self::$cleanupDeletionToken === $token) {
                self::$cleanupDeletionToken = null;
            }
        }
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<AuditArchive, $this> */
    public function archive(): BelongsTo
    {
        return $this->belongsTo(AuditArchive::class, 'archive_id');
    }
}
