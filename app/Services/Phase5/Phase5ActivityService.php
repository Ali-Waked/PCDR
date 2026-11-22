<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Permission-filtered, aggregate-only activity timeline.
 *
 * Only action keys, resource types, identifiers and timestamps are exposed.
 * Notes, narratives, document contents and personal data never leave the
 * source tables.
 */
final class Phase5ActivityService
{
    /** @var array<string, string> */
    private const PERMISSIONS = [
        'task' => 'tasks.view',
        'workflow_instance' => 'workflows.view',
        'document_review' => 'documents.review',
        'accountability_case' => 'accountability.view',
        'referral' => 'referrals.view',
        'automation_definition' => 'automations.view',
        'automation_execution' => 'automations.view',
        'integration_registry' => 'integrations.view',
        'webhook_endpoint' => 'integrations.view',
        'webhook_delivery' => 'integrations.view',
        'notification_preference' => 'notifications.preferences',
    ];

    public function __construct(private readonly Phase5Authorization $authorization) {}

    /** @param array<string, mixed> $filters @return list<array<string, mixed>> */
    public function activity(User $user, array $filters = []): array
    {
        $limit = min(max((int) ($filters['per_page'] ?? 25), 1), (int) config('phase5.dashboard.max_activity', 25));
        $items = [];

        foreach ($this->auditRows($filters, $limit * 2) as $row) {
            $items[] = $this->present($row, 'audit');
        }

        foreach ($this->historyRows($user, $filters, $limit) as $row) {
            $items[] = $this->present($row, 'history');
        }

        usort($items, static fn (array $left, array $right): int => strcmp(
            (string) $right['occurred_at'],
            (string) $left['occurred_at'],
        ) ?: ((int) $right['id'] <=> (int) $left['id']));

        $result = [];
        $seen = [];

        foreach ($items as $item) {
            $fingerprint = $item['source'].':'.$item['subject_type'].':'.$item['subject_id'].':'.$item['action'].':'.$item['occurred_at'];
            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $result[] = $item;

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'actions' => array_values(array_unique([
                'phase5.task.created',
                'phase5.task.status_changed',
                'phase5.workflow.status_changed',
                'phase5.document.status_changed',
                'phase5.automation.execution_succeeded',
                'phase5.automation.execution_failed',
                'phase5.webhook.endpoint_created',
                'phase5.webhook.inbound_accepted',
            ])),
            'subject_types' => array_keys(self::PERMISSIONS),
            'max_results' => (int) config('phase5.dashboard.max_activity', 25),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<object>
     */
    private function auditRows(array $filters, int $limit): array
    {
        $query = DB::table('audit_logs')
            ->select(['id', 'action', 'subject_type', 'subject_id', 'created_at'])
            ->where('action', 'like', 'phase5.%');

        if (! empty($filters['action'])) {
            $query->where('action', (string) $filters['action']);
        }
        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', (string) $filters['subject_type']);
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', (string) $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', (string) $filters['to']);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function historyRows(User $user, array $filters, int $limit): array
    {
        $definitions = [
            ['table' => 'workflow_transitions', 'type' => 'workflow_instance', 'action' => 'workflow.transitioned', 'id' => 'instance_id', 'status' => 'to_status'],
            ['table' => 'task_assignment_history', 'type' => 'task', 'action' => 'task.assigned', 'id' => 'task_id', 'status' => null],
            ['table' => 'accountability_events', 'type' => 'accountability_case', 'action' => 'accountability.status_changed', 'id' => 'case_id', 'status' => 'to_status'],
            ['table' => 'referral_events', 'type' => 'referral', 'action' => 'referral.status_changed', 'id' => 'referral_id', 'status' => 'to_status'],
            ['table' => 'document_review_events', 'type' => 'document_review', 'action' => 'document.status_changed', 'id' => 'review_id', 'status' => 'to_status'],
        ];

        $rows = [];

        foreach ($definitions as $definition) {
            if (! $this->visible($user, $definition['type'], 0)) {
                continue;
            }
            if (! empty($filters['subject_type']) && $filters['subject_type'] !== $definition['type']) {
                continue;
            }

            $query = DB::table($definition['table'])
                ->select(array_values(array_filter(['id', $definition['id'], $definition['status'], 'created_at'])))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit);

            if (! empty($filters['from'])) {
                $query->where('created_at', '>=', (string) $filters['from']);
            }
            if (! empty($filters['to'])) {
                $query->where('created_at', '<=', (string) $filters['to']);
            }

            foreach ($query->get() as $row) {
                $subjectId = (int) $row->{$definition['id']};
                if (! $this->visible($user, $definition['type'], $subjectId)) {
                    continue;
                }

                $rows[] = [
                    'id' => (int) $row->id,
                    'action' => $definition['action'],
                    'subject_type' => $definition['type'],
                    'subject_id' => (string) $subjectId,
                    'status' => $definition['status'] !== null ? (string) ($row->{$definition['status']} ?? '') : null,
                    'created_at' => (string) $row->created_at,
                ];
            }
        }

        return $rows;
    }

    /** @param array<string, mixed> $row */
    private function present(array|object $row, string $source): array
    {
        if (is_object($row)) {
            $row = [
                'id' => (int) $row->id,
                'action' => (string) $row->action,
                'subject_type' => (string) $row->subject_type,
                'subject_id' => (string) $row->subject_id,
                'status' => null,
                'created_at' => (string) $row->created_at,
            ];
        }

        return [
            'id' => (int) $row['id'],
            'source' => $source,
            'action' => $this->safeKey((string) $row['action']),
            'subject_type' => $this->safeKey((string) $row['subject_type']),
            'subject_id' => (string) $row['subject_id'],
            'status' => isset($row['status']) ? $this->safeKey((string) $row['status']) : null,
            'occurred_at' => (string) $row['created_at'],
        ];
    }

    private function visible(User $user, string $type, int $id): bool
    {
        $permission = self::PERMISSIONS[$type] ?? null;
        if ($permission === null || ! $this->authorization->allows($user, $permission)) {
            return false;
        }

        if ($id === 0 || $user->isPlatformOwner()) {
            return true;
        }

        return $this->authorization->canViewResource($user, $type, $id);
    }

    private function safeKey(string $value): string
    {
        return preg_match('/^[a-z0-9][a-z0-9_.:-]{0,127}$/i', $value) ? $value : 'unknown';
    }
}
