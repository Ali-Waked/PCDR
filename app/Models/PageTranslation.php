<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id', 'locale', 'slug', 'title', 'summary', 'body', 'seo_title',
        'seo_description', 'canonical_url', 'og_image', 'robots',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
