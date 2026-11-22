<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklistItem extends Model
{
    protected $fillable = [
        'task_id', 'title', 'position', 'is_done', 'completed_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'task_id' => 'integer',
            'position' => 'integer',
            'is_done' => 'boolean',
            'completed_by' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
