<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\Publication;
use App\Models\StrategicPillar;
use Illuminate\Support\Facades\URL;

final class PublicationController extends ContentController
{
    protected string $modelClass = Publication::class;

    protected string $resource = 'publications';

    protected string $label = 'Publications';

    protected string $routeBase = 'cms.publications';

    protected string $foreignKey = 'publication_id';

    protected array $translationFields = [
        'title', 'slug', 'description', 'seo_title', 'seo_description',
    ];

    protected array $extraFillable = [
        'category', 'strategic_pillar_id', 'document_media_id',
        'cover_media_id', 'publication_date', 'version', 'is_restricted',
    ];

    protected array $extraRules = [
        'category' => ['required', 'in:annual_report,strategic_plan,research,study,needs_assessment,policy,institutional'],
        'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
        'document_media_id' => ['nullable', 'exists:media,id'],
        'cover_media_id' => ['nullable', 'exists:media,id'],
        'publication_date' => ['nullable', 'date'],
        'version' => ['nullable', 'string', 'max:32'],
        'is_restricted' => ['boolean'],
    ];

    protected function options(): array
    {
        return [
            'categories' => collect(['annual_report', 'strategic_plan', 'research', 'study',
                'needs_assessment', 'policy', 'institutional'])
                ->map(fn ($key) => ['value' => $key, 'label' => __("website.publication.categories.{$key}")])->all(),
            'pillars' => StrategicPillar::query()->with('translations')->get()
                ->map(fn ($pillar) => ['id' => $pillar->id, 'label' => $pillar->t('title')])->all(),
            'restrictedDocuments' => Publication::query()->where('is_restricted', true)
                ->with('document')->get()->filter(fn ($item) => $item->document !== null)
                ->mapWithKeys(fn ($item) => [
                    $item->id => URL::temporarySignedRoute(
                        'public.media.download', now()->addHour(), [
                            'locale' => app()->getLocale(),
                            'uuid' => $item->document->uuid,
                        ],
                    ),
                ])->all(),
        ];
    }
}
