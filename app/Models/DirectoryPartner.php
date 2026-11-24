<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DirectoryPartner extends Model
{
    use HasFactory;
    use HasTranslations;
    use Publishable;
    use SoftDeletes;

    protected $fillable = [
        'directory_partner_category_id', 'logo_media_id', 'website_url',
        'partnership_year', 'status', 'published_at', 'display_order',
    ];

    protected function casts(): array
    {
        return [
            'partnership_year' => 'integer',
            'published_at' => 'datetime',
            'display_order' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return DirectoryPartnerTranslation::class;
    }

    public function translationForeignKey(): string
    {
        return 'directory_partner_id';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DirectoryPartnerCategory::class, 'directory_partner_category_id');
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    /** @param Builder<self> $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
