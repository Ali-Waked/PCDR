<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\OperationalTranslation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Localisation for config-declared operational entities.
 *
 * The resource discriminator is part of the relationship so two entities with
 * the same numeric id can never share a translation row.
 */
trait HasOperationalTranslations
{
    public function translations(): HasMany
    {
        /** @var Model $this */
        $resource = method_exists($this, 'getResource') ? $this->getResource() : (string) $this->getAttribute('resource');

        return $this->hasMany(OperationalTranslation::class, 'owner_id')
            ->where('resource', $resource);
    }

    public function translation(?string $locale = null): ?Model
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
        $translation = $this->translation($locale);

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
}
