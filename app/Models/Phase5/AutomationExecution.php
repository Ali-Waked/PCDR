<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use Illuminate\Database\Eloquent\Model;

final class AutomationExecution extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DEAD_LETTER = 'dead_letter';

    protected $table = 'automation_executions';

    protected $fillable = [
        'definition_id',
        'definition_version',
        'trigger_event',
        'subject_type',
        'subject_id',
        'idempotency_key',
        'status',
        'attempts',
        'last_error',
        'context',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'definition_version' => 'integer',
            'subject_id' => 'integer',
            'attempts' => 'integer',
            'context' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
