<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Exceptions\InvalidDomainTransition;
use App\Exceptions\Phase5GovernanceException;
use App\Models\User;
use App\Services\CmsAccess;
use App\Services\RbacService;
use Illuminate\Support\Facades\DB;

/**
 * Single authorization seam for the Phase 5 operating layer.
 *
 * Route middleware checks broad permissions; this service enforces resource
 * boundaries, sensitive-category restrictions and governance gates. A user
 * never receives access to a restricted source record through a mention, a
 * comment, a task link or a notification.
 */
final class Phase5Authorization
{
    private const DOMAIN_PERMISSIONS = [
        'workflow' => 'workflows.transition',
        'task' => 'tasks.transition',
        'accountability' => 'accountability.update',
        'referral' => 'referrals.transition',
        'document_review' => 'documents.review',
    ];

    private const RESOURCE_PERMISSIONS = [
        'task' => 'tasks.view',
        'workflow_instance' => 'workflows.view',
        'document_review' => 'documents.review',
        'accountability_case' => 'accountability.view',
        'referral' => 'referrals.view',
        'operational_record' => 'reports.view',
        'project' => 'projects.view',
        'program' => 'programs.view',
        'activity' => 'activities.view',
        'intervention' => 'interventions.view',
        'location' => 'locations.view',
        'partner' => 'partners.view',
        'committee_meeting' => 'committees.view',
        'community_feedback' => 'committees.view',
    ];

    public function __construct(
        private readonly RbacService $rbac,
        private readonly CmsAccess $cms,
    ) {}

    public function allows(User $user, string $permission): bool
    {
        return $this->rbac->has($user, $permission) || $this->cms->allows($user, $permission);
    }

    public function assertAllows(User $user, string $permission): void
    {
        if (! $this->allows($user, $permission)) {
            abort(403, __('phase5.no_permission'));
        }
    }

    /** @return array<string, bool> */
    public function gates(): array
    {
        return array_map(
            static fn (mixed $value): bool => (bool) $value,
            (array) config('phase5.gates', []),
        );
    }

    public function gateEnabled(string $key): bool
    {
        return (bool) ($this->gates()[$key] ?? false);
    }

    public function assertGate(string $key, string $decision): void
    {
        if (! $this->gateEnabled($key)) {
            throw new Phase5GovernanceException($key, $decision);
        }
    }

    public function canViewResource(User $user, string $type, int|string $id): bool
    {
        $permission = self::RESOURCE_PERMISSIONS[$type] ?? null;

        if ($permission === null) {
            return false;
        }

        // A submitter needs review visibility for their own document even when
        // they only hold the submission capability. General reviewers and
        // platform owners still pass through the normal permission check.
        $allowed = $this->allows($user, $permission);
        if (! $allowed && $type === 'document_review') {
            $allowed = $this->allows($user, 'documents.submit_review');
        }

        if (! $allowed) {
            return false;
        }

        if ($user->isPlatformOwner()) {
            return true;
        }

        return match ($type) {
            'task' => $this->canViewTask($user, (int) $id),
            'workflow_instance' => $this->canViewWorkflowInstance($user, (int) $id),
            'accountability_case' => $this->canViewCase($user, (int) $id),
            'referral' => $this->canViewReferral($user, (int) $id),
            'document_review' => $this->canViewDocumentReview($user, (int) $id),
            default => true,
        };
    }

    public function assertCanViewResource(User $user, string $type, int|string $id): void
    {
        // 404 prevents enumeration of records the user is not allowed to know exist.
        if (! $this->canViewResource($user, $type, $id)) {
            abort(404, __('phase5.not_found'));
        }
    }

    public function canComment(User $user, string $type, int|string $id): bool
    {
        return $this->allows($user, 'collaboration.comment')
            && $this->canViewResource($user, $type, $id);
    }

    public function canMention(User $user, User $mentioned, string $type, int|string $id): bool
    {
        return $this->allows($user, 'collaboration.mention')
            && $this->canComment($user, $type, $id)
            && $this->canViewResource($mentioned, $type, $id);
    }

    public function assertTransition(User $user, string $domain, ?string $from, string $to): void
    {
        $permission = self::DOMAIN_PERMISSIONS[$domain] ?? null;

        if ($permission === null || ! $this->allows($user, $permission)) {
            abort(403, __('phase5.no_permission'));
        }

        $transitions = (array) config("phase5.{$domain}.transitions", []);

        if ($from === null || ! in_array($to, $transitions[$from] ?? [], true)) {
            throw new InvalidDomainTransition('phase5.'.$domain, $from, $to);
        }
    }

    private function canViewTask(User $user, int $id): bool
    {
        $task = DB::table('tasks')->where('id', $id)->first(['owner_id', 'creator_id', 'subject_type', 'subject_id']);

        if ($task === null) {
            return false;
        }

        if ($this->isDirectParticipant($user, $task->owner_id, $task->creator_id)) {
            return true;
        }

        if (DB::table('task_collaborators')->where('task_id', $id)->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $task->subject_type === null
            || $task->subject_id === null
            || $this->canViewResource($user, (string) $task->subject_type, (int) $task->subject_id);
    }

    private function canViewWorkflowInstance(User $user, int $id): bool
    {
        $instance = DB::table('workflow_instances')
            ->where('id', $id)
            ->first(['initiator_id', 'subject_type', 'subject_id']);

        if ($instance === null) {
            return false;
        }

        if ($user->id === (int) $instance->initiator_id) {
            return true;
        }

        $stepAssignee = DB::table('workflow_steps')
            ->where('instance_id', $id)
            ->where('assignee_id', $user->id)
            ->exists();

        if ($stepAssignee) {
            return true;
        }

        return $instance->subject_type === null
            || $instance->subject_id === null
            || $this->canViewResource($user, (string) $instance->subject_type, (int) $instance->subject_id);
    }

    private function canViewCase(User $user, int $id): bool
    {
        $case = DB::table('accountability_cases')->where('id', $id)->first(['is_sensitive', 'assigned_to']);

        if ($case === null) {
            return false;
        }

        if ((bool) $case->is_sensitive && ! $this->allows($user, 'accountability.view_sensitive')) {
            return false;
        }

        return $user->id === (int) $case->assigned_to
            || $this->allows($user, 'accountability.view');
    }

    private function canViewReferral(User $user, int $id): bool
    {
        $referral = DB::table('referrals')
            ->where('id', $id)
            ->first(['source_type', 'source_id', 'assigned_to', 'created_by']);

        if ($referral === null) {
            return false;
        }

        if ($this->isDirectParticipant($user, $referral->assigned_to, $referral->created_by)) {
            return true;
        }

        return $this->canViewResource($user, (string) $referral->source_type, (int) $referral->source_id);
    }

    private function canViewDocumentReview(User $user, int $id): bool
    {
        $review = DB::table('document_reviews')
            ->where('id', $id)
            ->first(['subject_type', 'subject_id', 'reviewer_id', 'submitter_id']);

        if ($review === null) {
            return false;
        }

        if ($this->isDirectParticipant($user, $review->reviewer_id, $review->submitter_id)) {
            return true;
        }

        return $review->subject_type === null
            || $review->subject_id === null
            || $this->canViewResource($user, (string) $review->subject_type, (int) $review->subject_id);
    }

    private function isDirectParticipant(User $user, mixed $owner, mixed $creator): bool
    {
        return $user->id === (int) $owner || $user->id === (int) $creator;
    }
}
