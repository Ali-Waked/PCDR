<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $fillable = ['key', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(EmailTemplateTranslation::class);
    }

    public function translationFor(string $locale): ?EmailTemplateTranslation
    {
        $locales = array_values(array_unique([
            $locale,
            config('website.default_locale', 'ar'),
            config('website.fallback_locale', 'en'),
        ]));

        foreach ($locales as $candidate) {
            $match = $this->translations->firstWhere('locale', $candidate);

            if ($match !== null) {
                return $match;
            }
        }

        return $this->translations->sortBy('id')->first();
    }
}
