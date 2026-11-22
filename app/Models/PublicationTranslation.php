<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'publication_id', 'locale', 'slug', 'title', 'description', 'seo_title', 'seo_description',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }
}
