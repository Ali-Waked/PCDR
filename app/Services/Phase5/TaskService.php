<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Events\Phase5Event;
use App\Models\DomainTransition;
use App\Models\Phase5\Task;
use App\Models\Phase5\TaskAssignmentHistory;
use App\Models\Phase5\TaskChecklistItem;
use App\Models\Phase5\TaskCollaborator;
use App\Models\Phase5\TaskDependency;
use App\Models\Phase5\WorkflowInstance;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Phase5Support;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Task lifecycle, assignment history, checklists and dependency graph.
 *
 * Only fields declared in config/phase5.php are accepted, all status changes
 * pass through Phase5Authorization, and every mutation is audited with a small
 * scalar-only snapshot.
 */
final class TaskService
{
    private const DEPENDENCY_TYPES = ['finish_to_start', 'start_to_finish', 'start_to_start', 'finish_to_finish'];

    private const VIEWS = ['my', 'assigned', 'team', 'overdue', 'upcoming', 'blocked', 'completed', 'project', 'workflow'];

    private const SORT_FIELDS = ['created_at', 'updated_at', 'id', 'code', 'title', 'status', 'priority', 'due_at', 'start_at'];

    private const MAX_EDGES_SCANNED = 5000;

    public function __construct(
        private readonly AuditService $audit,
        private readonly Phase5Authorization $authorization,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Task
    {
        $this->assertNoApproval($data, 'status', 'priority');
        $this->assertLinkable($actor, $data);

        $status = (string) ($data['status'] ?? 'backlog');
        $this->assertStatus('task', $status);

        $task = DB::transaction(function () use ($data, $actor, $status): Task {
            $task = Task::query()->create([
                'code' => 'TSK-'.strtoupper((string) Str::ulid()),
                'title' => Phase5Support::clean((string) $data['title'], 255),
                'description' => isset($data['description'])
                    ? Phase5Support::clean((string) $data['description'], (int) config('phase5.task.max_description_chars', 10000))
                    : null,
                'status' => $status,
                'priority' => (string) ($data['priority'] ?? 'normal'),
                'start_at' => isset($data['start_at']) ? (string) $data['start_at'] : null,
                'due_at' => isset($data['due_at']) ? (string) $data['due_at'] : null,
                'owner_id' => isset($data['owner_id']) ? (int) $data['owner_id'] : null,
                'creator_id' => $actor->id,
                'subject_type' => isset($data['subject_type']) ? (string) $data['subject_type'] : null,
                'subject_id' => isset($data['subject_id']) ? (int) $data['subject_id'] : null,
                'workflow_instance_id' => isset($data['workflow_instance_id']) ? (int) $data['workflow_instance_id'] : null,
                'parent_id' => isset($data['parent_id']) ? (int) $data['parent_id'] : null,
                'progress_percent' => 0,
            ]);

            if ($task->owner_id !== null) {
                TaskAssignmentHistory::query()->create([
                    'task_id' => $task->getKey(),
                    'from_user_id' => null,
                    'to_user_id' => $task->owner_id,
                    'actor_id' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            return $task;
        });

        $this->audit->record(
            'phase5.task.created',
            'task',
            (string) $task->getKey(),
            null,
            $this->auditSnapshot($task),
        );

        $payload = [
            'task_id' => $task->getKey(),
            'code' => $task->code,
            'status' => $task->status,
            'priority' => $task->priority,
        ];
        if ($task->subject_type !== null) {
            $payload['subject_type'] = $task->subject_type;
            $payload['subject_id'] = (int) $task->subject_id;
        }
        $recipients = $this->authorizedRecipients([$task->owner_id], (int) $task->getKey());
        if ($recipients !== []) {
            $payload['user_ids'] = $recipients;
        }
        $this->dispatch('task.created', $payload, (int) $task->getKey(), $actor->id);

        return $task;
    }

    /** @param array<string, mixed> $data */
    public function update(Task $task, array $data, User $actor): Task
    {
        $this->assertNoApproval($data, 'status', 'priority', 'title');
        $before = $this->auditSnapshot($task);

        $attributes = [];
        foreach (['title', 'description', 'priority', 'start_at', 'due_at'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $attributes[$field] = $field === 'title'
                ? Phase5Support::clean((string) $data[$field], 255)
                : ($data[$field] === null ? null : Phase5Support::clean((string) $data[$field], (int) config('phase5.task.max_description_chars', 10000)));
        }

        if ($attributes !== []) {
            $task->fill($attributes)->save();
        }

        $this->audit->record(
            'phase5.task.updated',
            'task',
            (string) $task->getKey(),
            $before,
            $this->auditSnapshot($task),
        );

        return $task->refresh();
    }

    public function delete(Task $task, User $actor): void
    {
        $before = $this->auditSnapshot($task);

        DB::transaction(function () use ($task): void {
            $task->delete();
        });

        $this->audit->record(
            'phase5.task.archived',
            'task',
            (string) $task->getKey(),
            $before,
            null,
        );
    }

    /** @param array<string, mixed> $data */
    public function assign(Task $task, array $data, User $actor): Task
    {
        $assigneeId = array_key_exists('assignee_id', $data) && $data['assignee_id'] !== null
            ? (int) $data['assignee_id']
            : null;

        if ($assigneeId !== null && ! User::query()->whereKey($assigneeId)->exists()) {
            throw new InvalidArgumentException(__('phase5.no_permission'));
        }

        $previous = $task->owner_id === null ? null : (int) $task->owner_id;

        DB::transaction(function () use ($task, $assigneeId, $actor, $previous): void {
            /** @var Task $locked */
            $locked = Task::query()->whereKey($task->getKey())->lockForUpdate()->firstOrFail();
            $locked->forceFill(['owner_id' => $assigneeId])->save();

            TaskAssignmentHistory::query()->create([
                'task_id' => $locked->getKey(),
                'from_user_id' => $previous,
                'to_user_id' => $assigneeId,
                'actor_id' => $actor->id,
                'created_at' => now(),
            ]);
        });

        $this->audit->record(
            'phase5.task.assigned',
            'task',
            (string) $task->getKey(),
            ['owner_id' => $previous],
            ['owner_id' => $assigneeId],
        );

        $payload = [
            'task_id' => $task->getKey(),
            'code' => $task->code,
            'assignee_id' => $assigneeId,
        ];
        $recipients = $this->authorizedRecipients([$previous, $assigneeId], (int) $task->getKey());
        if ($recipients !== []) {
            $payload['user_ids'] = $recipients;
        }
        $this->dispatch('task.assigned', $payload, (int) $task->getKey(), $actor->id);

        return $task->refresh();
    }

    /** @param array<string, mixed> $data */
    public function transition(Task $task, array $data, User $actor): Task
    {
        $this->assertNoApproval($data, 'to_status', 'action');

        $from = (string) $task->status;
        $to = (string) $data['to_status'];
        $reason = isset($data['reason']) ? Phase5Support::clean((string) $data['reason'], 2000) : null;

        $this->authorization->assertTransition($actor, 'task', $from, $to);

        if ($from === 'completed' && $to === 'in_progress' && $reason === null) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $state = ['from' => $from];

        DB::transaction(function () use ($task, $from, $to, $reason, $actor, $state): void {
            /** @var Task $locked */
            $locked = Task::query()->whereKey($task->getKey())->lockForUpdate()->firstOrFail();
            $actualFrom = (string) $locked->status;

            if ($actualFrom !== $from) {
                $this->authorization->assertTransition($actor, 'task', $actualFrom, $to);
            }
            $state['from'] = $actualFrom;

            $attributes = ['status' => $to];
            if ($to === 'completed') {
                $attributes['completed_at'] = now();
            } elseif ($actualFrom === 'completed' && $to === 'in_progress') {
                $attributes['completed_at'] = null;
            }
            $locked->forceFill($attributes)->save();

            DomainTransition::query()->create([
                'resource' => 'tasks',
                'owner_id' => $locked->getKey(),
                'status_field' => 'status',
                'from_status' => $actualFrom,
                'to_status' => $to,
                'actor_id' => $actor->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);
        });

        $this->audit->record(
            'phase5.task.status_changed',
            'task',
            (string) $task->getKey(),
            ['status' => $state['from']],
            ['status' => $to],
        );

        $payload = [
            'task_id' => $task->getKey(),
            'code' => $task->code,
            'from_status' => $state['from'],
            'to_status' => $to,
        ];
        $collaboratorIds = $task->collaborators()->pluck('user_id')->all();
        $recipients = $this->authorizedRecipients(
            [$task->owner_id, $task->creator_id, ...$collaboratorIds],
            (int) $task->getKey(),
        );
        if ($recipients !== []) {
            $payload['user_ids'] = $recipients;
        }
        $this->dispatch('task.status_changed', $payload, (int) $task->getKey(), $actor->id);

        return $task->refresh();
    }

    /** @param array<string, mixed> $data */
    public function addChecklistItem(Task $task, array $data, User $actor): TaskChecklistItem
    {
        $max = (int) config('phase5.task.max_checklist_items', 100);
        if ($task->checklist()->count() >= $max) {
            throw new InvalidArgumentException(__('phase5.governance_disabled'));
        }

        $item = DB::transaction(function () use ($task, $data): TaskChecklistItem {
            $position = isset($data['position'])
                ? max(0, (int) $data['position'])
                : (int) $task->checklist()->max('position') + 1;

            return TaskChecklistItem::query()->create([
                'task_id' => $task->getKey(),
                'title' => Phase5Support::clean((string) $data['title'], 255),
                'position' => $position,
                'is_done' => false,
            ]);
        });

        $this->audit->record(
            'phase5.task.checklist_added',
            'task',
            (string) $task->getKey(),
            null,
            ['checklist_item_id' => $item->getKey()],
        );

        return $item;
    }

    public function toggleChecklistItem(Task $task, TaskChecklistItem $item, User $actor): TaskChecklistItem
    {
        if ((int) $item->task_id !== (int) $task->getKey()) {
            abort(404, __('phase5.not_found'));
        }

        $done = ! (bool) $item->is_done;
        $item->forceFill([
            'is_done' => $done,
            'completed_by' => $done ? $actor->id : null,
            'completed_at' => $done ? now() : null,
        ])->save();

        $this->audit->record(
            'phase5.task.checklist_toggled',
            'task',
            (string) $task->getKey(),
            ['is_done' => ! $done],
            ['is_done' => $done, 'checklist_item_id' => $item->getKey()],
        );

        return $item;
    }

    /** @param array<string, mixed> $data */
    public function addDependency(Task $task, array $data, User $actor): TaskDependency
    {
        $dependsOnId = (int) $data['depends_on_task_id'];
        $type = (string) ($data['type'] ?? 'finish_to_start');

        if (! in_array($type, self::DEPENDENCY_TYPES, true)) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }
        if ($dependsOnId === (int) $task->getKey()) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $this->authorization->assertCanViewResource($actor, 'task', $dependsOnId);

        if (TaskDependency::query()->where('task_id', $task->getKey())->where('depends_on_task_id', $dependsOnId)->exists()) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }
        if ($this->wouldCreateCycle((int) $task->getKey(), $dependsOnId)) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $dependency = DB::transaction(function () use ($task, $dependsOnId, $type): TaskDependency {
            return TaskDependency::query()->create([
                'task_id' => $task->getKey(),
                'depends_on_task_id' => $dependsOnId,
                'type' => $type,
                'created_at' => now(),
            ]);
        });

        $this->audit->record(
            'phase5.task.dependency_added',
            'task',
            (string) $task->getKey(),
            null,
            ['depends_on_task_id' => $dependsOnId, 'type' => $type],
        );

        return $dependency;
    }

    public function wouldCreateCycle(int $taskId, int $dependsOnId): bool
    {
        $edges = DB::table('task_dependencies')
            ->orderBy('id')
            ->limit(self::MAX_EDGES_SCANNED)
            ->get(['task_id', 'depends_on_task_id']);

        // Fail closed when the bounded scan cannot prove absence of a cycle.
        if ($edges->count() >= self::MAX_EDGES_SCANNED) {
            return true;
        }

        $graph = [];
        foreach ($edges as $edge) {
            $graph[(int) $edge->task_id][] = (int) $edge->depends_on_task_id;
        }

        $visited = [];
        $frontier = [$dependsOnId];

        while ($frontier !== []) {
            $next = [];
            foreach ($frontier as $node) {
                if ($node === $taskId) {
                    return true;
                }
                if (isset($visited[$node])) {
                    continue;
                }
                $visited[$node] = true;
                $next = array_merge($next, $graph[$node] ?? []);
            }
            $frontier = $next;
        }

        return false;
    }

    public function addCollaborator(Task $task, int $userId, User $actor): void
    {
        if (! User::query()->whereKey($userId)->exists()) {
            throw new InvalidArgumentException(__('phase5.no_permission'));
        }

        $max = (int) config('phase5.task.max_collaborators', 20);
        if ($task->collaborators()->where('user_id', '!=', $userId)->count() >= $max) {
            throw new InvalidArgumentException(__('phase5.governance_disabled'));
        }

        $collaborator = TaskCollaborator::query()->firstOrCreate([
            'task_id' => $task->getKey(),
            'user_id' => $userId,
        ]);

        if ($collaborator->wasRecentlyCreated) {
            $this->audit->record(
                'phase5.task.collaborator_added',
                'task',
                (string) $task->getKey(),
                null,
                ['user_id' => $userId],
            );
        }
    }

    public function removeCollaborator(Task $task, int $userId, User $actor): void
    {
        $deleted = TaskCollaborator::query()
            ->where('task_id', $task->getKey())
            ->where('user_id', $userId)
            ->delete();

        if ($deleted > 0) {
            $this->audit->record(
                'phase5.task.collaborator_removed',
                'task',
                (string) $task->getKey(),
                ['user_id' => $userId],
                null,
            );
        }
    }

    public function canView(Task $task, User $user): bool
    {
        return $this->authorization->canViewResource($user, 'task', (int) $task->getKey());
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, User $actor): LengthAwarePaginator
    {
        $query = Task::query()->with([
            'owner:id,name',
            'creator:id,name',
        ]);

        $view = in_array($filters['view'] ?? null, self::VIEWS, true) ? (string) $filters['view'] : 'assigned';
        $this->applyView($query, $view, $filters, $actor);

        if (isset($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['priority'])) {
            $query->where('priority', (string) $filters['priority']);
        }
        if (isset($filters['search'])) {
            $query->where('title', 'like', '%'.mb_substr((string) $filters['search'], 0, 100).'%');
        }

        $sort = in_array($filters['sort'] ?? null, self::SORT_FIELDS, true) ? (string) $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', $direction)
            ->paginate(max(1, min(50, (int) ($filters['per_page'] ?? 15))))
            ->withQueryString();
    }

    /** @param iterable<Task> $tasks @return list<array<string, mixed>> */
    public function taskPayloads(iterable $tasks): array
    {
        return array_map(
            fn (Task $task): array => $this->taskPayload($task),
            is_array($tasks) ? $tasks : iterator_to_array($tasks),
        );
    }

    /** @return array<string, mixed> */
    public function taskPayload(Task $task): array
    {
        return [
            'id' => $task->getKey(),
            'code' => $task->code,
            'title' => $task->title,
            'description' => $task->description === null ? null : Phase5Support::clean($task->description, 2000),
            'status' => $task->status,
            'priority' => $task->priority,
            'start_at' => $task->start_at?->toISOString(),
            'due_at' => $task->due_at?->toISOString(),
            'owner_id' => $task->owner_id,
            'owner_name' => $task->owner?->name,
            'creator_id' => $task->creator_id,
            'creator_name' => $task->creator?->name,
            'subject_type' => $task->subject_type,
            'subject_id' => $task->subject_id,
            'workflow_instance_id' => $task->workflow_instance_id,
            'progress_percent' => $task->progress_percent,
            'created_at' => $task->created_at?->toISOString(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function checklistPayloads(Task $task): array
    {
        return $task->checklist()->orderBy('position')->orderBy('id')->get()
            ->map(static fn (TaskChecklistItem $item): array => [
                'id' => $item->getKey(),
                'title' => $item->title,
                'position' => $item->position,
                'is_done' => (bool) $item->is_done,
                'completed_by' => $item->completed_by,
                'completed_at' => $item->completed_at?->toISOString(),
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    public function dependencyPayloads(Task $task): array
    {
        return $task->dependencies()
            ->with('dependsOn:id,code,title')
            ->orderBy('id')
            ->get()
            ->map(static fn (TaskDependency $dependency): array => [
                'id' => $dependency->getKey(),
                'task_id' => $dependency->task_id,
                'depends_on_task_id' => $dependency->depends_on_task_id,
                'depends_on_code' => $dependency->dependsOn?->code,
                'depends_on_title' => $dependency->dependsOn?->title,
                'type' => $dependency->type,
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    public function collaboratorPayloads(Task $task): array
    {
        return $task->collaborators()->with('user:id,name')->orderBy('id')->get()
            ->map(static fn (TaskCollaborator $collaborator): array => [
                'user_id' => $collaborator->user_id,
                'name' => $collaborator->user?->name,
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    public function activity(Task $task): array
    {
        $events = [];

        foreach (DomainTransition::query()
            ->where('resource', 'tasks')
            ->where('owner_id', $task->getKey())
            ->orderByDesc('created_at')->orderByDesc('id')->limit(15)->get() as $transition) {
            $events[] = [
                'id' => 'domain-'.$transition->getKey(),
                'source' => 'status',
                'action' => 'status_changed',
                'from' => $transition->from_status,
                'to' => $transition->to_status,
                'actor_id' => $transition->actor_id,
                'created_at' => $transition->created_at?->toISOString(),
            ];
        }

        foreach (TaskAssignmentHistory::query()
            ->where('task_id', $task->getKey())
            ->orderByDesc('created_at')->orderByDesc('id')->limit(15)->get() as $history) {
            $events[] = [
                'id' => 'assignment-'.$history->getKey(),
                'source' => 'assignment',
                'action' => 'assigned',
                'from' => $history->from_user_id,
                'to' => $history->to_user_id,
                'actor_id' => $history->actor_id,
                'created_at' => $history->created_at?->toISOString(),
            ];
        }

        foreach (DB::table('audit_logs')
            ->where('subject_type', 'task')
            ->where('subject_id', (string) $task->getKey())
            ->orderByDesc('created_at')->orderByDesc('id')->limit(15)
            ->get(['id', 'action', 'actor_id', 'created_at']) as $audit) {
            $events[] = [
                'id' => 'audit-'.$audit->id,
                'source' => 'audit',
                'action' => $audit->action,
                'from' => null,
                'to' => null,
                'actor_id' => $audit->actor_id,
                'created_at' => $audit->created_at,
            ];
        }

        usort($events, static fn (array $left, array $right): int => strcmp(
            (string) $right['created_at'],
            (string) $left['created_at'],
        ) ?: strcmp($left['id'], $right['id']));

        return array_slice($events, 0, 30);
    }

    /**
     * @param  Builder<Task>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyView($query, string $view, array $filters, User $actor): void
    {
        switch ($view) {
            case 'my':
                $query->where('creator_id', $actor->id);
                break;
            case 'assigned':
                $query->where('owner_id', $actor->id);
                break;
            case 'team':
                $query->where(function ($query) use ($actor): void {
                    $query->where('owner_id', $actor->id)
                        ->orWhere('creator_id', $actor->id)
                        ->orWhereHas('collaborators', fn ($collaborators) => $collaborators->where('user_id', $actor->id));
                });
                break;
            case 'overdue':
                $query->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
                break;
            case 'upcoming':
                $query->whereNotNull('due_at')
                    ->whereBetween('due_at', [now(), now()->addDays(7)])
                    ->whereNotIn('status', ['completed', 'cancelled']);
                break;
            case 'blocked':
                $query->where('status', 'blocked');
                break;
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'project':
                $projectId = (int) ($filters['project_id'] ?? 0);
                $this->authorization->assertCanViewResource($actor, 'project', $projectId);
                $query->where('subject_type', 'project')->where('subject_id', $projectId);
                break;
            case 'workflow':
                $instanceId = (int) ($filters['workflow_instance_id'] ?? 0);
                $this->authorization->assertCanViewResource($actor, 'workflow_instance', $instanceId);
                $query->where('workflow_instance_id', $instanceId);
                break;
        }
    }

    /** @param array<string, mixed> $data */
    private function assertLinkable(User $actor, array $data): void
    {
        $linkable = array_values((array) config('phase5.task.linkable_resources', []));

        if (isset($data['subject_type']) && ! in_array((string) $data['subject_type'], $linkable, true)) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }
        if (isset($data['subject_type'], $data['subject_id'])) {
            $this->authorization->assertCanViewResource(
                $actor,
                (string) $data['subject_type'],
                (int) $data['subject_id'],
            );
        }
        if (isset($data['workflow_instance_id'])) {
            $instance = WorkflowInstance::query()->findOrFail((int) $data['workflow_instance_id']);
            $this->authorization->assertCanViewResource($actor, 'workflow_instance', (int) $instance->getKey());
        }
        if (isset($data['parent_id'])) {
            $parent = Task::query()->findOrFail((int) $data['parent_id']);
            $this->authorization->assertCanViewResource($actor, 'task', (int) $parent->getKey());
        }
    }

    private function assertStatus(string $domain, string $status): void
    {
        if (! in_array($status, array_values((array) config("phase5.{$domain}.statuses", [])), true)) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }
    }

    /** @param array<string, mixed> $data */
    private function assertNoApproval(array $data, string ...$fields): void
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])
                && str_contains(mb_strtolower($data[$field]), 'approv')) {
                throw new InvalidArgumentException(__('phase5.governance_disabled'));
            }
        }
    }

    /** @param array<int|null> $candidates @return list<int> */
    private function authorizedRecipients(array $candidates, int $taskId): array
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
            if ($this->authorization->canViewResource($user, 'task', $taskId)) {
                $recipients[] = (int) $user->id;
            }
        }

        return $recipients;
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(Task $task): array
    {
        return [
            'id' => $task->getKey(),
            'code' => $task->code,
            'status' => $task->status,
            'priority' => $task->priority,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function dispatch(string $name, array $payload, int $subjectId, int $actorId): void
    {
        Phase5Event::dispatch($name, $payload, 'task', $subjectId, $actorId);
    }
}
