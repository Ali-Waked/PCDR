<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Enums\ProjectStatus;
use App\Models\Program;
use App\Models\Project;
use App\Models\StrategicPillar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class ProjectController extends ContentController
{
    protected string $modelClass = Project::class;

    protected string $resource = 'programs';

    protected string $uiResource = 'projects';

    protected string $label = 'Projects';

    protected string $pageTranslationKey = 'projects';

    protected string $routeBase = 'cms.projects';

    protected string $foreignKey = 'project_id';

    protected string $statusColumn = 'workflow_status';

    protected array $translationFields = [
        'title', 'slug', 'summary', 'description', 'objectives',
        'target_groups', 'geographic_coverage', 'seo_title', 'seo_description',
    ];

    protected array $extraFillable = [
        // `project_status` is mapped to the lifecycle `status` column in
        // attributes(); the publication workflow uses `workflow_status`.
        'program_id', 'strategic_pillar_id', 'start_date', 'end_date', 'featured_image_id',
    ];

    protected array $extraRules = [
        'program_id' => ['nullable', 'exists:programs,id'],
        'strategic_pillar_id' => ['nullable', 'exists:strategic_pillars,id'],
        'project_status' => ['required', 'in:planned,ongoing,completed,archived'],
        'start_date' => ['nullable', 'date'],
        'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        'featured_image_id' => ['nullable', 'exists:media,id'],
    ];

    /** @param array<string, mixed> $data */
    protected function attributes(array $data): array
    {
        $attributes = parent::attributes($data);
        $attributes['status'] = ProjectStatus::from($data['project_status']);
        $attributes['workflow_status'] = $data['status'];

        return $attributes;
    }

    protected function afterSave(Model $record, array $data): void
    {
        //
    }

    /** @return list<string> */
    protected function indexRelations(): array
    {
        return ['translations', 'program', 'pillar', 'featuredImage'];
    }

    protected function applyIndexFilters(Builder $query, Request $request): void
    {
        $programId = $request->integer('program');
        if ($programId > 0) {
            $query->where('program_id', $programId);
        }

        $pillarId = $request->integer('pillar');
        if ($pillarId > 0) {
            $query->where('strategic_pillar_id', $pillarId);
        }

        $projectStatus = $request->string('project_status')->toString();
        if (in_array($projectStatus, ProjectStatus::values(), true)) {
            $query->where('status', $projectStatus);
        }
    }

    /** @return array<string, mixed> */
    protected function indexItem(Model $item, string $locale): array
    {
        /** @var Project $item */
        $programTitle = $item->program?->t('title', $locale) ?: $item->program?->t('title');
        $pillarTitle = $item->pillar?->t('title', $locale) ?: $item->pillar?->t('title');
        $lifecycle = $item->status instanceof ProjectStatus ? $item->status : ProjectStatus::Planned;

        return array_merge(parent::indexItem($item, $locale), [
            'program' => (string) ($programTitle ?? ''),
            'pillar' => (string) ($pillarTitle ?? ''),
            'projectStatus' => $lifecycle->value,
            'projectStatusLabel' => $lifecycle->label($locale),
            'startDate' => $item->start_date?->toDateString(),
            'endDate' => $item->end_date?->toDateString(),
            'featuredImage' => $item->featuredImage ? [
                'url' => $item->featuredImage->url(),
                'alt' => $item->featuredImage->t('alt_text', $locale) ?: $item->featuredImage->t('alt_text'),
            ] : null,
        ]);
    }

    /**
     * Return the lifecycle stage to the form so editing a project does not
     * silently reset it to the default stage.
     *
     * @return array<string, mixed>
     */
    protected function recordAttributes(Model $record): array
    {
        /** @var Project $record */
        $lifecycle = $record->status instanceof ProjectStatus ? $record->status : ProjectStatus::Planned;

        return array_merge(parent::recordAttributes($record), [
            'project_status' => $lifecycle->value,
        ]);
    }

    /** @return list<array{key: string, label: string, options: list<array{value: string|int, label: string}>}> */
    protected function filterOptions(string $locale): array
    {
        return [
            [
                'key' => 'program',
                'label' => (string) __('website.ui.cms_content_page.filters.program', [], $locale),
                'options' => Program::query()->with('translations')->orderBy('id')->get()
                    ->map(fn (Program $program): array => [
                        'value' => $program->id,
                        'label' => (string) ($program->t('title', $locale) ?: $program->t('title')),
                    ])->all(),
            ],
            [
                'key' => 'pillar',
                'label' => (string) __('website.ui.cms_content_page.filters.pillar', [], $locale),
                'options' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                    ->map(fn (StrategicPillar $pillar): array => [
                        'value' => $pillar->id,
                        'label' => (string) ($pillar->t('title', $locale) ?: $pillar->t('title') ?: $pillar->code),
                    ])->all(),
            ],
            [
                'key' => 'project_status',
                'label' => (string) __('website.ui.cms_content_page.filters.project_status', [], $locale),
                'options' => collect(ProjectStatus::cases())
                    ->map(fn (ProjectStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->label($locale),
                    ])->all(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function options(string $locale): array
    {
        return [
            'programs' => Program::query()->with('translations')->get()
                ->map(fn (Program $program): array => [
                    'id' => $program->id,
                    'label' => (string) ($program->t('title', $locale) ?: $program->t('title')),
                ])->all(),
            'pillars' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                ->map(fn (StrategicPillar $pillar): array => [
                    'id' => $pillar->id,
                    'label' => (string) ($pillar->t('title', $locale) ?: $pillar->t('title')),
                ])->all(),
            'projectStatuses' => collect(ProjectStatus::cases())->map(fn (ProjectStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label($locale),
            ])->all(),
        ];
    }
}
