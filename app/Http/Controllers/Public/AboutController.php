<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Services\ContentQuery;
use Inertia\Inertia;
use Inertia\Response;

final class AboutController extends PublicController
{
    private const FOCI = ['overview', 'vision', 'mission', 'values', 'direction', 'groups', 'approach'];

    /** @var array<string, string> */
    private const TITLE_KEYS = [
        'overview' => 'website.navigation.about',
        'vision' => 'website.ui.vision',
        'mission' => 'website.ui.mission',
        'values' => 'website.ui.values',
        'direction' => 'website.ui.strategic_direction',
        'groups' => 'website.ui.target_groups',
        'approach' => 'website.ui.approach',
    ];

    public function __invoke(string $locale, ContentQuery $content, string $focus = 'overview'): Response
    {
        $focus = in_array($focus, self::FOCI, true) ? $focus : 'overview';

        $values = collect(config('website.values', []))->map(fn ($value, $index) => [
            'number' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            'label' => $value[$locale],
        ])->values()->all();
        $targetGroups = collect(config('website.about.target_groups', []))->map(fn ($group) => $group[$locale])->all();
        $approach = collect(config('website.about.approach', []))->map(fn ($step) => $step[$locale])->all();

        $title = __(self::TITLE_KEYS[$focus], [], $locale);
        $description = $this->focusDescription($focus, $locale, $values, $targetGroups, $approach);

        return Inertia::render('Public/About', [
            'focus' => $focus,
            'focusTitle' => $title,
            'focusDescription' => $description,
            'vision' => config("website.vision.{$locale}"),
            'mission' => config("website.mission.{$locale}"),
            'values' => $values,
            'targetGroups' => $targetGroups,
            'approach' => $approach,
            'pillars' => $content->publishedPillars()->map(fn ($pillar) => [
                'code' => $pillar->code,
                'title' => $pillar->t('title', $locale),
                'description' => $pillar->t('description', $locale),
                'url' => $pillar->route($locale),
            ])->all(),
            'meta' => $this->seo(
                $locale,
                $title,
                $description,
                "{$locale}/about".($focus === 'overview' ? '' : "/{$focus}"),
            ),
        ]);
    }

    /**
     * @param  list<array{number: string, label: string}>  $values
     * @param  list<string>  $targetGroups
     * @param  list<string>  $approach
     */
    private function focusDescription(string $focus, string $locale, array $values, array $targetGroups, array $approach): string
    {
        return match ($focus) {
            'vision' => (string) config("website.vision.{$locale}"),
            'mission' => (string) config("website.mission.{$locale}"),
            'values' => __('website.our_values', [], $locale).': '.implode(' · ', array_column($values, 'label')),
            'direction' => __('website.strategic_areas_intro', [], $locale),
            'groups' => __('website.ui.target_groups', [], $locale).': '.implode(' · ', $targetGroups),
            'approach' => implode(' · ', $approach),
            default => (string) config("website.description.{$locale}"),
        };
    }
}
