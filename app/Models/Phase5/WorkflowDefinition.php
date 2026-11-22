<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowDefinition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key', 'category', 'status', 'trigger_event', 'owner_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'owner_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class, 'definition_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'definition_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
