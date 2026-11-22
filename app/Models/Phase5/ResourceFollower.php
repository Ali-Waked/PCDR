<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceFollower extends Model
{
    protected $fillable = ['resource_type', 'resource_id', 'user_id'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'resource_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
