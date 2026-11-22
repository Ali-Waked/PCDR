<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\Phase5Event;
use App\Models\User;
use App\Services\Phase5\Phase5Authorization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Emits bounded, idempotent due-soon and overdue Phase 5 domain events. */
final class Phase5ScanDeadlines extends Command
{
    protected $signature = 'phase5:scan-deadlines {--limit=90} {--dry-run}';

    protected $description = 'Emit bounded, idempotent Phase 5 deadline events from existing due dates.';

    public function __construct(private readonly Phase5Authorization $authorization)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = min(max((int) $this->option('limit'), 3), 90);
        $perSource = intdiv($limit, 3);
        $bucket = now()->toDateString();
        $emitted = 0;
        $seen = [];

        foreach ($this->tasks($perSource) as $row) {
            $emitted += $this->emit(
                $row->due_at < now() ? 'task.overdue' : 'task.due_soon',
                'task',
                (int) $row->id,
                (string) $row->due_at,
                $bucket,
                $this->taskRecipients((int) $row->id, [$row->owner_id, $row->creator_id]),
                $seen,
                (bool) $this->option('dry-run'),
            );
        }

        foreach ($this->workflowSteps($perSource) as $row) {
            $emitted += $this->emit(
                $row->due_at < now() ? 'workflow.step_overdue' : 'workflow.step_due_soon',
                'workflow_instance',
                (int) $row->instance_id,
                (string) $row->due_at,
                $bucket,
                [$row->assignee_id, $row->initiator_id],
                $seen,
                (bool) $this->option('dry-run'),
            );
        }

        foreach ($this->referrals($perSource) as $row) {
            $emitted += $this->emit(
                $row->due_at < now() ? 'referral.overdue' : 'referral.due_soon',
                'referral',
                (int) $row->id,
                (string) $row->due_at,
                $bucket,
                [$row->assigned_to, $row->created_by],
                $seen,
                (bool) $this->option('dry-run'),
            );
        }

        $this->info(sprintf('Phase 5 deadline scan complete: %d event(s).', $emitted));

        return self::SUCCESS;
    }

    /** @return list<object> */
    private function tasks(int $limit): array
    {
        return DB::table('tasks')
            ->select(['id', 'owner_id', 'creator_id', 'due_at'])
            ->whereNull('deleted_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDay())
            ->whereNotIn('status', ['completed', 'cancelled', 'archived'])
            ->orderBy('due_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    /** @return list<object> */
    private function workflowSteps(int $limit): array
    {
        return DB::table('workflow_steps')
            ->join('workflow_instances', 'workflow_instances.id', '=', 'workflow_steps.instance_id')
            ->select([
                'workflow_steps.id',
                'workflow_steps.instance_id',
                'workflow_steps.assignee_id',
                'workflow_steps.due_at',
                'workflow_instances.initiator_id',
            ])
            ->whereNotNull('workflow_steps.due_at')
            ->where('workflow_steps.due_at', '<=', now()->addDay())
            ->whereNotIn('workflow_steps.status', ['completed', 'rejected', 'skipped', 'cancelled'])
            ->orderBy('workflow_steps.due_at')
            ->orderBy('workflow_steps.id')
            ->limit($limit)
            ->get()
            ->all();
    }

    /** @return list<object> */
    private function referrals(int $limit): array
    {
        return DB::table('referrals')
            ->select(['id', 'assigned_to', 'created_by', 'due_at'])
            ->whereNull('deleted_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDay())
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->orderBy('due_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @param  list<mixed>  $candidates
     * @param  array<string, true>  $seen
     */
    private function emit(
        string $name,
        string $subjectType,
        int $subjectId,
        string $dueAt,
        string $bucket,
        array $candidates,
        array &$seen,
        bool $dryRun,
    ): int {
        $recipients = $this->authorizedRecipients($subjectType, $subjectId, $candidates);
        $payload = [
            'subject_id' => $subjectId,
            'due_at' => $dueAt,
            'date_bucket' => $bucket,
        ];
        if ($recipients !== []) {
            $payload['user_ids'] = $recipients;
        }

        $event = new Phase5Event($name, $payload, $subjectType, $subjectId);
        $key = $event->idempotencyKey();
        if (isset($seen[$key])) {
            return 0;
        }
        $seen[$key] = true;

        if (! $dryRun) {
            event($event);
        }

        return 1;
    }

    /**
     * @param  list<mixed>  $candidates
     * @return list<int>
     */
    private function authorizedRecipients(string $subjectType, int $subjectId, array $candidates): array
    {
        $ids = [];
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }
            $id = (int) $candidate;
            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        $ids = array_slice($ids, 0, 20);
        if ($ids === []) {
            return [];
        }

        $users = User::query()->whereIn('id', $ids)->get(['id']);
        $result = [];
        foreach ($users as $user) {
            if ($this->authorization->canViewResource($user, $subjectType, $subjectId)) {
                $result[] = (int) $user->id;
            }
        }

        return $result;
    }

    /** @param list<mixed> $candidates @return list<mixed> */
    private function taskRecipients(int $taskId, array $candidates): array
    {
        foreach (DB::table('task_collaborators')->where('task_id', $taskId)->pluck('user_id') as $collaborator) {
            $candidates[] = $collaborator;
        }

        return $candidates;
    }
}
