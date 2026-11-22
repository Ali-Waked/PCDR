<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Support\Phase5Support;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Reads existing domain dates; never creates or duplicates a second date store. */
final class CalendarAggregationService
{
    /** @var array<string, array{resource_type: string, id_column: string}> */
    private const RESOURCE_MAP = [
        'task' => ['resource_type' => 'task', 'id_column' => 'id'],
        'workflow_step' => ['resource_type' => 'workflow_instance', 'id_column' => 'instance_id'],
        'referral' => ['resource_type' => 'referral', 'id_column' => 'id'],
    ];

    public function __construct(private readonly Phase5Authorization $authorization) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function calendar(array $filters = []): array
    {
        $timezone = (string) config('phase5.timezone', 'Asia/Gaza');
        $views = array_values((array) config('phase5.calendar.views', []));
        $view = in_array($filters['view'] ?? null, $views, true) ? (string) $filters['view'] : 'month';
        $sources = (array) config('phase5.calendar.sources', []);
        $requestedSources = is_array($filters['sources'] ?? null)
            ? array_values(array_filter($filters['sources'], static fn (mixed $source): bool => is_string($source)))
            : array_keys($sources);
        $category = isset($filters['category']) ? (string) $filters['category'] : null;

        [$start, $end] = $this->range($view, $filters, $timezone);
        $events = [];
        $perSource = 100;
        $maximum = (int) config('phase5.calendar.max_events', 500);

        foreach ($requestedSources as $source) {
            if (count($events) >= $maximum || ! isset($sources[$source]) || ! is_array($sources[$source])) {
                continue;
            }

            $definition = $sources[$source];
            $permission = (string) ($definition['permission'] ?? '');
            if ($permission === '' || ! $this->authorization->allows($filters['user'], $permission)) {
                continue;
            }

            $labelKey = (string) ($definition['label_key'] ?? $source);
            if ($category !== null && $category !== $labelKey) {
                continue;
            }

            $table = (string) ($definition['table'] ?? '');
            $dateColumn = (string) ($definition['date_column'] ?? '');
            if ($table === '' || $dateColumn === '' || ! preg_match('/^[a-z0-9_]+$/i', $table.$dateColumn)) {
                continue;
            }

            $mapping = self::RESOURCE_MAP[$source] ?? null;
            $idColumn = $mapping['id_column'] ?? 'id';
            $columns = array_values(array_unique(['id', $dateColumn, $idColumn]));

            $query = DB::table($table)
                ->select($columns)
                ->whereNotNull($dateColumn)
                ->whereBetween($dateColumn, [
                    $start->toDateTimeString(),
                    $end->toDateTimeString(),
                ]);

            if ($this->tableHasSoftDeletes($table)) {
                $query->whereNull('deleted_at');
            }

            $rows = $query
                ->orderBy($dateColumn)
                ->orderBy('id')
                ->limit($perSource)
                ->get();

            foreach ($rows as $row) {
                if (count($events) >= $maximum) {
                    break;
                }

                $resourceType = $mapping['resource_type'] ?? null;
                $resourceId = (int) $row->{$idColumn};

                if ($resourceType !== null
                    && ! $this->authorization->canViewResource($filters['user'], $resourceType, $resourceId)) {
                    continue;
                }

                try {
                    $date = CarbonImmutable::parse((string) $row->{$dateColumn}, $timezone)->toIso8601String();
                } catch (Throwable) {
                    continue;
                }

                $events[] = [
                    'id' => Phase5Support::clean($source.'-'.(string) $row->id, 128),
                    'source' => $source,
                    'category' => $labelKey,
                    'title_key' => 'phase5.calendar.'.$labelKey,
                    'date' => $date,
                    'timezone' => $timezone,
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceType === null ? null : $resourceId,
                ];
            }
        }

        usort($events, static fn (array $left, array $right): int => strcmp($left['date'], $right['date'])
            ?: strcmp($left['id'], $right['id']));

        return [
            'events' => array_values($events),
            'views' => $views,
            'sources' => array_values(array_map(
                static fn (string $key, mixed $definition): array => [
                    'key' => $key,
                    'label_key' => is_array($definition) ? (string) ($definition['label_key'] ?? $key) : $key,
                ],
                array_keys($sources),
                array_values($sources),
            )),
            'timezone' => $timezone,
        ];
    }

    /** @param array<string, mixed> $filters @return array{CarbonImmutable, CarbonImmutable} */
    private function range(string $view, array $filters, string $timezone): array
    {
        $now = CarbonImmutable::now($timezone);

        if (! empty($filters['from'])) {
            $start = CarbonImmutable::parse((string) $filters['from'], $timezone)->startOfDay();
        } else {
            $start = match ($view) {
                'week' => $now->startOfWeek(CarbonImmutable::MONDAY),
                'agenda' => $now->startOfDay(),
                default => $now->startOfMonth(),
            };
        }

        if (! empty($filters['to'])) {
            $end = CarbonImmutable::parse((string) $filters['to'], $timezone)->endOfDay();
        } else {
            $end = match ($view) {
                'week' => $start->endOfWeek(CarbonImmutable::SUNDAY),
                'agenda' => $start->addDays(14)->endOfDay(),
                default => $start->endOfMonth(),
            };
        }

        if ($end->lessThan($start)) {
            $end = $start->copy()->endOfDay();
        }

        if ($start->diffInDays($end) > 62) {
            $end = $start->addDays(62)->endOfDay();
        }

        return [$start, $end];
    }

    private function tableHasSoftDeletes(string $table): bool
    {
        return in_array($table, ['tasks', 'referrals', 'activities', 'committee_meetings'], true);
    }
}
