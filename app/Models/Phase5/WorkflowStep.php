<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    protected $fillable = [
        'instance_id', 'step_key', 'label', 'ordering', 'assignment_type',
        'assignee_id', 'role_key', 'status', 'due_at', 'started_at',
        'completed_at', 'result',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'instance_id' => 'integer',
            'ordering' => 'integer',
            'assignee_id' => 'integer',
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
