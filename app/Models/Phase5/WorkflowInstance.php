<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    protected $fillable = [
        'reference', 'definition_id', 'version_id', 'subject_type', 'subject_id',
        'status', 'current_step_order', 'initiator_id', 'due_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'definition_id' => 'integer',
            'version_id' => 'integer',
            'subject_id' => 'integer',
            'current_step_order' => 'integer',
            'initiator_id' => 'integer',
            'due_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'version_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'instance_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'instance_id');
    }
}
