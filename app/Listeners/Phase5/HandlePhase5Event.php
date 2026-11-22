<?php

declare(strict_types=1);

namespace App\Listeners\Phase5;

use App\Events\Phase5Event;
use App\Models\User;
use App\Services\Phase5\AutomationEngineService;
use App\Services\Phase5\NotificationCenterService;
use App\Services\Phase5\WebhookService;
use Illuminate\Support\Facades\DB;

/** Fans a safe Phase 5 event out to notifications, automations and webhooks. */
final class HandlePhase5Event
{
    /** @var array<string, string> */
    private const SUBJECT_TABLES = [
        'task' => 'tasks',
        'workflow_instance' => 'workflow_instances',
        'workflow_step' => 'workflow_steps',
        'document_review' => 'document_reviews',
        'accountability_case' => 'accountability_cases',
        'referral' => 'referrals',
    ];

    /** @var list<string> */
    private const RECIPIENT_COLUMNS = [
        'owner_id', 'creator_id', 'assignee_id', 'assigned_to',
        'initiator_id', 'reviewer_id', 'submitter_id',
    ];

    public function __construct(
        private readonly NotificationCenterService $notifications,
        private readonly AutomationEngineService $automations,
        private readonly WebhookService $webhooks,
    ) {}

    public function handle(Phase5Event $event): void
    {
        $this->notify($event);
        $this->automations->matchAndCreateExecutions($event);
        $this->webhooks->fanout($event);
    }

    private function notify(Phase5Event $event): void
    {
        if (str_ends_with($event->name, '.failed')) {
            return;
        }

        $recipients = $this->recipients($event);
        if ($recipients === []) {
            return;
        }

        $this->notifications->notifyUsers($recipients, [
            'category' => $this->category($event->name),
            'priority' => $this->priority($event->name),
            'title_key' => 'phase5.notification.'.str_replace('.', '_', $event->name),
            'preview_key' => 'phase5.notification.'.str_replace('.', '_', $event->name).'_preview',
            'resource_type' => $event->subjectType,
            'resource_id' => $event->subjectId,
        ], $event->idempotencyKey().':in-app');
    }

    /** @return list<int> */
    private function recipients(Phase5Event $event): array
    {
        $ids = $event->payload['user_ids'] ?? [];
        $candidates = is_array($ids) ? $ids : [];

        if ($candidates === [] && $event->subjectType !== null && $event->subjectId !== null) {
            $candidates = $this->subjectRecipients($event->subjectType, (int) $event->subjectId);
        }

        $ids = [];
        foreach ($candidates as $candidate) {
            if (is_int($candidate) || (is_string($candidate) && ctype_digit($candidate))) {
                $id = (int) $candidate;
                if ($id > 0 && ! in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }

        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', array_slice($ids, 0, 50))
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /** @return list<int> */
    private function subjectRecipients(string $type, int $id): array
    {
        $table = self::SUBJECT_TABLES[$type] ?? null;
        if ($table === null) {
            return [];
        }

        $columns = [];
        foreach (self::RECIPIENT_COLUMNS as $column) {
            if ($this->columnExists($table, $column)) {
                $columns[] = $column;
            }
        }
        if ($columns === []) {
            return [];
        }

        $row = DB::table($table)->where('id', $id)->first($columns);
        if ($row === null) {
            return [];
        }

        $ids = [];
        foreach ($columns as $column) {
            if ($row->{$column} !== null) {
                $ids[] = (int) $row->{$column};
            }
        }

        if ($type === 'task' && $this->columnExists('task_collaborators', 'user_id')) {
            foreach (DB::table('task_collaborators')->where('task_id', $id)->pluck('user_id') as $collaborator) {
                $ids[] = (int) $collaborator;
            }
        }

        return array_slice(array_values(array_unique(array_filter($ids))), 0, 50);
    }

    private function columnExists(string $table, string $column): bool
    {
        return in_array($column, DB::getSchemaBuilder()->getColumnListing($table), true);
    }

    private function category(string $eventName): string
    {
        $prefix = explode('.', $eventName, 2)[0];

        return match ($prefix) {
            'task' => 'task',
            'workflow' => 'workflow',
            'document' => 'document',
            'accountability' => 'accountability',
            'referral' => 'referral',
            'collaboration' => 'collaboration',
            'integration' => 'integration',
            'automation' => 'automation',
            default => 'system',
        };
    }

    private function priority(string $eventName): string
    {
        if (str_contains($eventName, 'overdue') || str_contains($eventName, 'urgent')) {
            return 'urgent';
        }
        if (str_contains($eventName, 'failed') || str_contains($eventName, 'due_soon')) {
            return 'high';
        }

        return 'normal';
    }
}
