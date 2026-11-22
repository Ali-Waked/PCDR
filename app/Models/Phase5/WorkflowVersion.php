<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowVersion extends Model
{
    protected $fillable = [
        'definition_id', 'version', 'config', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'definition_id' => 'integer',
            'version' => 'integer',
            'config' => 'array',
            'published_at' => 'datetime',
            'created_by' => 'integer',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
