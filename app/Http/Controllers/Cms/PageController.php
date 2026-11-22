<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Models\Page;

final class PageController extends ContentController
{
    protected string $modelClass = Page::class;

    protected string $resource = 'pages';

    protected string $label = 'Pages';

    protected string $routeBase = 'cms.pages';

    protected string $foreignKey = 'page_id';

    protected array $translationFields = [
        'title', 'slug', 'summary', 'body', 'seo_title', 'seo_description',
        'canonical_url', 'og_image', 'robots',
    ];

    protected array $extraFillable = ['parent_id', 'is_system'];

    protected array $extraRules = [
        'parent_id' => ['nullable', 'exists:pages,id'],
        'is_system' => ['boolean'],
        'translations.*.canonical_url' => ['nullable', 'url', 'max:500'],
    ];
}
