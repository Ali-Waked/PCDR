<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Services\ContentQuery;
use App\Services\PartnerDirectory;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
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

    public function partners(string $locale, Request $request, PartnerDirectory $directory): Response
    {
        $category = mb_strcut(trim($request->string('category')->toString()), 0, 80);
        $directoryData = $directory->overview($locale, $category !== '' ? $category : null);
        $title = __('website.ui.partner_directory.seo_title', [], $locale);
        $description = __('website.ui.partner_directory.seo_description', [], $locale);

        return Inertia::render('Public/Partnerships', [
            'directory' => $directoryData,
            'meta' => $this->seo(
                $locale,
                $title,
                $description,
                path: "{$locale}/partners",
                overrides: [
                    'title' => $title,
                    'description' => $description,
                    'og' => ['title' => $title, 'description' => $description],
                    'twitter' => ['title' => $title, 'description' => $description],
                ],
            ),
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

    public function complaints(string $locale, SiteSettings $settings): Response
    {
        $title = __('website.navigation.complaints', [], $locale);
        $description = __('website.ui.complaints.meta_description', [], $locale);

        return Inertia::render('Public/Complaints', [
            'complaintsEmail' => $settings->complaintsEmail($locale) ?? '',
            'meta' => $this->seo(
                $locale,
                $title,
                $description,
                path: "{$locale}/complaints",
                overrides: [
                    'title' => __('website.ui.complaints.seo_title', [], $locale),
                    'og' => [
                        'title' => __('website.ui.complaints.seo_title', [], $locale),
                        'description' => $description,
                    ],
                    'twitter' => [
                        'title' => __('website.ui.complaints.seo_title', [], $locale),
                        'description' => $description,
                    ],
                ],
            ),
        ]);
    }
}
