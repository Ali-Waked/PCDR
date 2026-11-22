<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'category', 'name', 'email', 'subject', 'message',
        'locale', 'consent', 'ip_hash', 'user_agent', 'status',
    ];

    protected function casts(): array
    {
        return ['consent' => 'boolean'];
    }
}
