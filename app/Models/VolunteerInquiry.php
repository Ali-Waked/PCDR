<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'name', 'email', 'phone', 'area_of_interest', 'availability',
        'message', 'locale', 'consent', 'ip_hash', 'status',
    ];

    protected function casts(): array
    {
        return ['consent' => 'boolean'];
    }
}
