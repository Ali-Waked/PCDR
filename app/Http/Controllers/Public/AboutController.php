<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Services\ContentQuery;
use Inertia\Inertia;
use Inertia\Response;

final class AboutController extends PublicController
{
    private const FOCI = ['overview', 'vision', 'mission', 'values', 'direction', 'groups', 'approach'];

    public function __invoke(string $locale, ContentQuery $content, string $focus = 'overview'): Response
    {
        $focus = in_array($focus, self::FOCI, true) ? $focus : 'overview';

        return Inertia::render('Public/About', [
            'focus' => $focus,
            'vision' => config("website.vision.{$locale}"),
            'mission' => config("website.mission.{$locale}"),
            'values' => collect(config('website.values', []))->map(fn ($value, $index) => [
                'number' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'label' => $value[$locale],
            ])->values()->all(),
            'targetGroups' => collect(config('website.about.target_groups', []))->map(fn ($group) => $group[$locale])->all(),
            'approach' => collect(config('website.about.approach', []))->map(fn ($step) => $step[$locale])->all(),
            'pillars' => $content->publishedPillars()->map(fn ($pillar) => [
                'code' => $pillar->code,
                'title' => $pillar->t('title', $locale),
                'url' => $pillar->route($locale),
            ])->all(),
            'meta' => $this->seo(
                $locale,
                __('website.navigation.about', [], $locale),
                (string) config("website.description.{$locale}"),
                "{$locale}/about".($focus === 'overview' ? '' : "/{$focus}"),
            ),
        ]);
    }
}
