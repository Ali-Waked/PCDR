<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\Program;
use App\Models\StrategicPillar;

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

    protected function options(): array
    {
        return [
            'pillars' => StrategicPillar::query()->with('translations')->orderBy('code')->get()
                ->map(fn ($pillar) => ['id' => $pillar->id, 'label' => $pillar->t('title')])->all(),
        ];
    }
}
