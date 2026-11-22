<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Actions\TransitionContentStatus;
use App\Enums\ContentStatus;
use App\Models\ContentRevision;
use App\Models\User;
use App\Services\TranslationSaver;
use Illuminate\Database\Eloquent\Model;

trait ManagesContent
{
    /** @param list<string> $fields */
    protected function saveTranslations(Model $model, array $translations, array $fields, string $foreignKey): void
    {
        app(TranslationSaver::class)->sync($model, $translations, $fields, $foreignKey);
    }

    protected function changeStatus(Model $model, string $status, ?User $actor = null): void
    {
        app(TransitionContentStatus::class)->handle($model, ContentStatus::from($status), $actor);
    }

    protected function recordRevision(Model $model, ?User $actor, string $note): void
    {
        $payload = [];
        foreach ($model->translations as $translation) {
            $payload[$translation->locale] = $translation->only(['title', 'slug', 'summary', 'body', 'description']);
        }

        ContentRevision::create([
            'revisionable_type' => $model->getMorphClass(),
            'revisionable_id' => $model->getKey(),
            'locale' => app()->getLocale(),
            'title' => $model->t('title'),
            'status' => ContentStatus::valueOf($model->workflow_status ?? $model->status, ''),
            'payload' => $payload,
            'actor_id' => $actor?->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, array<string, mixed>>
     */
    protected function translationInputs(?Model $model, array $fields): array
    {
        $result = [];
        foreach (config('website.locales', ['ar', 'en']) as $locale) {
            $translation = $model?->translations->firstWhere('locale', $locale);
            foreach ($fields as $field) {
                $result[$locale][$field] = $translation?->getAttribute($field) ?? '';
            }
        }

        return $result;
    }
}
