<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionThread extends Model
{
    protected $fillable = ['resource_type', 'resource_id', 'created_by'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'resource_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DiscussionComment::class, 'thread_id');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(ResourceFollower::class, 'resource_id')
            ->where('resource_type', $this->resource_type);
    }
}
