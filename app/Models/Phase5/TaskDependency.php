<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends Model
{
    public $timestamps = false;

    protected $fillable = ['task_id', 'depends_on_task_id', 'type', 'created_at'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'task_id' => 'integer',
            'depends_on_task_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }
}
