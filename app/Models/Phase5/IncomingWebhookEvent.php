<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use Illuminate\Database\Eloquent\Model;

final class IncomingWebhookEvent extends Model
{
    protected $table = 'incoming_webhook_events';

    public $timestamps = false;

    protected $fillable = [
        'integration_id',
        'event',
        'nonce',
        'payload_hash',
        'processed',
        'received_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'processed' => 'boolean',
            'received_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
