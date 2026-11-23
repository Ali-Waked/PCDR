<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'category', 'name', 'email', 'subject', 'message',
        'locale', 'consent', 'ip_hash', 'user_agent', 'status', 'assigned_to',
    ];

    protected function casts(): array
    {
        return ['consent' => 'boolean'];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
