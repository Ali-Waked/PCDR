<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'title', 'description', 'status', 'priority', 'start_at',
        'due_at', 'owner_id', 'creator_id', 'subject_type', 'subject_id',
        'workflow_instance_id', 'parent_id', 'progress_percent',
        'completed_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'owner_id' => 'integer',
            'creator_id' => 'integer',
            'subject_id' => 'integer',
            'workflow_instance_id' => 'integer',
            'parent_id' => 'integer',
            'progress_percent' => 'integer',
            'start_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class, 'task_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function collaborators(): HasMany
    {
        return $this->hasMany(TaskCollaborator::class, 'task_id');
    }

    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(TaskAssignmentHistory::class, 'task_id');
    }
}
