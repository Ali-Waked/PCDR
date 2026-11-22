<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\StrategicPillar;
use App\Services\ContentQuery;
use Inertia\Inertia;
use Inertia\Response;

final class PillarController extends PublicController
{
    public function index(string $locale, ContentQuery $content): Response
    {
        return Inertia::render('Public/Pillars/Index', [
            'pillars' => $content->publishedPillars()->map(fn ($pillar) => [
                'code' => $pillar->code,
                'title' => $pillar->t('title', $locale),
                'shortTitle' => $pillar->t('short_title', $locale),
                'description' => $pillar->t('description', $locale),
                'objectives' => $pillar->objectiveLines(),
                'url' => $pillar->route($locale),
                'featured' => $pillar->is_featured,
            ])->all(),
            'meta' => $this->seo($locale, __('website.navigation.pillars', [], $locale), path: "{$locale}/strategic-areas"),
        ]);
    }

    public function show(string $locale, string $slug, ContentQuery $content): Response
    {
        $pillar = $content->findPublished(StrategicPillar::class, $slug);
        abort_if($pillar === null, 404);

        /** @var StrategicPillar $pillar */
        return Inertia::render('Public/Pillars/Show', [
            'pillar' => [
                'code' => $pillar->code,
                'title' => $pillar->t('title', $locale),
                'description' => $pillar->t('description', $locale),
                'objectives' => $pillar->objectiveLines(),
                'targetGroups' => $pillar->targetGroupLines(),
                'image' => $pillar->featuredImage?->url(),
                'imageAlt' => $pillar->featuredImage?->t('alt_text', $locale),
            ],
            'programs' => $pillar->programs()->published()->with('translations')->orderBy('display_order')->get()
                ->map(fn ($program) => [
                    'title' => $program->t('title', $locale),
                    'summary' => $program->t('summary', $locale),
                    'url' => $program->route($locale),
                ])->all(),
            'projects' => $pillar->projects()->published()->with('translations')->orderBy('display_order')->get()
                ->map(fn ($project) => [
                    'title' => $project->t('title', $locale),
                    'status' => $project->statusLabel($locale),
                    'url' => $project->route($locale),
                ])->all(),
            'articles' => $pillar->articles()->published()->with('translations')->orderByDesc('published_at')->limit(4)->get()
                ->map(fn ($article) => [
                    'title' => $article->t('title', $locale),
                    'excerpt' => $article->t('excerpt', $locale),
                    'url' => $article->route($locale),
                ])->all(),
            'publications' => $pillar->publications()->published()->with('translations')->limit(4)->get()
                ->map(fn ($publication) => [
                    'title' => $publication->t('title', $locale),
                    'url' => $publication->route($locale),
                ])->all(),
            'otherPillars' => $content->publishedPillars()->reject(fn ($item) => $item->id === $pillar->id)
                ->map(fn ($item) => [
                    'code' => $item->code,
                    'title' => $item->t('title', $locale),
                    'url' => $item->route($locale),
                ])->values()->all(),
            'meta' => $this->seo(
                $locale,
                (string) ($pillar->t('title', $locale) ?: $pillar->t('title')),
                $pillar->t('description', $locale),
                "{$locale}/strategic-areas/{$slug}",
                $pillar->featuredImage,
            ),
        ]);
    }
}
