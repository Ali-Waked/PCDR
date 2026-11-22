<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TranslationSaver
{
    /**
     * @param  list<string>  $fillableFields
     * @param  list<string>  $richTextFields
     */
    public function sync(
        Model $model,
        array $translations,
        array $fillableFields,
        string $foreignKey,
        array $richTextFields = ['body', 'description', 'objectives'],
    ): void {
        DB::transaction(function () use ($model, $translations, $fillableFields, $foreignKey, $richTextFields): void {
            foreach ($translations as $locale => $values) {
                if (! in_array($locale, config('website.locales', ['ar', 'en']), true) || ! is_array($values)) {
                    continue;
                }

                $attributes = [$foreignKey => $model->getKey(), 'locale' => $locale];
                foreach ($fillableFields as $field) {
                    if (! array_key_exists($field, $values)) {
                        continue;
                    }
                    $value = $values[$field];
                    $attributes[$field] = in_array($field, $richTextFields, true)
                        ? RichText::clean(is_string($value) ? $value : '')
                        : (is_string($value) || $value === null ? $value : null);
                }

                if (in_array('slug', $fillableFields, true)) {
                    $slug = trim((string) ($attributes['slug'] ?? ''));
                    if ($slug === '') {
                        $slug = Str::slug((string) ($attributes['title'] ?? 'content'));
                        if ($slug === '') {
                            $slug = Str::lower(Str::random(8));
                        }
                    }
                    $attributes['slug'] = Str::limit($slug, 180, '');
                }

                $model->translations()->updateOrCreate(
                    [$foreignKey => $model->getKey(), 'locale' => $locale],
                    $attributes,
                );
            }
        });

        $model->unsetRelation('translations');
        $model->load('translations');
    }
}
