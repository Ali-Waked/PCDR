<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Enums\ProjectStatus;
use App\Models\Program;
use App\Models\Project;
use App\Models\StrategicPillar;
use Illuminate\Database\Eloquent\Model;

final class ProjectController extends ContentController
{
    protected string $modelClass = Project::class;

    protected string $resource = 'programs';

    protected string $label = 'Projects';

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

    protected function options(): array
    {
        return [
            'programs' => Program::query()->with('translations')->get()
                ->map(fn ($program) => ['id' => $program->id, 'label' => $program->t('title')])->all(),
            'pillars' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                ->map(fn ($pillar) => ['id' => $pillar->id, 'label' => $pillar->t('title')])->all(),
            'projectStatuses' => collect(ProjectStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(app()->getLocale()),
            ])->all(),
        ];
    }
}
