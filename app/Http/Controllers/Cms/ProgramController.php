<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\Program;
use App\Models\StrategicPillar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class ProgramController extends ContentController
{
    protected string $modelClass = Program::class;

    protected string $resource = 'programs';

    protected string $label = 'Programmes';

    protected string $routeBase = 'cms.programs';

    protected string $foreignKey = 'program_id';

    protected array $translationFields = [
        'title', 'slug', 'summary', 'description', 'objectives',
        'target_groups', 'geographic_coverage', 'seo_title', 'seo_description',
    ];

    protected array $extraFillable = [
        'strategic_pillar_id', 'start_date', 'end_date', 'featured_image_id',
    ];

    protected array $extraRules = [
        'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
        'start_date' => ['nullable', 'date'],
        'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        'featured_image_id' => ['nullable', 'exists:media,id'],
    ];

    /** @return list<string> */
    protected function indexRelations(): array
    {
        return ['translations', 'pillar', 'featuredImage'];
    }

    protected function applyIndexFilters(Builder $query, Request $request): void
    {
        $pillarId = $request->integer('pillar');
        if ($pillarId > 0) {
            $query->where('strategic_pillar_id', $pillarId);
        }
    }

    /** @return array<string, mixed> */
    protected function indexItem(Model $item, string $locale): array
    {
        /** @var Program $item */
        $pillarTitle = $item->pillar?->t('title', $locale) ?: $item->pillar?->t('title');

        return array_merge(parent::indexItem($item, $locale), [
            'pillar' => (string) ($pillarTitle ?? ''),
            'startDate' => $item->start_date?->toDateString(),
            'endDate' => $item->end_date?->toDateString(),
            'featuredImage' => $item->featuredImage ? [
                'url' => $item->featuredImage->url(),
                'alt' => $item->featuredImage->t('alt_text', $locale) ?: $item->featuredImage->t('alt_text'),
            ] : null,
        ]);
    }

    /** @return list<array{key: string, label: string, options: list<array{value: int, label: string}>}> */
    protected function filterOptions(string $locale): array
    {
        return [[
            'key' => 'pillar',
            'label' => (string) __('website.ui.cms_content_page.filters.pillar', [], $locale),
            'options' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                ->map(fn (StrategicPillar $pillar): array => [
                    'value' => $pillar->id,
                    'label' => (string) ($pillar->t('title', $locale) ?: $pillar->t('title') ?: $pillar->code),
                ])->all(),
        ]];
    }

    /** @return array<string, mixed> */
    protected function options(string $locale): array
    {
        return [
            'pillars' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                ->map(fn (StrategicPillar $pillar): array => [
                    'id' => $pillar->id,
                    'label' => (string) ($pillar->t('title', $locale) ?: $pillar->t('title')),
                ])->all(),
        ];
    }
}
