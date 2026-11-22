<?php

declare(strict_types=1);

namespace App\Models\Phase5;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The notification_id foreign key stays null for local channels: notifications use ULIDs. */
final class NotificationDelivery extends Model
{
    protected $table = 'notification_deliveries';

    protected $fillable = [
        'notification_id',
        'user_id',
        'channel',
        'provider',
        'status',
        'provider_reference',
        'attempts',
        'error_code',
        'queued_at',
        'sent_at',
        'delivered_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
