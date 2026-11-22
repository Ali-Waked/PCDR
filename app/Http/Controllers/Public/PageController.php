<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Services\ContentQuery;
use Inertia\Inertia;
use Inertia\Response;

final class PageController extends PublicController
{
    public function show(string $locale, string $slug, ContentQuery $content): Response
    {
        $page = $content->findPublishedPage($slug);
        abort_if($page === null, 404);

        return Inertia::render('Public/Page', [
            'page' => [
                'title' => $page->t('title', $locale),
                'summary' => $page->t('summary', $locale),
                'body' => $page->t('body', $locale),
            ],
            'meta' => $this->seo(
                $locale,
                (string) $page->t('title', $locale),
                $page->t('seo_description', $locale) ?? $page->t('summary', $locale),
                "{$locale}/pages/{$slug}",
            ),
        ]);
    }
}
