<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Events\Phase5Event;
use App\Exceptions\Phase5GovernanceException;
use App\Jobs\Phase5\ExecuteAutomationJob;
use App\Models\Phase5\AutomationDefinition;
use App\Models\Phase5\AutomationExecution;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Phase5Support;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/** Structured, allow-listed automation. No code evaluation and no privileged actions. */
final class AutomationEngineService
{
    private const KEY_PATTERN = '/^[a-z0-9][a-z0-9_.-]{0,119}$/i';

    private const SAFE_TEXT_PATTERN = '/<\?|<\/|eval\s*\(|exec\s*\(|system\s*\(|passthru\s*\(|shell_exec\s*\(|proc_open\s*\(|`|\$\{|\$\(|&&|\|\||\r|\n|(^|\s)(select\s+.+\s+from|insert\s+into|update\s+.+\s+set|delete\s+from|drop\s+table|create\s+table)\b/i';

    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationCenterService $notifications,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function normalize(array $data, ?AutomationDefinition $definition = null): array
    {
        $allowed = ['category', 'trigger_event', 'conditions', 'actions', 'retry_limit', 'timeout_seconds'];
        $unknown = array_diff(array_keys($data), $allowed);

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unsupported automation configuration keys.');
        }

        $triggers = (array) config('phase5.automation.triggers', []);
        $trigger = (string) ($data['trigger_event'] ?? '');

        if (! in_array($trigger, $triggers, true)) {
            throw new InvalidArgumentException('Unsupported automation trigger.');
        }

        $category = (string) ($data['category'] ?? 'generic');
        if (! preg_match(self::KEY_PATTERN, $category)) {
            throw new InvalidArgumentException('Unsupported automation category.');
        }

        $conditions = $this->normalizeConditions($data['conditions'] ?? []);
        $actions = $this->normalizeActions($data['actions'] ?? []);
        $maxRetries = (int) config('phase5.automation.max_retries', 3);
        $retryLimit = (int) ($data['retry_limit'] ?? $maxRetries);
        $timeout = (int) ($data['timeout_seconds'] ?? config('phase5.automation.timeout_seconds', 20));

        if ($retryLimit < 0 || $retryLimit > $maxRetries || $timeout < 1 || $timeout > 60) {
            throw new InvalidArgumentException('Automation limits are outside the allowed range.');
        }

        return [
            'category' => $category,
            'trigger_event' => $trigger,
            'conditions' => $conditions,
            'actions' => $actions,
            'retry_limit' => $retryLimit,
            'timeout_seconds' => $timeout,
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): AutomationDefinition
    {
        $attributes = $this->normalize(Arr::except($data, ['key']));
        $key = (string) ($data['key'] ?? '');

        if (! preg_match(self::KEY_PATTERN, $key)) {
            throw new InvalidArgumentException('Automation key is not a safe identifier.');
        }

        $definition = DB::transaction(function () use ($actor, $attributes, $key): AutomationDefinition {
            $record = AutomationDefinition::query()->create([
                ...$attributes,
                'key' => $key,
                'owner_id' => $actor->id,
                'enabled' => false,
                'version' => 1,
            ]);

            $this->audit->record(
                'phase5.automation.created',
                'automation_definition',
                (string) $record->id,
                null,
                ['key' => $record->key, 'trigger_event' => $record->trigger_event],
            );

            return $record;
        });

        return $definition;
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, AutomationDefinition $definition, array $data): AutomationDefinition
    {
        $attributes = $this->normalize(Arr::except($data, ['key']), $definition);

        DB::transaction(function () use ($definition, $attributes): void {
            $definition->update([
                ...$attributes,
                'version' => $definition->version + 1,
            ]);

            $this->audit->record(
                'phase5.automation.updated',
                'automation_definition',
                (string) $definition->id,
                null,
                ['key' => $definition->key, 'version' => $definition->version],
            );
        });

        return $definition->refresh();
    }

    public function toggle(User $actor, AutomationDefinition $definition, bool $enabled): AutomationDefinition
    {
        DB::transaction(function () use ($definition, $enabled): void {
            $definition->update(['enabled' => $enabled]);

            $this->audit->record(
                'phase5.automation.toggled',
                'automation_definition',
                (string) $definition->id,
                ['enabled' => ! $enabled],
                ['enabled' => $enabled],
            );
        });

        return $definition->refresh();
    }

    /** @param array<string, mixed> $filters @return LengthAwarePaginator<int, mixed> */
    public function definitions(array $filters = []): LengthAwarePaginator
    {
        $query = AutomationDefinition::query();

        if (! empty($filters['trigger_event'])) {
            $query->where('trigger_event', (string) $filters['trigger_event']);
        }

        if (isset($filters['enabled']) && $filters['enabled'] !== '') {
            $query->where('enabled', (bool) $filters['enabled']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        return $query
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'definitionPage', max(1, (int) ($filters['definitionPage'] ?? 1)))
            ->withQueryString();
    }

    /** @param array<string, mixed> $filters @return LengthAwarePaginator<int, mixed> */
    public function executions(array $filters = []): LengthAwarePaginator
    {
        $query = AutomationExecution::query();

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['trigger_event'])) {
            $query->where('trigger_event', (string) $filters['trigger_event']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'executionPage', max(1, (int) ($filters['executionPage'] ?? 1)))
            ->withQueryString();
    }

    /** Create unique executions for enabled definitions matching a safe event. */
    public function matchAndCreateExecutions(Phase5Event $event): int
    {
        $definitions = AutomationDefinition::query()
            ->where('enabled', true)
            ->where('trigger_event', $event->name)
            ->orderBy('id')
            ->get();

        $created = [];

        foreach ($definitions as $definition) {
            $conditions = is_array($definition->conditions) ? $definition->conditions : [];

            if (! $this->matches($conditions, $event)) {
                continue;
            }

            $idempotencyKey = hash('sha256', $event->idempotencyKey().'|'.$definition->id.'|'.$definition->version);

            $execution = DB::transaction(fn (): ?AutomationExecution => $this->createExecution(
                $definition,
                $event,
                $idempotencyKey,
            ));

            if ($execution !== null) {
                $created[] = $execution->id;
            }
        }

        foreach ($created as $id) {
            ExecuteAutomationJob::dispatch($id)->afterCommit();
        }

        return count($created);
    }

    /** Bounded, deterministic execution with code-only failure diagnostics. */
    public function execute(AutomationExecution $execution): void
    {
        if ($execution->status === AutomationExecution::STATUS_SUCCEEDED) {
            return;
        }

        $definition = AutomationDefinition::withTrashed()->find($execution->definition_id);
        $attempts = (int) $execution->attempts + 1;

        if ($definition === null) {
            $this->fail($execution, new InvalidArgumentException('Missing automation definition'), $attempts, 0);

            return;
        }

        $this->transition($execution, AutomationExecution::STATUS_RUNNING, null, $attempts);

        try {
            $actions = is_array($definition->actions) ? $definition->actions : [];
            foreach ($actions as $index => $action) {
                $this->applyAction($execution, $definition, is_array($action) ? $action : [], (int) $index);
            }

            $this->transition($execution, AutomationExecution::STATUS_SUCCEEDED, null, $attempts);
            $definition->update(['last_executed_at' => now()]);

            $this->audit->record(
                'phase5.automation.execution_succeeded',
                'automation_execution',
                (string) $execution->id,
                null,
                ['definition_id' => $definition->id, 'attempts' => $attempts],
            );
        } catch (Throwable $exception) {
            $this->fail($execution, $exception, $attempts, (int) $definition->retry_limit);
        }
    }

    /** @param array<string, mixed> $filters */
    public function retry(User $actor, AutomationExecution $execution): void
    {
        $definition = $execution->definition_id !== null
            ? AutomationDefinition::withTrashed()->find($execution->definition_id)
            : null;
        $limit = $definition?->retry_limit ?? (int) config('phase5.automation.max_retries', 3);

        if (! in_array($execution->status, [
            AutomationExecution::STATUS_FAILED,
            AutomationExecution::STATUS_DEAD_LETTER,
        ], true) || (int) $execution->attempts >= $limit) {
            throw new Phase5GovernanceException('automation_retry', 'Retry only allows bounded failed executions.');
        }

        DB::transaction(function () use ($execution): void {
            $this->transition($execution, AutomationExecution::STATUS_PENDING, null, (int) $execution->attempts);
            $this->audit->record(
                'phase5.automation.retried',
                'automation_execution',
                (string) $execution->id,
                ['status' => AutomationExecution::STATUS_FAILED],
                ['status' => AutomationExecution::STATUS_PENDING],
            );
        });

        ExecuteAutomationJob::dispatch($execution->id)->afterCommit();
    }

    /** @param list<array<string, mixed>> $conditions */
    public function matches(array $conditions, Phase5Event $event): bool
    {
        foreach ($conditions as $condition) {
            $field = (string) ($condition['field'] ?? '');
            $operator = (string) ($condition['operator'] ?? '');
            $expected = $condition['value'] ?? null;
            $actual = $this->fieldValue($field, $event);

            if (! $this->compare($actual, $operator, $expected)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<array<string, mixed>> */
    private function normalizeConditions(mixed $conditions): array
    {
        if (! is_array($conditions) || count($conditions) > (int) config('phase5.automation.max_conditions', 10)) {
            throw new InvalidArgumentException('Invalid automation conditions.');
        }

        $fields = (array) config('phase5.automation.fields', []);
        $operators = (array) config('phase5.automation.operators', []);
        $normalized = [];

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                throw new InvalidArgumentException('Invalid automation condition.');
            }

            $unknown = array_diff(array_keys($condition), ['field', 'operator', 'value']);
            if ($unknown !== []) {
                throw new InvalidArgumentException('Unsupported condition keys.');
            }

            $field = (string) ($condition['field'] ?? '');
            $operator = (string) ($condition['operator'] ?? '');

            if (! isset($fields[$field]) || ! is_array($fields[$field]) || ! isset($operators[$operator])) {
                throw new InvalidArgumentException('Unsupported automation field or operator.');
            }

            $type = (string) ($fields[$field]['type'] ?? 'string');
            if (! in_array($type, (array) $operators[$operator], true)) {
                throw new InvalidArgumentException('Operator is not valid for this field.');
            }

            $value = $condition['value'] ?? null;
            if (! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Condition values must be scalars.');
            }

            if (is_string($value)) {
                $this->assertSafeText($value);
                if (isset($fields[$field]['values']) && ! in_array($value, (array) $fields[$field]['values'], true)) {
                    throw new InvalidArgumentException('Condition value is outside the allowed list.');
                }
            }

            $normalized[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ];
        }

        return $normalized;
    }

    /** @return list<array<string, mixed>> */
    private function normalizeActions(mixed $actions): array
    {
        if (! is_array($actions) || count($actions) > (int) config('phase5.automation.max_actions', 5)) {
            throw new InvalidArgumentException('Invalid automation actions.');
        }

        $definitions = (array) config('phase5.automation.actions', []);
        $normalized = [];

        foreach ($actions as $action) {
            if (! is_array($action)) {
                throw new InvalidArgumentException('Invalid automation action.');
            }

            $name = (string) ($action['action'] ?? '');
            if (! isset($definitions[$name]) || ! is_array($definitions[$name])) {
                throw new InvalidArgumentException('Unsupported automation action.');
            }

            $expectedKeys = $definitions[$name];
            $unknown = array_diff(array_keys($action), ['action', ...$expectedKeys]);
            if ($unknown !== []) {
                throw new InvalidArgumentException('Unsupported automation action keys.');
            }

            $values = ['action' => $name];
            foreach ($expectedKeys as $key) {
                $value = $action[$key] ?? null;
                if (! is_scalar($value) && $value !== null) {
                    throw new InvalidArgumentException('Action values must be scalars.');
                }
                if (is_string($value)) {
                    $this->assertSafeText($value);
                }
                $values[$key] = $value;
            }

            $this->validateActionValues($values);
            $normalized[] = $values;
        }

        return $normalized;
    }

    /** @param array<string, mixed> $values */
    private function validateActionValues(array $values): void
    {
        $name = (string) $values['action'];

        if ($name === 'create_notification') {
            foreach (['title_key', 'preview_key'] as $key) {
                if (! preg_match(self::KEY_PATTERN, (string) ($values[$key] ?? ''))) {
                    throw new InvalidArgumentException('Notification action keys must be safe translation keys.');
                }
            }
            if (! in_array($values['category'] ?? null, (array) config('phase5.notification.categories', []), true)
                || ! in_array($values['priority'] ?? null, (array) config('phase5.notification.priorities', []), true)) {
                throw new InvalidArgumentException('Unsupported notification action configuration.');
            }
        }

        if ($name === 'create_task') {
            if (mb_strlen((string) ($values['title'] ?? '')) > 255) {
                throw new InvalidArgumentException('Task title is too long.');
            }
            if (! in_array($values['priority'] ?? null, (array) config('phase5.task.priorities', []), true)) {
                throw new InvalidArgumentException('Unsupported task priority.');
            }
            $days = (int) ($values['due_days'] ?? 0);
            if ($days < 0 || $days > 90) {
                throw new InvalidArgumentException('Task due window is outside the allowed range.');
            }
        }

        if ($name === 'add_internal_comment' && mb_strlen((string) ($values['body'] ?? '')) > 1000) {
            throw new InvalidArgumentException('Internal comment is too long.');
        }

        if ($name === 'log_audit' && ! preg_match(self::KEY_PATTERN, (string) ($values['action'] ?? ''))) {
            throw new InvalidArgumentException('Audit action must be a safe key.');
        }
    }

    private function assertSafeText(string $value): void
    {
        if ($value === '' || mb_strlen($value) > 2000 || preg_match(self::SAFE_TEXT_PATTERN, $value)) {
            throw new InvalidArgumentException('Automation values must be structured data, not code or queries.');
        }
    }

    private function fieldValue(string $field, Phase5Event $event): mixed
    {
        $payload = $event->payload;
        $key = match ($field) {
            'task.status', 'workflow.status', 'document_review.status' => $payload['status'] ?? $payload['to_status'] ?? null,
            'task.priority' => $payload['priority'] ?? null,
            'task.due_at', 'workflow.due_at' => $payload['due_at'] ?? null,
            default => null,
        };

        return is_scalar($key) ? $key : null;
    }

    /** @param array<string, mixed> $condition */
    private function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            'equals' => (string) $actual === (string) $expected,
            'not_equals' => (string) $actual !== (string) $expected,
            'contains' => $actual !== null && str_contains((string) $actual, (string) $expected),
            'is_empty' => $actual === null || $actual === '' || $actual === 0 || $actual === false,
            'is_not_empty' => $actual !== null && $actual !== '' && $actual !== 0 && $actual !== false,
            'greater_than' => $this->numericCompare($actual, $expected, true),
            'less_than' => $this->numericCompare($actual, $expected, false),
            'date_before' => $this->dateCompare($actual, $expected, true),
            'date_after' => $this->dateCompare($actual, $expected, false),
            'in_list' => in_array((string) $actual, array_map('trim', explode(',', (string) $expected)), true),
            default => false,
        };
    }

    private function numericCompare(mixed $actual, mixed $expected, bool $greater): bool
    {
        $actualDate = $this->asTimestamp($actual);
        $expectedDate = $this->asTimestamp($expected);

        if ($actualDate !== null && $expectedDate !== null) {
            return $greater ? $actualDate > $expectedDate : $actualDate < $expectedDate;
        }

        if (! is_numeric($actual) || ! is_numeric($expected)) {
            return false;
        }

        return $greater
            ? (float) $actual > (float) $expected
            : (float) $actual < (float) $expected;
    }

    private function dateCompare(mixed $actual, mixed $expected, bool $before): bool
    {
        $actualDate = $this->asTimestamp($actual);
        $expectedDate = $this->asTimestamp($expected);

        if ($actualDate === null || $expectedDate === null) {
            return false;
        }

        return $before ? $actualDate < $expectedDate : $actualDate > $expectedDate;
    }

    private function asTimestamp(mixed $value): ?int
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function safeContext(Phase5Event $event): array
    {
        $userIds = $event->payload['user_ids'] ?? [];
        $userIds = is_array($userIds)
            ? array_values(array_filter($userIds, static fn (mixed $id): bool => is_int($id) || ctype_digit((string) $id)))
            : [];

        return [
            'event_name' => $event->name,
            'event_idempotency_key' => $event->idempotencyKey(),
            'user_ids' => array_slice(array_map('intval', $userIds), 0, 50),
            'resource_type' => $event->subjectType,
            'resource_id' => $event->subjectId,
            'actor_id' => $event->actorId,
            'history' => [],
        ];
    }

    private function createExecution(
        AutomationDefinition $definition,
        Phase5Event $event,
        string $idempotencyKey,
    ): ?AutomationExecution {
        if (AutomationExecution::query()->where('idempotency_key', $idempotencyKey)->exists()) {
            return null;
        }

        $execution = AutomationExecution::query()->create([
            'definition_id' => $definition->id,
            'definition_version' => $definition->version,
            'trigger_event' => $event->name,
            'subject_type' => $event->subjectType,
            'subject_id' => $event->subjectId,
            'idempotency_key' => $idempotencyKey,
            'status' => AutomationExecution::STATUS_QUEUED,
            'attempts' => 0,
            'last_error' => null,
            'context' => $this->safeContext($event),
        ]);

        $this->audit->record(
            'phase5.automation.execution_queued',
            'automation_execution',
            (string) $execution->id,
            null,
            ['definition_id' => $definition->id, 'trigger_event' => $event->name],
        );

        return $execution;
    }

    private function transition(
        AutomationExecution $execution,
        string $status,
        ?string $errorCode,
        int $attempts,
    ): void {
        $context = is_array($execution->context) ? $execution->context : [];
        $history = is_array($context['history'] ?? null) ? $context['history'] : [];
        $history[] = [
            'status' => $status,
            'at' => now()->toIso8601String(),
            'error_code' => $errorCode,
        ];
        $context['history'] = array_slice($history, -10);
        $execution->update([
            'status' => $status,
            'attempts' => $attempts,
            'last_error' => $errorCode,
            'context' => $context,
            'started_at' => $execution->started_at ?? ($status === AutomationExecution::STATUS_RUNNING ? now() : null),
            'finished_at' => in_array($status, [
                AutomationExecution::STATUS_SUCCEEDED,
                AutomationExecution::STATUS_FAILED,
                AutomationExecution::STATUS_DEAD_LETTER,
            ], true) ? now() : null,
        ]);
    }

    private function fail(AutomationExecution $execution, Throwable $exception, int $attempts, int $limit): void
    {
        $limit = $limit > 0 ? $limit : (int) config('phase5.automation.max_retries', 3);
        $deadLetter = $attempts >= min($limit, (int) config('phase5.automation.dead_letter_after_attempts', 3));
        $code = Phase5Support::errorCode($exception);

        $this->transition(
            $execution,
            $deadLetter ? AutomationExecution::STATUS_DEAD_LETTER : AutomationExecution::STATUS_FAILED,
            $code,
            $attempts,
        );

        $this->audit->record(
            'phase5.automation.execution_failed',
            'automation_execution',
            (string) $execution->id,
            null,
            ['error_code' => $code, 'attempts' => $attempts, 'dead_letter' => $deadLetter],
        );
    }

    /** @param array<string, mixed> $action */
    private function applyAction(AutomationExecution $execution, AutomationDefinition $definition, array $action, int $index): void
    {
        $context = is_array($execution->context) ? $execution->context : [];
        $type = $execution->subject_type;
        $id = $execution->subject_id !== null ? (int) $execution->subject_id : null;

        switch ($action['action'] ?? '') {
            case 'create_notification':
                $this->notifications->notifyUsers(
                    array_map('intval', $context['user_ids'] ?? []),
                    [
                        'category' => (string) $action['category'],
                        'priority' => (string) $action['priority'],
                        'title_key' => (string) $action['title_key'],
                        'preview_key' => (string) $action['preview_key'],
                        'resource_type' => $type,
                        'resource_id' => $id,
                    ],
                    hash('sha256', $execution->idempotency_key.':notification:'.$index.':'.($context['event_idempotency_key'] ?? '')),
                );

                break;
            case 'create_task':
                $code = 'AT-'.strtoupper(substr($execution->idempotency_key, 0, 16)).'-'.$index;
                $existing = DB::table('tasks')->where('code', $code)->first(['id']);
                if ($existing === null) {
                    DB::table('tasks')->insert([
                        'code' => $code,
                        'title' => Phase5Support::clean((string) $action['title'], 255),
                        'status' => 'to_do',
                        'priority' => (string) $action['priority'],
                        'due_at' => now()->addDays((int) $action['due_days']),
                        'subject_type' => $type,
                        'subject_id' => $id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                break;
            case 'add_internal_comment':
                if ($type === null || $id === null
                    || ! in_array($type, (array) config('phase5.collaboration.commentable_resources', []), true)) {
                    break;
                }

                DB::table('discussion_threads')->insertOrIgnore([
                    'resource_type' => $type,
                    'resource_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $threadId = DB::table('discussion_threads')
                    ->where('resource_type', $type)
                    ->where('resource_id', $id)
                    ->value('id');
                $body = Phase5Support::clean((string) $action['body'], 1000);
                $authorId = isset($context['actor_id']) ? (int) $context['actor_id'] : null;
                $duplicate = DB::table('discussion_comments')
                    ->where('thread_id', $threadId)
                    ->where('body', $body)
                    ->where('author_id', $authorId)
                    ->where('created_at', '>=', $execution->created_at)
                    ->exists();

                if (! $duplicate) {
                    DB::table('discussion_comments')->insert([
                        'thread_id' => $threadId,
                        'author_id' => $authorId,
                        'body' => $body,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                break;
            case 'log_audit':
                $this->audit->record(
                    'phase5.automation.'.Phase5Support::clean((string) $action['action'], 96),
                    'automation_execution',
                    (string) $execution->id,
                    null,
                    ['definition_id' => $definition->id],
                );

                break;
        }
    }
}
