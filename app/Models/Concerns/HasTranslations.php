<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shared translation behaviour for authored Phase 3 content.
 *
 * Fallback policy: requested locale -> configured default -> configured
 * fallback -> any available translation. Missing values are never synthesized.
 */
trait HasTranslations
{
    /** @return class-string<Model> */
    abstract public function translationModel(): string;

    abstract public function translationForeignKey(): string;

    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel(), $this->translationForeignKey());
    }

    public function translate(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();
        $locales = array_values(array_unique([
            $locale,
            config('website.default_locale', 'ar'),
            config('website.fallback_locale', 'en'),
        ]));

        $loaded = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->get();

        foreach ($locales as $candidate) {
            $match = $loaded->firstWhere('locale', $candidate);
            if ($match !== null) {
                return $match;
            }
        }

        return $loaded->sortBy('id')->first();
    }

    public function t(string $field, ?string $locale = null): ?string
    {
        $translation = $this->translate($locale);

        return $translation?->getAttribute($field);
    }

    /** @return list<string> */
    public function availableLocales(): array
    {
        $loaded = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->get();

        return $loaded->pluck('locale')->all();
    }

    public function hasLocale(string $locale): bool
    {
        return in_array($locale, $this->availableLocales(), true);
    }
}
