<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OperationalTranslation;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Sanitised writer for the shared operational translation table.
 */
final class OperationalTranslationSaver
{
    public function __construct(private readonly OperationCatalog $catalog) {}

    /**
     * @param  array<string, mixed>  $translations
     */
    public function sync(string $resource, Model $owner, array $translations): void
    {
        $fields = $this->catalog->translationFields($resource);
        $richText = $this->catalog->richTextFields();
        $locales = config('operations.locales', ['ar', 'en']);
        $locales = is_array($locales) ? $locales : ['ar', 'en'];

        DB::transaction(function () use ($resource, $owner, $translations, $fields, $richText, $locales): void {
            foreach ($translations as $locale => $values) {
                if (! in_array($locale, $locales, true) || ! is_array($values)) {
                    continue;
                }

                $attributes = [
                    'resource' => $resource,
                    'owner_id' => $owner->getKey(),
                    'locale' => $locale,
                ];

                foreach ($fields as $field) {
                    $value = $values[$field] ?? null;
                    if (! is_string($value) && $value !== null) {
                        continue;
                    }

                    $attributes[$field] = in_array($field, $richText, true)
                        ? RichText::clean($value)
                        : ($value === null ? null : trim(strip_tags($value)));
                }

                OperationalTranslation::query()->updateOrCreate(
                    ['resource' => $resource, 'owner_id' => $owner->getKey(), 'locale' => $locale],
                    $attributes,
                );
            }
        });
    }
}
