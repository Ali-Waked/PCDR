<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditRedactor;
use App\Support\CsvSafeWriter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

final class AuditLogQueryService
{
    /** @var array<string, list<string>> */
    private const CLASS_PATTERNS = [
        'security' => ['auth.%', 'login%', 'logout%', 'password%', 'security.%', 'permission.%', 'role.%', 'audit_logs.%', 'audit_archives.%'],
        'content' => ['content.%', 'news.%', 'program%', 'project%', 'publication%', 'media.%', 'pillar%', 'homepage%', 'navigation%', 'page.%'],
        'people' => ['user.%', 'users.%', 'inquiry%', 'contact%'],
        'system' => ['settings.%', 'platform.%', 'system.%', 'phase5.%'],
    ];

    /** @var array<string, list<string>> */
    private const SEVERITY_PATTERNS = [
        'critical' => ['%.delete', '%.destroy', '%.revoke', 'password.reset%', 'users.reset_password', 'roles.permissions', 'audit_archives.cleanup', '%.prune'],
        'warning' => ['%.update', '%.edit', '%.change', '%.status', '%.publish', '%.unpublish', '%.archive', '%.assign%', '%.grant%', '%.hold', '%.release_hold'],
    ];

    /** @return array<string, string> */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->toString(),
            'actor' => $request->string('actor')->trim()->toString(),
            'action' => $request->string('action')->trim()->toString(),
            'subject' => $request->string('subject')->trim()->toString(),
            'from' => $this->validDate($request->string('from')->trim()->toString()),
            'to' => $this->validDate($request->string('to')->trim()->toString()),
            'event_class' => $this->validChoice($request->string('event_class')->trim()->toString(), array_keys(self::CLASS_PATTERNS)),
            'severity' => $this->validChoice($request->string('severity')->trim()->toString(), ['critical', 'warning', 'info']),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function list(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->with('actor:id,name')
            ->select([
                'id', 'actor_id', 'action', 'subject_type', 'subject_id',
                'request_id', 'created_at', 'archive_id',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => $this->presentSummary($log));
    }

    /** @param array<string, string> $filters @return array<string, int> */
    public function statistics(array $filters): array
    {
        $query = $this->baseQuery($filters);

        $security = (clone $query)->where(function (Builder $inner): void {
            $this->applyPatternFilter($inner, self::CLASS_PATTERNS['security']);
        })->count();

        $created = (clone $query)->where(function (Builder $inner): void {
            $inner->where('action', 'like', '%create%')
                ->orWhere('action', 'like', '%store%')
                ->orWhere('action', 'like', '%.add');
        })->count();

        $updated = (clone $query)->where(function (Builder $inner): void {
            $inner->where('action', 'like', '%update%')
                ->orWhere('action', 'like', '%edit%')
                ->orWhere('action', 'like', '%change%');
        })->count();

        $deleted = (clone $query)->where(function (Builder $inner): void {
            $inner->where('action', 'like', '%delete%')
                ->orWhere('action', 'like', '%destroy%')
                ->orWhere('action', 'like', '%remove%');
        })->count();

        return [
            'total' => (clone $query)->count(),
            'today' => (clone $query)->where('created_at', '>=', now()->startOfDay())->count(),
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'security' => $security,
        ];
    }

    /** @return array<string, mixed> */
    public function detail(AuditLog $log): array
    {
        $log->loadMissing('actor:id,name');

        $before = AuditRedactor::redact(is_array($log->before) ? $log->before : []);
        $after = AuditRedactor::redact(is_array($log->after) ? $log->after : []);
        $context = AuditRedactor::redact(is_array($log->context) ? $log->context : []);

        // Network metadata is personal data; expose it only to administrators
        // who already hold the stronger audit export capability.
        if (! Gate::allows('export', AuditLog::class)) {
            $context['ip'] = '[RESTRICTED]';
            $context['user_agent'] = '[RESTRICTED]';
        }

        $beforeFields = is_array($before) ? $before : [];
        $afterFields = is_array($after) ? $after : [];
        $changed = [];

        foreach (array_unique([...array_keys($beforeFields), ...array_keys($afterFields)]) as $field) {
            $oldValue = $beforeFields[$field] ?? null;
            $newValue = $afterFields[$field] ?? null;

            if (CsvSafeWriter::json($oldValue) !== CsvSafeWriter::json($newValue)) {
                $changed[] = [
                    'field' => (string) $field,
                    'before' => $oldValue,
                    'after' => $newValue,
                ];
            }
        }

        return [
            'id' => $log->id,
            'created_at' => $log->created_at?->toISOString(),
            'actor' => $log->actor?->name ?? 'system',
            'actor_id' => $log->actor_id,
            'action' => $log->action,
            'event_label' => $this->eventLabel((string) $log->action),
            'event_class' => $this->classify((string) $log->action),
            'severity' => $this->severity((string) $log->action),
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'request_id' => $log->request_id,
            'description' => $this->description($context),
            'before' => $before,
            'after' => $after,
            'context' => $context,
            'changed_fields' => $changed,
            'archived_at' => $log->archived_at?->toISOString(),
            'archive_id' => $log->archive_id,
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @param  callable(array<string, mixed>): void  $callback
     */
    public function exportRows(array $filters, callable $callback, int $batchSize = 500): int
    {
        $exported = 0;

        $this->baseQuery($filters)
            ->with('actor:id,name')
            ->select([
                'id', 'actor_id', 'action', 'subject_type', 'subject_id',
                'request_id', 'created_at', 'before', 'after', 'context',
            ])
            ->orderBy('id')
            ->chunkById($batchSize, function ($logs) use ($callback, &$exported): void {
                foreach ($logs as $log) {
                    $callback($this->presentExportRow($log));
                    $exported++;
                }
            });

        return $exported;
    }

    /** @return list<array{id: int, name: string}> */
    public function actors(): array
    {
        return User::query()
            ->whereIn('id', AuditLog::query()->select('actor_id')->whereNotNull('actor_id'))
            ->orderBy('name')
            ->limit(250)
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
            ->all();
    }

    /** @return list<string> */
    public function actions(): array
    {
        return AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->limit(250)
            ->pluck('action')
            ->all();
    }

    /** @return list<string> */
    public function subjects(): array
    {
        return AuditLog::query()
            ->distinct()
            ->orderBy('subject_type')
            ->limit(250)
            ->pluck('subject_type')
            ->all();
    }

    /** @param array<string, string> $filters */
    private function baseQuery(array $filters): Builder
    {
        $query = AuditLog::query();
        $search = trim($filters['search'] ?? '');
        $action = trim($filters['action'] ?? '');
        $subject = trim($filters['subject'] ?? '');
        $actor = trim($filters['actor'] ?? '');
        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';

        if ($search !== '') {
            $term = addcslashes($search, '\\%_');
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('action', 'like', "%{$term}%")
                    ->orWhere('subject_type', 'like', "%{$term}%")
                    ->orWhere('subject_id', 'like', "%{$term}%")
                    ->orWhere('request_id', 'like', "%{$term}%")
                    ->orWhereHas('actor', fn (Builder $actorQuery): Builder => $actorQuery
                        ->where('name', 'like', "%{$term}%"));
            });
        }

        if ($action !== '') {
            $query->where('action', 'like', '%'.addcslashes($action, '\\%_').'%');
        }

        if ($subject !== '') {
            $query->where('subject_type', 'like', '%'.addcslashes($subject, '\\%_').'%');
        }

        if (ctype_digit($actor)) {
            $query->where('actor_id', (int) $actor);
        }

        if ($from !== '') {
            $query->where('created_at', '>=', Carbon::parse($from.' 00:00:00'));
        }

        if ($to !== '') {
            $query->where('created_at', '<', Carbon::parse($to.' 00:00:00')->addDay());
        }

        $eventClass = $filters['event_class'] ?? '';
        if ($eventClass !== '' && isset(self::CLASS_PATTERNS[$eventClass])) {
            $query->where(function (Builder $inner) use ($eventClass): void {
                $this->applyPatternFilter($inner, self::CLASS_PATTERNS[$eventClass]);
            });
        }

        $severity = $filters['severity'] ?? '';
        if ($severity === 'critical' || $severity === 'warning') {
            $query->where(function (Builder $inner) use ($severity): void {
                $this->applyPatternFilter($inner, self::SEVERITY_PATTERNS[$severity]);
            });
        } elseif ($severity === 'info') {
            $query->where(function (Builder $inner): void {
                foreach ([...self::SEVERITY_PATTERNS['critical'], ...self::SEVERITY_PATTERNS['warning']] as $pattern) {
                    $inner->where('action', 'not like', $pattern);
                }
            });
        }

        return $query;
    }

    /** @param list<string> $patterns */
    private function applyPatternFilter(Builder $query, array $patterns): void
    {
        foreach ($patterns as $index => $pattern) {
            if ($index === 0) {
                $query->where('action', 'like', $pattern);

                continue;
            }

            $query->orWhere('action', 'like', $pattern);
        }
    }

    /** @return array<string, mixed> */
    private function presentSummary(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'created_at' => $log->created_at?->toISOString(),
            'actor' => $log->actor?->name ?? 'system',
            'actor_id' => $log->actor_id,
            'action' => $log->action,
            'event_label' => $this->eventLabel((string) $log->action),
            'event_class' => $this->classify((string) $log->action),
            'severity' => $this->severity((string) $log->action),
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'request_id' => $log->request_id,
            'is_archived' => $log->archive_id !== null,
        ];
    }

    /** @return array<string, mixed> */
    private function presentExportRow(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'created_at' => $log->created_at?->toISOString(),
            'actor' => $log->actor?->name ?? 'system',
            'actor_id' => $log->actor_id,
            'action' => $log->action,
            'event_class' => $this->classify((string) $log->action),
            'severity' => $this->severity((string) $log->action),
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'request_id' => $log->request_id,
            'before' => AuditRedactor::redact(is_array($log->before) ? $log->before : []),
            'after' => AuditRedactor::redact(is_array($log->after) ? $log->after : []),
            'context' => AuditRedactor::redact(is_array($log->context) ? $log->context : []),
        ];
    }

    private function eventLabel(string $action): string
    {
        return ucwords(str_replace(['.', '_'], ' ', $action));
    }

    private function classify(string $action): string
    {
        foreach (self::CLASS_PATTERNS as $class => $patterns) {
            foreach ($patterns as $pattern) {
                if ($this->matches($action, $pattern)) {
                    return $class;
                }
            }
        }

        return 'system';
    }

    private function severity(string $action): string
    {
        foreach (self::SEVERITY_PATTERNS as $severity => $patterns) {
            foreach ($patterns as $pattern) {
                if ($this->matches($action, $pattern)) {
                    return $severity;
                }
            }
        }

        return 'info';
    }

    private function matches(string $action, string $pattern): bool
    {
        $regex = '/^'.str_replace('%', '.*', preg_quote($pattern, '/')).'$/i';

        return preg_match($regex, $action) === 1;
    }

    /** @param array<string, mixed> $context */
    private function description(array $context): string
    {
        foreach (['description', 'reason', 'note', 'message'] as $key) {
            if (isset($context[$key]) && is_string($context[$key]) && $context[$key] !== '') {
                return $context[$key];
            }
        }

        return '';
    }

    private function validDate(string $value): string
    {
        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return '';
        }

        $date = Carbon::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }

    /** @param list<string> $choices */
    private function validChoice(string $value, array $choices): string
    {
        return in_array($value, $choices, true) ? $value : '';
    }
}
