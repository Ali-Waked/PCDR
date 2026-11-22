<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Services\ContentQuery;
use App\Services\SiteSettings;
use Inertia\Inertia;
use Inertia\Response;

final class StaticPageController extends PublicController
{
    public function transparency(string $locale, ContentQuery $content): Response
    {
        return Inertia::render('Public/Transparency', [
            'principles' => collect(config('website.values', []))->only([2, 8])->map(fn ($value) => $value[$locale])->values()->all(),
            'documents' => $content->publishedPublications([
                'category' => 'policy',
            ], 12)->through(fn ($publication) => [
                'title' => $publication->t('title', $locale),
                'date' => $publication->publication_date?->toDateString(),
                'url' => $publication->route($locale),
            ])->items(),
            'reports' => $content->publishedPublications(['category' => 'annual_report'], 12)
                ->through(fn ($publication) => [
                    'title' => $publication->t('title', $locale),
                    'date' => $publication->publication_date?->toDateString(),
                    'url' => $publication->route($locale),
                ])->items(),
            'meta' => $this->seo($locale, __('website.navigation.transparency', [], $locale), path: "{$locale}/transparency"),
        ]);
    }

    public function partnerships(string $locale): Response
    {
        return Inertia::render('Public/Partnerships', [
            'meta' => $this->seo($locale, __('website.navigation.partnerships', [], $locale), path: "{$locale}/partnerships"),
        ]);
    }

    public function involve(string $locale, ContentQuery $content): Response
    {
        return Inertia::render('Public/Involve', [
            'areas' => $content->publishedPillars(11)->map(fn ($pillar) => [
                'code' => $pillar->code,
                'title' => $pillar->t('title', $locale),
                'url' => $pillar->route($locale),
            ])->all(),
            'meta' => $this->seo($locale, __('website.navigation.involve', [], $locale), path: "{$locale}/get-involved"),
        ]);
    }

    public function contact(string $locale, SiteSettings $settings): Response
    {
        return Inertia::render('Public/Contact', [
            'contact' => $settings->contactDetails($locale),
            'meta' => $this->seo($locale, __('website.navigation.contact', [], $locale), path: "{$locale}/contact"),
        ]);
    }
}
