<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ContentStatus;
use App\Exceptions\InvalidWorkflowTransition;

/**
 * Builds localized, user-facing workflow feedback from the transition graph
 * that is actually executed by TransitionContentStatus.
 */
final class WorkflowFeedback
{
    /**
     * @param  array{value: string, label: string}|null  $nextAction
     * @return array<string, mixed>
     */
    public static function invalidTransition(
        InvalidWorkflowTransition $exception,
        string $locale = 'ar',
        ?array $nextAction = null,
    ): array {
        $graph = self::graph();
        $current = $exception->from;
        $requested = $exception->to;
        $currentLabel = self::statusLabel($current, $locale);
        $requestedLabel = self::statusLabel($requested, $locale);
        $path = self::path($current, $requested, $graph);

        return [
            'type' => 'invalid_transition',
            'title' => __('website.ui.cms_pillars_page.alert.invalid_title', [], $locale),
            'message' => __('website.ui.cms_pillars_page.alert.invalid_message', [], $locale),
            'current' => ['value' => $current->value, 'label' => $currentLabel],
            'requested' => ['value' => $requested->value, 'label' => $requestedLabel],
            'reason' => __('website.ui.cms_pillars_page.alert.invalid_reason', [
                'current' => $currentLabel,
                'requested' => $requestedLabel,
            ], $locale),
            'sequence' => array_map(
                fn (string $value): string => self::statusLabel(ContentStatus::from($value), $locale),
                $path,
            ),
            'nextAction' => $nextAction,
            'nextLabel' => __('website.ui.cms_pillars_page.alert.next_action_label', [], $locale),
            'noNextLabel' => __('website.ui.cms_pillars_page.alert.no_next_available', [], $locale),
            'sequenceLabel' => __('website.ui.cms_pillars_page.alert.required_sequence', [], $locale),
            'currentLabel' => __('website.ui.cms_pillars_page.alert.current_status', [], $locale),
            'requestedLabel' => __('website.ui.cms_pillars_page.alert.requested_status', [], $locale),
            'understoodLabel' => __('website.ui.cms_pillars_page.alert.understood', [], $locale),
            'detailsLabel' => __('website.ui.cms_pillars_page.alert.view_details', [], $locale),
        ];
    }

    /** @return array<string, mixed> */
    public static function permissionDenied(string $locale = 'ar'): array
    {
        return self::simpleFeedback('permission_denied', 'permission_denied_title', $locale);
    }

    /** @return array<string, mixed> */
    public static function missingRequirements(string $locale = 'ar'): array
    {
        return self::simpleFeedback('missing_requirements', 'missing_requirements_title', $locale);
    }

    /** @return array<string, mixed> */
    public static function unexpectedFailure(string $locale = 'ar'): array
    {
        return self::simpleFeedback('unexpected_failure', 'unexpected_failure_title', $locale);
    }

    /** @return array<string, list<string>> */
    public static function graph(): array
    {
        $graph = [];

        foreach (ContentStatus::cases() as $status) {
            $graph[$status->value] = array_values(array_filter(
                (array) config("cms.transitions.{$status->value}", []),
                static fn (mixed $value): bool => is_string($value) && ContentStatus::tryFrom($value) !== null,
            ));
        }

        return $graph;
    }

    /**
     * Shortest route between two states in the executed graph. Falls back to
     * the route from the initial state when the current state cannot reach the
     * requested one at all.
     *
     * @param  array<string, list<string>>  $graph
     * @return list<string>
     */
    public static function sequence(ContentStatus $current, ContentStatus $requested, array $graph, string $locale = 'ar'): array
    {
        return array_map(
            fn (string $value): string => self::statusLabel(ContentStatus::from($value), $locale),
            self::path($current, $requested, $graph),
        );
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @return list<string>
     */
    public static function path(ContentStatus $current, ContentStatus $requested, array $graph): array
    {
        $route = self::shortestPath($current->value, $requested->value, $graph);

        if ($route === null) {
            $route = self::shortestPath(ContentStatus::Draft->value, $requested->value, $graph);
        }

        return $route ?? [$requested->value];
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @return list<string>|null
     */
    private static function shortestPath(string $from, string $to, array $graph): ?array
    {
        if ($from === $to) {
            return [$from];
        }

        $queue = [[$from]];
        $visited = [$from => true];

        while ($queue !== []) {
            /** @var list<string> $path */
            $path = array_shift($queue);
            $node = $path[count($path) - 1];

            foreach ($graph[$node] ?? [] as $next) {
                if (isset($visited[$next])) {
                    continue;
                }

                $candidate = [...$path, $next];
                if ($next === $to) {
                    return $candidate;
                }

                $visited[$next] = true;
                $queue[] = $candidate;
            }
        }

        return null;
    }

    private static function statusLabel(ContentStatus $status, string $locale): string
    {
        return (string) __("website.ui.{$status->value}", [], $locale);
    }

    /**
     * @return array<string, mixed>
     */
    private static function simpleFeedback(string $type, string $titleKey, string $locale): array
    {
        return [
            'type' => $type,
            'title' => __("website.ui.cms_pillars_page.alert.{$titleKey}", [], $locale),
            'message' => __("website.ui.cms_pillars_page.alert.{$type}_message", [], $locale),
            'understoodLabel' => __('website.ui.cms_pillars_page.alert.understood', [], $locale),
            'detailsLabel' => __('website.ui.cms_pillars_page.alert.view_details', [], $locale),
        ];
    }
}
