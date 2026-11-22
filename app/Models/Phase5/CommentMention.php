<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentMention extends Model
{
    protected $fillable = ['comment_id', 'mentioned_user_id', 'notified_at'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'comment_id' => 'integer',
            'mentioned_user_id' => 'integer',
            'notified_at' => 'datetime',
        ];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(DiscussionComment::class, 'comment_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }
}
