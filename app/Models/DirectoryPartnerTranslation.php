<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryPartnerTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'directory_partner_id', 'locale', 'slug', 'name', 'description',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(DirectoryPartner::class, 'directory_partner_id');
    }
}
