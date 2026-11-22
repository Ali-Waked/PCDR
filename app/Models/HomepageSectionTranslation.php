<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageSectionTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'homepage_section_id', 'locale', 'eyebrow', 'title', 'body', 'cta_label', 'cta_link',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(HomepageSection::class, 'homepage_section_id');
    }
}
