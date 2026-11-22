<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WebhookDelivery extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENDING = 'sending';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DEAD_LETTER = 'dead_letter';

    public const STATUS_DISABLED = 'disabled';

    protected $table = 'webhook_deliveries';

    public $timestamps = false;

    protected $fillable = [
        'endpoint_id',
        'event',
        'payload_version',
        'payload_hash',
        'status',
        'attempts',
        'response_code',
        'error_code',
        'next_attempt_at',
        'delivered_at',
        'created_at',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }

    protected function casts(): array
    {
        return [
            'payload_version' => 'integer',
            'attempts' => 'integer',
            'response_code' => 'integer',
            'next_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
