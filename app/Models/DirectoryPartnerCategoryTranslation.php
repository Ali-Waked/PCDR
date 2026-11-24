<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryPartnerCategoryTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'directory_partner_category_id', 'locale', 'name', 'description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DirectoryPartnerCategory::class, 'directory_partner_category_id');
    }
}
