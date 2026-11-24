<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Enums\ContentStatus;
use App\Exceptions\InvalidWorkflowTransition;
use App\Models\StrategicPillar;
use App\Models\User;
use App\Services\CmsAccess;
use App\Support\WorkflowFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

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

    /**
     * Dedicated, backend-filtered index for the strategic pillars page.
     */
    public function index(string $locale): Response
    {
        $this->authorizeCms($this->resource.'.view');

        $request = request();
        $search = trim((string) $request->query('search', ''));
        $requestedStatus = (string) $request->query('status', 'all');
        $status = in_array($requestedStatus, ContentStatus::values(), true) ? $requestedStatus : 'all';

        $query = StrategicPillar::query()->with('translations');

        if ($search !== '') {
            $query->search($search);
        }

        if ($status !== 'all') {
            $query->workflowStatus($status);
        }

        $items = $query->orderByDesc('updated_at')->paginate(12);
        $items->withQueryString();

        $access = app(CmsAccess::class);
        $user = $request->user();
        $graph = WorkflowFeedback::graph();
        $statuses = $this->statusOptions($locale);
        $actions = $this->transitionActions($graph, $statuses, $access, $user, $locale);
        $paths = $this->transitionPaths($graph);
        $counts = $this->statusCounts();

        return Inertia::render('Cms/Pillars/Index', [
            'resource' => $this->resource,
            'label' => $this->label,
            'routeBase' => $this->routeBase,
            'pageTitle' => __('website.ui.cms_pillars_page.title', [], $locale),
            'pageDescription' => __('website.ui.cms_pillars_page.description', [], $locale),
            'items' => $items->through(function (StrategicPillar $item) use ($locale, $actions): array {
                $statusValue = ContentStatus::valueOf($item->status);
                $available = $actions[$statusValue] ?? [];

                return [
                    'id' => $item->getKey(),
                    'code' => $item->code,
                    'title' => $item->t('title', $locale) ?: $item->t('title'),
                    'description' => Str::limit(strip_tags((string) ($item->t('description', $locale) ?: $item->t('description'))), 160),
                    'status' => $statusValue,
                    'publishedAt' => $item->published_at?->toDateString(),
                    'updatedAt' => $item->updated_at?->toDateString(),
                    'locales' => $item->availableLocales(),
                    'isFeatured' => (bool) $item->is_featured,
                    'nextAction' => $available[0] ?? null,
                ];
            })->items(),
            'pagination' => [
                'currentPage' => $items->currentPage(),
                'lastPage' => $items->lastPage(),
                'perPage' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'links' => $items->linkCollection()
                    ->map(fn (array $link): array => [
                        'url' => $link['url'],
                        'label' => strip_tags((string) $link['label']),
                        'active' => (bool) $link['active'],
                    ])->all(),
            ],
            'counts' => $counts,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => array_values($statuses),
            'workflow' => [
                'graph' => $graph,
                'paths' => $paths,
                'actions' => $actions,
                'statusLabels' => array_map(
                    static fn (array $status): string => $status['label'],
                    $statuses,
                ),
                'sequenceLabel' => __('website.ui.cms_pillars_page.workflow_sequence', [], $locale),
                'forbiddenHint' => __('website.ui.cms_pillars_page.forbidden_hint', [], $locale),
            ],
            'can' => [
                'view' => true,
                'create' => $user !== null && $access->allows($user, $this->resource.'.create'),
                'update' => $user !== null && $access->allows($user, $this->resource.'.update'),
                'delete' => $user !== null && $access->allows($user, $this->resource.'.delete'),
                'publish' => $user !== null && $access->allows($user, $this->resource.'.publish'),
                'unpublish' => $user !== null && $access->allows($user, $this->resource.'.unpublish'),
            ],
        ]);
    }

    /**
     * Keep the user's current page and filters when the domain rejects a
     * transition, and return an Inertia-compatible localized redirect.
     */
    public function status(string $locale, Request $request, int $content): RedirectResponse
    {
        try {
            return parent::status($locale, $request, $content);
        } catch (InvalidWorkflowTransition $exception) {
            // Non-Inertia clients keep the existing 409/JSON contract. Only
            // Inertia visits receive the localized redirect with workflow data.
            if (! $request->inertia()) {
                throw $exception;
            }

            $user = $request->user();
            $graph = WorkflowFeedback::graph();
            $actions = $this->transitionActions(
                $graph,
                $this->statusOptions($locale),
                app(CmsAccess::class),
                $user,
                $locale,
            );
            $nextAction = $actions[$exception->from->value][0] ?? null;

            $feedback = WorkflowFeedback::invalidTransition($exception, $locale, $nextAction);

            return to_route('cms.pillars.index', $this->redirectQuery($locale, $request))
                ->with('flash', [
                    'error' => $feedback['title'],
                    'workflow' => $feedback,
                ])
                ->setStatusCode(303);
        }
    }

    /** @return array<string, string> */
    private function redirectQuery(string $locale, Request $request): array
    {
        $query = ['locale' => $locale];
        $filters = $request->input('filters');

        if (is_array($filters)) {
            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query['search'] = Str::limit($search, 255, '');
            }

            $status = (string) ($filters['status'] ?? 'all');
            if (in_array($status, ContentStatus::values(), true)) {
                $query['status'] = $status;
            }

            $page = max(1, (int) ($filters['page'] ?? 1));
            if ($page > 1) {
                $query['page'] = (string) $page;
            }
        }

        return $query;
    }

    /** @return array<string, array{value: string, label: string, description: string}> */
    private function statusOptions(string $locale): array
    {
        $statuses = [];

        foreach (ContentStatus::cases() as $status) {
            $statuses[$status->value] = [
                'value' => $status->value,
                'label' => (string) __("website.ui.{$status->value}", [], $locale),
                'description' => (string) __("website.ui.cms_pillars_page.status_descriptions.{$status->value}", [], $locale),
            ];
        }

        return $statuses;
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @param  array<string, array{value: string, label: string, description: string}>  $statuses
     * @return array<string, list<array{value: string, label: string, description: string, permission: string, allowed: bool}>>
     */
    private function transitionActions(
        array $graph,
        array $statuses,
        CmsAccess $access,
        ?User $user,
        string $locale,
    ): array {
        $actions = [];

        foreach (ContentStatus::cases() as $status) {
            $actions[$status->value] = [];

            foreach ($graph[$status->value] ?? [] as $targetValue) {
                $target = ContentStatus::tryFrom($targetValue);
                if ($target === null || ! isset($statuses[$targetValue])) {
                    continue;
                }

                $permission = $this->transitionPermission($status, $target);
                $allowed = $user !== null && $access->allows($user, $permission);

                if (! $allowed) {
                    continue;
                }

                $actions[$status->value][] = [
                    'value' => $targetValue,
                    'label' => (string) __("website.ui.cms_pillars_page.actions.{$targetValue}.label", [], $locale),
                    'description' => (string) __("website.ui.cms_pillars_page.actions.{$targetValue}.description", [], $locale),
                    'permission' => $permission,
                    'allowed' => true,
                ];
            }
        }

        return $actions;
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @return array<string, array<string, list<string>>>
     */
    private function transitionPaths(array $graph): array
    {
        $paths = [];

        foreach (ContentStatus::cases() as $current) {
            foreach (ContentStatus::cases() as $target) {
                $paths[$current->value][$target->value] = WorkflowFeedback::path($current, $target, $graph);
            }
        }

        return $paths;
    }

    /** @return array<string, int> */
    private function statusCounts(): array
    {
        $counts = ['all' => StrategicPillar::query()->count()];

        foreach (ContentStatus::cases() as $status) {
            $counts[$status->value] = StrategicPillar::query()
                ->where('status', $status->value)
                ->count();
        }

        return $counts;
    }
}
