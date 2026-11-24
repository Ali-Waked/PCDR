<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\Media;
use App\Models\Publication;
use App\Models\StrategicPillar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PublicationController extends ContentController
{
    private const CATEGORIES = [
        'annual_report', 'strategic_plan', 'research', 'study',
        'needs_assessment', 'policy', 'institutional',
    ];

    protected string $modelClass = Publication::class;

    protected string $resource = 'publications';

    protected string $label = 'Publications';

    protected string $routeBase = 'cms.publications';

    protected string $foreignKey = 'publication_id';

    protected string $auditPrefix = 'publication';

    /** @var list<string> */
    protected array $translationFields = [
        'title', 'slug', 'description', 'seo_title', 'seo_description',
    ];

    /** @var list<string> */
    protected array $extraFillable = [
        'category', 'strategic_pillar_id', 'document_media_id',
        'cover_media_id', 'publication_date', 'version', 'is_restricted',
    ];

    /** @var array<string, mixed> */
    protected array $extraRules = [
        'category' => ['required', 'in:annual_report,strategic_plan,research,study,needs_assessment,policy,institutional'],
        'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
        'publication_date' => ['nullable', 'date'],
        'version' => ['nullable', 'string', 'max:32'],
        'is_restricted' => ['boolean'],
    ];

    protected function applyIndexFilters(Builder $query, Request $request): void
    {
        $category = $request->string('category')->toString();
        if (in_array($category, self::CATEGORIES, true)) {
            $query->where('category', $category);
        }
    }

    /** @return list<array{key: string, label: string, options: list<array{value: string|int, label: string}>}> */
    protected function filterOptions(string $locale): array
    {
        return [[
            'key' => 'category',
            'label' => (string) __('website.ui.category', [], $locale),
            'options' => $this->categoryOptions($locale),
        ]];
    }

    /** @return array<string, mixed> */
    protected function indexItem(Model $item, string $locale): array
    {
        /** @var Publication $item */
        $title = $item->t('title', $locale) ?: $item->t('title');
        $description = $item->t('description', $locale) ?: $item->t('description');

        return array_merge(parent::indexItem($item, $locale), [
            'title' => $title,
            'summary' => Str::limit(strip_tags((string) $description), 160),
            'category' => $item->categoryLabel($locale),
            'categoryKey' => $item->category,
            'version' => $item->version,
            'isRestricted' => (bool) $item->is_restricted,
            'hasDocument' => $item->document !== null,
            'startDate' => $item->publication_date?->toDateString(),
        ]);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function validateContent(Request $request): array
    {
        // Rule objects cannot be used in a property initializer; attach the
        // media-type constraints immediately before the shared validation runs.
        $this->extraRules['document_media_id'] = [
            'nullable',
            'integer',
            Rule::exists('media', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('type', 'document')),
        ];
        $this->extraRules['cover_media_id'] = [
            'nullable',
            'integer',
            Rule::exists('media', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('type', 'image')),
        ];

        $data = parent::validateContent($request);
        $documentId = $data['document_media_id'] ?? null;
        $restricted = (bool) ($data['is_restricted'] ?? false);

        if ($restricted && empty($documentId)) {
            throw ValidationException::withMessages([
                'document_media_id' => __('website.publication.restricted_requires_document'),
            ]);
        }

        if (! empty($documentId)) {
            $document = Media::query()->find($documentId);
            if ($document !== null && (bool) $document->is_restricted !== $restricted) {
                throw ValidationException::withMessages([
                    'is_restricted' => __('website.publication.document_visibility_mismatch'),
                ]);
            }
        }

        return $data;
    }

    /** @return array<string, mixed> */
    protected function auditSnapshot(Model $record): array
    {
        /** @var Publication $record */
        return array_merge(parent::auditSnapshot($record), [
            'category' => $record->category,
            'isRestricted' => (bool) $record->is_restricted,
        ]);
    }

    /** @return array<string, mixed> */
    protected function options(string $locale): array
    {
        return [
            'categories' => $this->categoryOptions($locale),
            'pillars' => StrategicPillar::query()->with('translations')->get()
                ->map(fn (StrategicPillar $pillar): array => [
                    'id' => $pillar->id,
                    'label' => $pillar->t('title', $locale) ?: $pillar->t('title'),
                ])->all(),
            'media' => $this->mediaOptions(),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function categoryOptions(string $locale): array
    {
        return collect(self::CATEGORIES)
            ->map(fn (string $key): array => [
                'value' => $key,
                'label' => (string) __("website.publication.categories.{$key}", [], $locale),
            ])
            ->all();
    }
}
