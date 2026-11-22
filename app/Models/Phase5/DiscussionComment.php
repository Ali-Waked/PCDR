<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionComment extends Model
{
    use SoftDeletes;

    protected $fillable = ['thread_id', 'parent_id', 'author_id', 'body', 'edited_at'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'thread_id' => 'integer',
            'parent_id' => 'integer',
            'author_id' => 'integer',
            'edited_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(DiscussionThread::class, 'thread_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(CommentMention::class, 'comment_id');
    }
}
