<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnershipInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'organization', 'contact_name', 'email', 'website',
        'interest', 'message', 'locale', 'consent', 'ip_hash', 'status', 'assigned_to',
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
