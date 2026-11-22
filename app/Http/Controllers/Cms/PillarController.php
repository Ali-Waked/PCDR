<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\StrategicPillar;

final class PillarController extends ContentController
{
    protected string $modelClass = StrategicPillar::class;

    protected string $resource = 'pillars';

    protected string $label = 'Strategic pillars';

    protected string $routeBase = 'cms.pillars';

    protected string $foreignKey = 'strategic_pillar_id';

    protected array $translationFields = [
        'title', 'slug', 'short_title', 'description', 'objectives',
        'target_groups', 'seo_title', 'seo_description',
    ];

    protected array $extraFillable = ['code', 'featured_image_id', 'is_featured'];

    protected array $extraRules = [
        'code' => ['required', 'string', 'size:2'],
        'featured_image_id' => ['nullable', 'exists:media,id'],
        'is_featured' => ['boolean'],
    ];
}
