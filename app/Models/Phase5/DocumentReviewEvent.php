<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only revision trail for a technical document review. */
final class DocumentReviewEvent extends Model
{
    public $timestamps = false;

    protected $table = 'document_review_events';

    /** @var list<string> */
    protected $fillable = [
        'review_id',
        'action',
        'from_status',
        'to_status',
        'actor_id',
        'note',
        'revision',
        'created_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'review_id' => 'integer',
            'actor_id' => 'integer',
            'revision' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(DocumentReview::class, 'review_id');
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
