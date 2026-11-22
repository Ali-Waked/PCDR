<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignmentHistory extends Model
{
    public $timestamps = false;

    protected $table = 'task_assignment_history';

    protected $fillable = [
        'task_id', 'from_user_id', 'to_user_id', 'actor_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'task_id' => 'integer',
            'from_user_id' => 'integer',
            'to_user_id' => 'integer',
            'actor_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function previousOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function newOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
