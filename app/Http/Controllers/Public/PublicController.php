<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Seo;

abstract class PublicController extends Controller
{
    /** @param array<string, mixed> $overrides */
    protected function seo(
        string $locale,
        string $title,
        ?string $description = null,
        ?string $path = null,
        ?Media $image = null,
        array $overrides = [],
    ): array {
        return app(Seo::class)->for($locale, $title, $description, $path, $image, $overrides);
    }
}
