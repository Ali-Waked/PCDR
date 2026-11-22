<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCollaborator extends Model
{
    protected $fillable = ['task_id', 'user_id'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'task_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
