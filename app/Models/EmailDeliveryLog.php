<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDeliveryLog extends Model
{
    protected $fillable = [
        'idempotency_key', 'purpose', 'template_key', 'inquiry_type', 'inquiry_id',
        'inquiry_reference', 'recipient_hash', 'locale', 'status', 'provider',
        'provider_message_id', 'error_code', 'attempts', 'sender_user_id',
        'queued_at', 'sent_at', 'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
