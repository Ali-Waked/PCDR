<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Models\User;
use App\Support\Phase5Support;
use Illuminate\Support\Facades\DB;

/** Aggregate-only Phase 5 dashboard. No names, narratives or personal data. */
final class Phase5DashboardService
{
    public function __construct(
        private readonly Phase5Authorization $authorization,
        private readonly Phase5ActivityService $activity,
    ) {}

    /** @return array<string, mixed> */
    public function dashboard(User $user): array
    {
        $summary = [];
        $sections = [];

        foreach ((array) config('phase5.dashboard.sections', []) as $key => $permission) {
            $key = (string) $key;
            $permission = (string) $permission;
            if (! $this->authorization->allows($user, $permission)) {
                continue;
            }

            $count = $this->count($key, $user);
            $summary[$key] = $count;
            $sections[] = [
                'key' => $key,
                'label_key' => 'phase5.dashboard.'.$key,
                'count' => $count,
            ];
        }

        $notice = Phase5Support::localized(
            is_array(config('phase5.governance.approval_notice'))
                ? config('phase5.governance.approval_notice')
                : [],
        );

        return [
            'summary' => $summary,
            'sections' => $sections,
            'activity' => $this->activity->activity($user),
            'gates' => $this->authorization->gates(),
            'notice' => $notice,
        ];
    }

    private function count(string $section, User $user): int
    {
        return match ($section) {
            'workflows' => (int) DB::table('workflow_instances')->count(),
            'tasks' => (int) DB::table('tasks')->whereNull('deleted_at')->count(),
            'documents' => (int) DB::table('document_reviews')->whereNull('deleted_at')->count(),
            'accountability' => $this->accountabilityCount($user),
            'referrals' => (int) DB::table('referrals')->whereNull('deleted_at')->count(),
            'automation' => (int) DB::table('automation_definitions')->whereNull('deleted_at')->count(),
            'integrations' => (int) DB::table('integration_registries')->whereNull('deleted_at')->count(),
            'notifications' => (int) DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            default => 0,
        };
    }

    private function accountabilityCount(User $user): int
    {
        $query = DB::table('accountability_cases')->whereNull('deleted_at');

        if (! $this->authorization->allows($user, 'accountability.view_sensitive')) {
            $query->where('is_sensitive', false);
        }

        return (int) $query->count();
    }
}
