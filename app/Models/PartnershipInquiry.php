<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnershipInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'organization', 'contact_name', 'email', 'website',
        'interest', 'message', 'locale', 'consent', 'ip_hash', 'status',
    ];

    protected function casts(): array
    {
        return ['consent' => 'boolean'];
    }
}
