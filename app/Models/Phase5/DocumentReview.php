<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\DocumentLink;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Technical document review. No institutional approval outcome is modelled. */
final class DocumentReview extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'document_reviews';

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'media_id',
        'document_link_id',
        'subject_type',
        'subject_id',
        'title',
        'description',
        'status',
        'version',
        'reviewer_id',
        'submitter_id',
        'due_at',
        'reviewed_at',
        'outcome_note',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'media_id' => 'integer',
            'document_link_id' => 'integer',
            'subject_id' => 'integer',
            'reviewer_id' => 'integer',
            'submitter_id' => 'integer',
            'version' => 'integer',
            'due_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function documentLink(): BelongsTo
    {
        return $this->belongsTo(DocumentLink::class, 'document_link_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DocumentReviewEvent::class, 'review_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }
}
