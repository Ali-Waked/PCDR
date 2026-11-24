<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Events\Phase5Event;
use App\Models\DomainTransition;
use App\Models\Phase5\WorkflowDefinition;
use App\Models\Phase5\WorkflowInstance;
use App\Models\Phase5\WorkflowStep;
use App\Models\Phase5\WorkflowTransition;
use App\Models\Phase5\WorkflowVersion;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Phase5Support;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Workflow definitions, immutable version snapshots and instance lifecycles.
 *
 * Version configuration is a closed data format: only the step keys declared
 * in config/phase5.php are persisted, and no executable or approval token is
 * accepted. Every status change is authorized through Phase5Authorization and
 * written to both the workflow and shared domain transition ledgers.
 */
final class WorkflowService
{
    private const SAFE_ACTIONS = ['start', 'advance', 'return', 'suspend', 'resume', 'cancel', 'close', 'archive'];

    private const UNSAFE_TOKENS = [
        '<?php', '<?=', 'eval(', 'system(', 'exec(', 'shell_exec(', 'passthru(',
        'proc_open(', 'popen(', 'base64_decode(', 'file_get_contents(',
        'include ', 'require ', '$_get', '$_post', '$this->', 'new ',
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly Phase5Authorization $authorization,
    ) {}

    /** @param array<string, mixed> $data */
    public function createDefinition(array $data, User $actor): WorkflowDefinition
    {
        $this->assertNoApproval($data, 'status', 'key', 'category', 'trigger_event');

        $definition = DB::transaction(function () use ($data, $actor): WorkflowDefinition {
            $definition = WorkflowDefinition::query()->create([
                'key' => Phase5Support::clean((string) $data['key'], 96),
                'category' => (string) $data['category'],
                'status' => (string) ($data['status'] ?? 'draft'),
                'trigger_event' => (string) ($data['trigger_event'] ?? 'manual'),
                'owner_id' => isset($data['owner_id']) ? (int) $data['owner_id'] : $actor->id,
                'created_by' => $actor->id,
            ]);

            if (isset($data['translations']) && is_array($data['translations'])) {
                $this->syncTranslations('workflow_definition', (int) $definition->getKey(), $data['translations']);
            }

            return $definition;
        });

        $this->audit->record(
            'phase5.workflow.definition_created',
            'workflow_definition',
            (string) $definition->getKey(),
            null,
            $this->definitionAuditSnapshot($definition),
        );

        return $definition;
    }

    /** @param array<string, mixed> $data */
    public function updateDefinition(WorkflowDefinition $definition, array $data, User $actor): WorkflowDefinition
    {
        $this->assertNoApproval($data, 'status', 'key', 'category', 'trigger_event');

        $before = $this->definitionAuditSnapshot($definition);

        DB::transaction(function () use ($definition, $data): void {
            $attributes = [];

            foreach (['key', 'category', 'status', 'trigger_event'] as $field) {
                if (array_key_exists($field, $data)) {
                    $attributes[$field] = $field === 'key'
                        ? Phase5Support::clean((string) $data[$field], 96)
                        : (string) $data[$field];
                }
            }

            if (array_key_exists('owner_id', $data)) {
                $attributes['owner_id'] = (int) $data['owner_id'];
            }

            if ($attributes !== []) {
                $definition->fill($attributes)->save();
            }

            if (isset($data['translations']) && is_array($data['translations'])) {
                $this->syncTranslations('workflow_definition', (int) $definition->getKey(), $data['translations']);
            }
        });

        $this->audit->record(
            'phase5.workflow.definition_updated',
            'workflow_definition',
            (string) $definition->getKey(),
            $before,
            $this->definitionAuditSnapshot($definition->refresh()),
        );

        return $definition;
    }

    /**
     * Append a new immutable snapshot. Historical versions are never changed.
     *
     * @param  array<string, mixed>  $data
     */
    public function createVersion(WorkflowDefinition $definition, array $data, User $actor): WorkflowVersion
    {
        $this->assertNoApproval($data, 'status', 'key');

        $steps = $this->sanitizeSteps((array) ($data['steps'] ?? []));

        $version = DB::transaction(function () use ($definition, $steps, $actor): WorkflowVersion {
            $next = (int) $definition->versions()->max('version') + 1;
            $config = ['steps' => $steps];
            $maxBytes = (int) config('phase5.workflow.max_version_config_bytes', 65536);

            if (strlen(Phase5Support::encode($config)) > $maxBytes) {
                throw new InvalidArgumentException(__('phase5.governance_disabled'));
            }

            return WorkflowVersion::query()->create([
                'definition_id' => $definition->getKey(),
                'version' => $next,
                'config' => $config,
                'published_at' => now(),
                'created_by' => $actor->id,
            ]);
        });

        $this->audit->record(
            'phase5.workflow.version_created',
            'workflow_version',
            (string) $version->getKey(),
            null,
            [
                'definition_id' => $definition->getKey(),
                'version' => $version->version,
                'step_count' => count($steps),
            ],
        );

        return $version;
    }

    /**
     * Start an instance bound to exactly one version of its definition.
     *
     * @param  array<string, mixed>  $data
     */
    public function startInstance(WorkflowDefinition $definition, array $data, User $actor): WorkflowInstance
    {
        $this->assertNoApproval($data, 'status', 'subject_type');

        $version = WorkflowVersion::query()
            ->whereKey((int) ($data['version_id'] ?? 0))
            ->where('definition_id', $definition->getKey())
            ->firstOrFail();

        $subjectType = isset($data['subject_type']) ? (string) $data['subject_type'] : null;
        $subjectId = isset($data['subject_id']) ? (int) $data['subject_id'] : null;

        if ($subjectType !== null && $subjectId !== null) {
            $this->authorization->assertCanViewResource($actor, $subjectType, $subjectId);
        }

        $steps = (array) ($version->config['steps'] ?? []);
        $initiatorId = isset($data['initiator_id']) ? (int) $data['initiator_id'] : $actor->id;

        $instance = DB::transaction(function () use (
            $definition,
            $version,
            $steps,
            $subjectType,
            $subjectId,
            $initiatorId,
            $data,
            $actor,
        ): WorkflowInstance {
            $instance = WorkflowInstance::query()->create([
                'reference' => 'WF-'.strtoupper((string) Str::ulid()),
                'definition_id' => $definition->getKey(),
                'version_id' => $version->getKey(),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'status' => 'active',
                'current_step_order' => 0,
                'initiator_id' => $initiatorId,
                'due_at' => isset($data['due_at']) ? (string) $data['due_at'] : null,
                'metadata' => [],
            ]);

            foreach ($steps as $step) {
                $assigneeId = null;
                if (($step['assignment_type'] ?? 'unassigned') === 'user' && isset($step['assignee_id'])) {
                    $candidate = (int) $step['assignee_id'];
                    if (! User::query()->whereKey($candidate)->exists()) {
                        throw new InvalidArgumentException(__('phase5.no_permission'));
                    }
                    $assigneeId = $candidate;
                }

                WorkflowStep::query()->create([
                    'instance_id' => $instance->getKey(),
                    'step_key' => (string) $step['key'],
                    'label' => Phase5Support::localized([
                        'ar' => isset($step['label_ar']) ? (string) $step['label_ar'] : null,
                        'en' => isset($step['label_en']) ? (string) $step['label_en'] : null,
                    ]) ?: Phase5Support::clean((string) ($step['label'] ?? $step['key']), 255),
                    'ordering' => (int) ($step['order'] ?? 0),
                    'assignment_type' => (string) ($step['assignment_type'] ?? 'unassigned'),
                    'assignee_id' => $assigneeId,
                    'role_key' => isset($step['role_key']) ? (string) $step['role_key'] : null,
                    'status' => 'pending',
                    'due_at' => isset($step['due_at']) ? (string) $step['due_at'] : null,
                ]);
            }

            $this->appendTransition(
                $instance,
                null,
                'active',
                'start',
                $actor,
                null,
                ['version_id' => $version->getKey(), 'definition_key' => $definition->key],
            );

            return $instance;
        });

        $this->audit->record(
            'phase5.workflow.created',
            'workflow_instance',
            (string) $instance->getKey(),
            null,
            [
                'reference' => $instance->reference,
                'definition_key' => $definition->key,
                'version' => $version->version,
            ],
        );

        $payload = [
            'workflow_instance_id' => $instance->getKey(),
            'definition_key' => $definition->key,
            'version' => $version->version,
            'status' => 'active',
        ];
        $recipients = $this->authorizedRecipients(
            array_merge([$initiatorId], $instance->steps()->pluck('assignee_id')->all()),
            'workflow_instance',
            (int) $instance->getKey(),
        );
        if ($recipients !== []) {
            $payload['user_ids'] = $recipients;
        }
        $this->dispatch('workflow.created', $payload, (int) $instance->getKey(), $actor->id);

        foreach ($instance->steps()->whereNotNull('assignee_id')->get() as $step) {
            $stepRecipients = $this->authorizedRecipients(
                [(int) $step->assignee_id],
                'workflow_instance',
                (int) $instance->getKey(),
            );
            if ($stepRecipients === []) {
                continue;
            }

            $this->dispatch('workflow.step_assigned', [
                'workflow_instance_id' => $instance->getKey(),
                'step_id' => $step->getKey(),
                'step_key' => $step->step_key,
                'user_ids' => $stepRecipients,
            ], (int) $instance->getKey(), $actor->id);
        }

        return $instance->load('steps');
    }

    /** @param array<string, mixed> $data */
    public function transitionInstance(WorkflowInstance $instance, array $data, User $actor): WorkflowInstance
    {
        $this->assertNoApproval($data, 'to_status', 'action');

        $from = (string) $instance->status;
        $to = (string) $data['to_status'];
        $action = $this->safeAction($data['action'] ?? 'advance');

        $this->authorization->assertTransition($actor, 'workflow', $from, $to);

        $state = ['from' => $from];

        DB::transaction(function () use ($instance, $to, $action, $data, $actor, $state): void {
            /** @var WorkflowInstance $locked */
            $locked = WorkflowInstance::query()->whereKey($instance->getKey())->lockForUpdate()->firstOrFail();
            $actualFrom = (string) $locked->status;

            if ($actualFrom !== $state['from']) {
                $this->authorization->assertTransition($actor, 'workflow', $actualFrom, $to);
            }

            $state['from'] = $actualFrom;
            $locked->forceFill(['status' => $to])->save();

            $this->appendTransition(
                $locked,
                $actualFrom,
                $to,
                $action,
                $actor,
                isset($data['reason']) ? (string) $data['reason'] : null,
                ['version_id' => $locked->version_id],
            );
        });

        $this->audit->record(
            'phase5.workflow.status_changed',
            'workflow_instance',
            (string) $instance->getKey(),
            ['status' => $state['from']],
            ['status' => $to],
        );

        $payload = [
            'workflow_instance_id' => $instance->getKey(),
            'version' => (int) $instance->version_id,
            'from_status' => $state['from'],
            'to_status' => $to,
        ];
        $recipients = $this->authorizedRecipients(
            array_merge(
                [$instance->initiator_id],
                $instance->steps()->whereNotNull('assignee_id')->pluck('assignee_id')->all(),
            ),
            'workflow_instance',
            (int) $instance->getKey(),
        );
        if ($recipients !== []) {
            $payload['user_ids'] = $recipients;
        }
        $this->dispatch('workflow.status_changed', $payload, (int) $instance->getKey(), $actor->id);

        return $instance->refresh();
    }

    /** @param array<string, mixed> $data */
    public function transitionStep(
        WorkflowInstance $instance,
        WorkflowStep $step,
        array $data,
        User $actor,
    ): WorkflowStep {
        if ((int) $step->instance_id !== (int) $instance->getKey()) {
            abort(404, __('phase5.not_found'));
        }

        $this->assertNoApproval($data, 'to_status', 'action');

        $from = (string) $step->status;
        $to = (string) $data['to_status'];
        $action = $this->safeAction($data['action'] ?? 'advance');

        // The shared authorization seam owns permission checks; step status
        // maps live under workflow.step_transitions rather than workflow.transitions.
        $this->authorization->assertAllows($actor, 'workflows.transition');

        if (! $this->stepTransitionAllowed($from, $to)) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $state = ['from' => $from];

        DB::transaction(function () use ($step, $to, $action, $state, $actor): void {
            /** @var WorkflowStep $locked */
            $locked = WorkflowStep::query()->whereKey($step->getKey())->lockForUpdate()->firstOrFail();
            $actualFrom = (string) $locked->status;

            if (! $this->stepTransitionAllowed($actualFrom, $to)) {
                throw new InvalidArgumentException(__('phase5.invalid_transition'));
            }

            $state['from'] = $actualFrom;
            $attributes = ['status' => $to];

            if ($to === 'in_progress' && $locked->started_at === null) {
                $attributes['started_at'] = now();
            }
            if (in_array($to, ['completed', 'rejected', 'skipped', 'cancelled'], true)) {
                $attributes['completed_at'] = now();
            }

            $locked->forceFill($attributes)->save();

            $this->appendTransition(
                $locked->instance,
                $actualFrom,
                $to,
                'step_'.$action,
                $actor,
                null,
                ['step_id' => $locked->getKey(), 'step_key' => $locked->step_key],
            );

            DomainTransition::query()->create([
                'resource' => 'workflow_steps',
                'owner_id' => $locked->getKey(),
                'status_field' => 'step_status',
                'from_status' => $actualFrom,
                'to_status' => $to,
                'actor_id' => $actor->id,
                'created_at' => now(),
            ]);
        });

        $this->audit->record(
            'phase5.workflow.step_status_changed',
            'workflow_step',
            (string) $step->getKey(),
            ['status' => $state['from']],
            ['status' => $to],
        );

        $payload = [
            'workflow_instance_id' => $instance->getKey(),
            'step_id' => $step->getKey(),
            'from_status' => $state['from'],
            'to_status' => $to,
        ];
        $recipients = $this->authorizedRecipients(
            [$step->assignee_id, $instance->initiator_id],
            'workflow_instance',
            (int) $instance->getKey(),
        );
        if ($recipients !== []) {
            $payload['user_ids'] = $recipients;
        }
        $this->dispatch('workflow.status_changed', $payload, (int) $instance->getKey(), $actor->id);

        return $step->refresh();
    }

    /**
     * Validate and normalize a step list against the closed configuration
     * format. Unknown keys and code-like values are rejected before storage.
     *
     * @param  array<mixed>  $steps
     * @return list<array<string, mixed>>
     */
    public function sanitizeSteps(array $steps): array
    {
        if ($steps === [] || ! array_is_list($steps)) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $maxSteps = (int) config('phase5.workflow.max_steps', 25);
        if (count($steps) > $maxSteps) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $allowedKeys = array_values((array) config('phase5.workflow.allowed_step_keys', []));
        $allowedAssignments = array_values((array) config('phase5.workflow.assignment_types', []));
        $conditionKeys = array_values((array) config('phase5.workflow.allowed_condition_keys', []));

        $clean = [];
        foreach ($steps as $step) {
            if (! is_array($step)) {
                throw new InvalidArgumentException(__('phase5.invalid_transition'));
            }

            $unknown = array_diff(array_keys($step), $allowedKeys);
            if ($unknown !== []) {
                throw new InvalidArgumentException(__('phase5.governance_disabled'));
            }

            $key = isset($step['key']) ? Phase5Support::clean((string) $step['key'], 96) : '';
            if ($key === '' || preg_match('/^[a-z0-9][a-z0-9_.-]*$/', $key) !== 1) {
                throw new InvalidArgumentException(__('phase5.invalid_transition'));
            }
            if (isset($clean[$key])) {
                throw new InvalidArgumentException(__('phase5.invalid_transition'));
            }

            $label = isset($step['label']) ? Phase5Support::clean((string) $step['label'], 255) : $key;
            if ($label === '') {
                throw new InvalidArgumentException(__('phase5.invalid_transition'));
            }

            $assignmentType = (string) ($step['assignment_type'] ?? 'unassigned');
            if (! in_array($assignmentType, $allowedAssignments, true)) {
                throw new InvalidArgumentException(__('phase5.invalid_transition'));
            }

            $sanitized = [
                'key' => $key,
                'label' => $label,
                'order' => isset($step['order']) ? max(0, (int) $step['order']) : count($clean) + 1,
                'assignment_type' => $assignmentType,
            ];

            foreach (['label_ar', 'label_en', 'role_key', 'due_at', 'notification_event'] as $field) {
                if (! array_key_exists($field, $step)) {
                    continue;
                }
                if (! is_scalar($step[$field])) {
                    throw new InvalidArgumentException(__('phase5.governance_disabled'));
                }
                $sanitized[$field] = Phase5Support::clean((string) $step[$field], 255);
            }

            if (array_key_exists('assignee_id', $step) && $step['assignee_id'] !== null) {
                $sanitized['assignee_id'] = (int) $step['assignee_id'];
            }
            if (array_key_exists('due_days', $step) && $step['due_days'] !== null) {
                $sanitized['due_days'] = max(0, min(365, (int) $step['due_days']));
            }
            foreach (['allow_rejection', 'allow_return', 'requires_comment'] as $field) {
                if (array_key_exists($field, $step)) {
                    $sanitized[$field] = (bool) $step[$field];
                }
            }

            foreach (['completion_condition', 'metadata'] as $field) {
                if (! array_key_exists($field, $step) || $step[$field] === null) {
                    continue;
                }
                if (! is_array($step[$field])) {
                    throw new InvalidArgumentException(__('phase5.governance_disabled'));
                }
                foreach ($step[$field] as $nestedKey => $value) {
                    if (! in_array($nestedKey, $conditionKeys, true) || ! is_scalar($value)) {
                        throw new InvalidArgumentException(__('phase5.governance_disabled'));
                    }
                    $sanitized[$field][$nestedKey] = Phase5Support::clean((string) $value, 255);
                }
            }

            foreach ($sanitized as $value) {
                $this->assertSafeValue($value);
            }

            $clean[$key] = $sanitized;
        }

        $steps = array_values($clean);
        usort($steps, static function (array $left, array $right): int {
            return [$left['order'], $left['key']] <=> [$right['order'], $right['key']];
        });

        return $steps;
    }

    public function stepTransitionAllowed(string $from, string $to): bool
    {
        $transitions = (array) config('phase5.workflow.step_transitions', []);

        return in_array($to, $transitions[$from] ?? [], true);
    }

    /** @param array<string, mixed> $filters */
    public function paginateDefinitions(array $filters): LengthAwarePaginator
    {
        $query = WorkflowDefinition::query();

        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['category'])) {
            $query->where('category', (string) $filters['category']);
        }

        $sort = in_array($filters['sort'] ?? null, ['created_at', 'id', 'key', 'status'], true)
            ? (string) $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', $direction)
            ->paginate($this->perPage($filters), ['*'], 'definitionPage', max(1, (int) ($filters['definitionPage'] ?? 1)))
            ->withQueryString();
    }

    /** @param array<string, mixed> $filters */
    public function paginateInstances(array $filters, User $actor): LengthAwarePaginator
    {
        $query = WorkflowInstance::query()->with('definition:id,key,category,status');

        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['definition_id'])) {
            $query->where('definition_id', (int) $filters['definition_id']);
        }
        if (isset($filters['subject_type'], $filters['subject_id'])) {
            $subjectType = (string) $filters['subject_type'];
            $subjectId = (int) $filters['subject_id'];
            $this->authorization->assertCanViewResource($actor, $subjectType, $subjectId);
            $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
        }

        $sort = in_array($filters['sort'] ?? null, ['created_at', 'id', 'status', 'reference', 'due_at'], true)
            ? (string) $filters['sort']
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', $direction)
            ->paginate($this->perPage($filters), ['*'], 'instancePage', max(1, (int) ($filters['instancePage'] ?? 1)))
            ->withQueryString();
    }

    /** @param iterable<WorkflowDefinition> $definitions @return list<array<string, mixed>> */
    public function definitionPayloads(iterable $definitions): array
    {
        $items = is_array($definitions) ? $definitions : iterator_to_array($definitions);
        $names = $this->localizedNames('workflow_definition', array_map(
            static fn (WorkflowDefinition $definition): int => (int) $definition->getKey(),
            $items,
        ));
        $latest = WorkflowVersion::query()
            ->whereIn('definition_id', array_map(
                static fn (WorkflowDefinition $definition): int => (int) $definition->getKey(),
                $items,
            ))
            ->selectRaw('definition_id, MAX(version) as latest_version')
            ->groupBy('definition_id')
            ->pluck('latest_version', 'definition_id')
            ->all();

        return array_map(
            fn (WorkflowDefinition $definition): array => $this->definitionPayload(
                $definition,
                $names[(int) $definition->getKey()] ?? [],
                (int) ($latest[(int) $definition->getKey()] ?? 0),
            ),
            $items,
        );
    }

    /** @param iterable<WorkflowInstance> $instances @return list<array<string, mixed>> */
    public function instancePayloads(iterable $instances): array
    {
        $items = is_array($instances) ? $instances : iterator_to_array($instances);
        $definitionIds = array_values(array_unique(array_map(
            static fn (WorkflowInstance $instance): int => (int) $instance->definition_id,
            $items,
        )));
        $definitions = $definitionIds === [] ? [] : WorkflowDefinition::query()
            ->whereIn('id', $definitionIds)
            ->get(['id', 'key'])
            ->keyBy('id');
        $names = $this->localizedNames('workflow_definition', $definitionIds);

        return array_map(
            fn (WorkflowInstance $instance): array => $this->instancePayload(
                $instance,
                $definitions[(int) $instance->definition_id] ?? null,
                $names[(int) $instance->definition_id] ?? [],
            ),
            $items,
        );
    }

    /** @return array<string, mixed> */
    public function instancePayload(
        WorkflowInstance $instance,
        ?WorkflowDefinition $definition = null,
        array $names = [],
    ): array {
        $definition ??= $instance->definition;
        $version = $instance->version_id;
        if ($names === [] && $definition !== null) {
            $names = $this->localizedNames('workflow_definition', [(int) $definition->getKey()])[(int) $definition->getKey()] ?? [];
        }

        return [
            'id' => $instance->getKey(),
            'reference' => $instance->reference,
            'definition_id' => $instance->definition_id,
            'definition_key' => $definition?->key,
            'version_id' => $version,
            'name' => $names['name'] ?? ($definition?->key ?? ''),
            'description' => $names['description'] ?? null,
            'status' => $instance->status,
            'current_step_order' => $instance->current_step_order,
            'subject_type' => $instance->subject_type,
            'subject_id' => $instance->subject_id,
            'due_at' => $instance->due_at?->toISOString(),
            'created_at' => $instance->created_at?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    public function stepPayload(WorkflowStep $step): array
    {
        return [
            'id' => $step->getKey(),
            'step_key' => $step->step_key,
            'label' => $step->label,
            'ordering' => $step->ordering,
            'assignment_type' => $step->assignment_type,
            'assignee_id' => $step->assignee_id,
            'role_key' => $step->role_key,
            'status' => $step->status,
            'due_at' => $step->due_at?->toISOString(),
            'started_at' => $step->started_at?->toISOString(),
            'completed_at' => $step->completed_at?->toISOString(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function instanceHistory(WorkflowInstance $instance): array
    {
        return WorkflowTransition::query()
            ->where('instance_id', $instance->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->map(static fn (WorkflowTransition $transition): array => [
                'id' => $transition->getKey(),
                'from_status' => $transition->from_status,
                'to_status' => $transition->to_status,
                'action' => $transition->action,
                'actor_id' => $transition->actor_id,
                'created_at' => $transition->created_at?->toISOString(),
            ])->all();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function appendTransition(
        WorkflowInstance $instance,
        ?string $from,
        string $to,
        string $action,
        User $actor,
        ?string $reason,
        array $context,
    ): void {
        WorkflowTransition::query()->create([
            'instance_id' => $instance->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'actor_id' => $actor->id,
            'reason' => $reason === null ? null : Phase5Support::clean($reason, 2000),
            'context' => $context,
            'created_at' => now(),
        ]);

        DomainTransition::query()->create([
            'resource' => 'workflow_instances',
            'owner_id' => $instance->getKey(),
            'status_field' => 'status',
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor->id,
            'reason' => $reason === null ? null : Phase5Support::clean($reason, 2000),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<int|string|null>  $candidates
     * @return list<int>
     */
    private function authorizedRecipients(array $candidates, string $type, int $id): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($value): int => (int) $value, $candidates),
            static fn (int $value): bool => $value > 0,
        )));

        if ($ids === []) {
            return [];
        }

        $recipients = [];
        foreach (User::query()->whereIn('id', $ids)->get(['id']) as $user) {
            if ($this->authorization->canViewResource($user, $type, $id)) {
                $recipients[] = (int) $user->id;
            }
        }

        return $recipients;
    }

    /** @param array<string, mixed> $data */
    private function assertNoApproval(array $data, string ...$fields): void
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && $this->isApprovalToken($data[$field])) {
                throw new InvalidArgumentException(__('phase5.governance_disabled'));
            }
        }
    }

    private function isApprovalToken(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $normalized = mb_strtolower($value);

        foreach ((array) config('phase5.governance.approval_statuses', []) as $status) {
            if ($normalized === mb_strtolower((string) $status)) {
                return true;
            }
        }

        return str_contains($normalized, 'approv');
    }

    private function assertSafeValue(mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->assertSafeValue($item);
            }

            return;
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException(__('phase5.governance_disabled'));
        }

        $this->assertNoApproval(['value' => (string) $value], 'value');

        foreach (self::UNSAFE_TOKENS as $token) {
            if (str_contains(mb_strtolower((string) $value), $token)) {
                throw new InvalidArgumentException(__('phase5.governance_disabled'));
            }
        }
    }

    private function safeAction(mixed $action): string
    {
        $value = is_string($action) ? $action : 'advance';
        if (! in_array($value, self::SAFE_ACTIONS, true)) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        return $value;
    }

    /** @param array<string, mixed> $filters */
    private function perPage(array $filters): int
    {
        return max(1, min(50, (int) ($filters['per_page'] ?? 15)));
    }

    /** @param array<string, mixed> $translations */
    private function syncTranslations(string $resource, int $ownerId, array $translations): void
    {
        foreach (['ar', 'en'] as $locale) {
            $values = $translations[$locale] ?? null;
            if (! is_array($values)) {
                continue;
            }

            DB::table('phase5_translations')->updateOrInsert(
                ['resource' => $resource, 'owner_id' => $ownerId, 'locale' => $locale],
                [
                    'name' => isset($values['name']) ? Phase5Support::clean((string) $values['name'], 255) : null,
                    'description' => isset($values['description'])
                        ? Phase5Support::clean((string) $values['description'], 2000)
                        : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /** @param list<int> $ownerIds @return array<int, array{name: string, description: string|null}> */
    private function localizedNames(string $resource, array $ownerIds): array
    {
        $ownerIds = array_values(array_unique($ownerIds));
        if ($ownerIds === []) {
            return [];
        }

        $rows = DB::table('phase5_translations')
            ->where('resource', $resource)
            ->whereIn('owner_id', $ownerIds)
            ->get(['owner_id', 'locale', 'name', 'description']);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->owner_id][$row->locale] = $row;
        }

        $result = [];
        foreach ($ownerIds as $ownerId) {
            $values = $grouped[$ownerId] ?? [];
            $preferred = $values[app()->getLocale()] ?? $values['en'] ?? $values['ar'] ?? null;
            $result[$ownerId] = [
                'name' => $preferred?->name ?? '',
                'description' => $preferred?->description ?? null,
            ];
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function definitionPayload(
        WorkflowDefinition $definition,
        array $names = [],
        int $latestVersion = 0,
    ): array {
        return [
            'id' => $definition->getKey(),
            'key' => $definition->key,
            'name' => $names['name'] ?? $definition->key,
            'description' => $names['description'] ?? null,
            'category' => $definition->category,
            'status' => $definition->status,
            'trigger_event' => $definition->trigger_event,
            'latest_version' => $latestVersion,
            'created_at' => $definition->created_at?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    private function definitionAuditSnapshot(WorkflowDefinition $definition): array
    {
        return [
            'id' => $definition->getKey(),
            'key' => $definition->key,
            'category' => $definition->category,
            'status' => $definition->status,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function dispatch(string $name, array $payload, int $subjectId, int $actorId): void
    {
        Phase5Event::dispatch($name, $payload, 'workflow_instance', $subjectId, $actorId);
    }
}
