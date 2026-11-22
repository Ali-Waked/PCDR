<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['group', 'key', 'type', 'is_localized', 'is_public', 'value'];

    protected function casts(): array
    {
        return ['is_localized' => 'boolean', 'is_public' => 'boolean'];
    }

    public function translationModel(): string
    {
        return WebsiteSettingTranslation::class;
    }

    public function translationForeignKey(): string
    {
        return 'website_setting_id';
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function resolvedValue(?string $locale = null): ?string
    {
        if (! $this->is_localized) {
            return $this->value;
        }

        return $this->t('value', $locale) ?? $this->value;
    }
}
