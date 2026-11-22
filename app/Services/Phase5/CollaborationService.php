<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Events\Phase5Event;
use App\Models\Phase5\CommentMention;
use App\Models\Phase5\DiscussionComment;
use App\Models\Phase5\DiscussionThread;
use App\Models\Phase5\ResourceFollower;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Phase5Support;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Internal discussions, follow state and a bounded cross-source timeline.
 *
 * Mention recipients are authorized against the underlying resource before a
 * comment is written and before any event is dispatched. A mention never
 * grants access to the source record and never carries its content.
 */
final class CollaborationService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function threadFor(string $type, int $id, User $actor, bool $create = false): ?DiscussionThread
    {
        if (! in_array($type, $this->commentableResources(), true)) {
            abort(404, __('phase5.not_found'));
        }

        $this->authorization->assertCanViewResource($actor, $type, $id);

        if (! $create) {
            return DiscussionThread::query()
                ->where('resource_type', $type)
                ->where('resource_id', $id)
                ->first();
        }

        return DiscussionThread::query()->firstOrCreate([
            'resource_type' => $type,
            'resource_id' => $id,
        ], [
            'created_by' => $actor->id,
        ]);
    }

    /**
     * Authorize every recipient before the caller writes or dispatches anything.
     *
     * @param  array<int>  $mentionedUserIds
     */
    public function assertMentionsAllowed(User $actor, array $mentionedUserIds, string $type, int $id): void
    {
        if ($mentionedUserIds === []) {
            return;
        }
        if (! $this->authorization->allows($actor, 'collaboration.mention')
            || ! $this->authorization->canComment($actor, $type, $id)) {
            abort(403, __('phase5.no_permission'));
        }

        $max = (int) config('phase5.collaboration.max_mentions_per_comment', 10);
        if (count($mentionedUserIds) > $max) {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $users = User::query()->whereIn('id', $mentionedUserIds)->get(['id'])->keyBy('id');
        if ($users->count() !== count($mentionedUserIds)) {
            abort(404, __('phase5.not_found'));
        }

        foreach ($mentionedUserIds as $userId) {
            $mentioned = $users->get($userId);
            if ($mentioned === null
                || ! $this->authorization->canMention($actor, $mentioned, $type, $id)) {
                // 404 avoids confirming the existence of a restricted source.
                abort(404, __('phase5.not_found'));
            }
        }
    }

    /** @param array<string, mixed> $data */
    public function storeComment(DiscussionThread $thread, array $data, User $actor): DiscussionComment
    {
        $type = (string) $thread->resource_type;
        $resourceId = (int) $thread->resource_id;
        $mentions = array_values(array_unique(array_map('intval', (array) ($data['mentions'] ?? []))));

        $this->assertMentionsAllowed($actor, $mentions, $type, $resourceId);

        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId !== null && ! DiscussionComment::query()
            ->where('thread_id', $thread->getKey())
            ->whereKey($parentId)
            ->exists()) {
            abort(404, __('phase5.not_found'));
        }

        $body = Phase5Support::clean(
            (string) $data['body'],
            (int) config('phase5.collaboration.max_comment_chars', 5000),
        );
        if ($body === '') {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $comment = DB::transaction(function () use ($thread, $body, $parentId, $mentions, $actor): DiscussionComment {
            $comment = DiscussionComment::query()->create([
                'thread_id' => $thread->getKey(),
                'parent_id' => $parentId,
                'author_id' => $actor->id,
                'body' => $body,
            ]);

            foreach ($mentions as $userId) {
                CommentMention::query()->create([
                    'comment_id' => $comment->getKey(),
                    'mentioned_user_id' => $userId,
                ]);
            }

            return $comment;
        });

        $this->audit->record(
            'phase5.collaboration.comment_created',
            'discussion_comment',
            (string) $comment->getKey(),
            null,
            [
                'resource_type' => $type,
                'resource_id' => $resourceId,
                'mention_count' => count($mentions),
            ],
        );

        if ($mentions !== []) {
            $this->authorization->assertCanViewResource($actor, $type, $resourceId);
            Phase5Event::dispatch('collaboration.mention', [
                'resource_type' => $type,
                'resource_id' => $resourceId,
                'comment_id' => $comment->getKey(),
                'user_ids' => $mentions,
            ], $type, $resourceId, $actor->id);
        }

        return $comment->load('mentions');
    }

    /** @param array<string, mixed> $data */
    public function updateComment(DiscussionComment $comment, array $data, User $actor): DiscussionComment
    {
        $this->assertCanModifyComment($comment, $actor, false);

        $body = Phase5Support::clean(
            (string) $data['body'],
            (int) config('phase5.collaboration.max_comment_chars', 5000),
        );
        if ($body === '') {
            throw new InvalidArgumentException(__('phase5.invalid_transition'));
        }

        $comment->forceFill(['body' => $body, 'edited_at' => now()])->save();

        $this->audit->record(
            'phase5.collaboration.comment_updated',
            'discussion_comment',
            (string) $comment->getKey(),
            null,
            ['thread_id' => $comment->thread_id],
        );

        return $comment;
    }

    public function deleteComment(DiscussionComment $comment, User $actor): void
    {
        $this->assertCanModifyComment($comment, $actor, true);

        $comment->delete();

        $this->audit->record(
            'phase5.collaboration.comment_deleted',
            'discussion_comment',
            (string) $comment->getKey(),
            null,
            ['thread_id' => $comment->thread_id],
        );
    }

    public function toggleFollow(string $type, int $id, User $actor): bool
    {
        if (! in_array($type, $this->commentableResources(), true)) {
            abort(404, __('phase5.not_found'));
        }

        $this->authorization->assertCanViewResource($actor, $type, $id);

        $following = false;
        DB::transaction(function () use ($type, $id, $actor, &$following): void {
            $follower = ResourceFollower::query()
                ->where('resource_type', $type)
                ->where('resource_id', $id)
                ->where('user_id', $actor->id)
                ->first();

            if ($follower !== null) {
                $follower->delete();
                $following = false;

                return;
            }

            ResourceFollower::query()->create([
                'resource_type' => $type,
                'resource_id' => $id,
                'user_id' => $actor->id,
            ]);
            $following = true;
        });

        $this->audit->record(
            'phase5.collaboration.follow_toggled',
            $type,
            (string) $id,
            null,
            ['following' => $following],
        );

        return $following;
    }

    public function comments(DiscussionThread $thread): LengthAwarePaginator
    {
        return DiscussionComment::withTrashed()
            ->where('thread_id', $thread->getKey())
            ->with([
                'author:id,name',
                'parent:id,thread_id,body,deleted_at',
                'mentions:comment_id,mentioned_user_id',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(50);
    }

    /** @return list<array<string, mixed>> */
    public function followers(DiscussionThread $thread): array
    {
        return ResourceFollower::query()
            ->where('resource_type', $thread->resource_type)
            ->where('resource_id', $thread->resource_id)
            ->with('user:id,name')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(static fn (ResourceFollower $follower): array => [
                'user_id' => $follower->user_id,
                'name' => $follower->user?->name,
            ])->all();
    }

    /** @return array<string, mixed> */
    public function threadPayload(DiscussionThread $thread, User $actor): array
    {
        $type = (string) $thread->resource_type;
        $id = (int) $thread->resource_id;

        return [
            'resource' => ['type' => $type, 'id' => $id],
            'thread' => [
                'id' => $thread->getKey(),
                'resource_type' => $type,
                'resource_id' => $id,
                'created_at' => $thread->created_at?->toISOString(),
            ],
            'comments' => [],
            'followers' => $this->followers($thread),
            'canComment' => $this->authorization->canComment($actor, $type, $id),
            'canMention' => $this->authorization->allows($actor, 'collaboration.mention')
                && $this->authorization->canComment($actor, $type, $id),
            'canModerate' => $this->authorization->allows($actor, 'collaboration.moderate'),
        ];
    }

    /** @return array<string, mixed> */
    public function commentPayload(DiscussionComment $comment): array
    {
        return [
            'id' => $comment->getKey(),
            'parent_id' => $comment->parent_id,
            'author_id' => $comment->author_id,
            'author_name' => $comment->author?->name,
            'body' => $comment->trashed() ? '' : $comment->body,
            'deleted' => $comment->trashed(),
            'edited_at' => $comment->edited_at?->toISOString(),
            'created_at' => $comment->created_at?->toISOString(),
            'mention_user_ids' => $comment->relationLoaded('mentions')
                ? $comment->mentions->map(static fn (CommentMention $mention): int => (int) $mention->mentioned_user_id)->all()
                : [],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function timeline(string $type, int $id, User $actor): array
    {
        if (! in_array($type, $this->commentableResources(), true)) {
            abort(404, __('phase5.not_found'));
        }

        $this->authorization->assertCanViewResource($actor, $type, $id);

        $events = [];

        foreach (DB::table('domain_transitions')
            ->where('resource', $type)->where('owner_id', $id)
            ->orderByDesc('created_at')->orderByDesc('id')->limit(20)
            ->get(['id', 'from_status', 'to_status', 'actor_id', 'created_at']) as $transition) {
            $events[] = [
                'id' => 'domain-'.$transition->id,
                'source' => 'status',
                'action' => 'status_changed',
                'from' => $transition->from_status,
                'to' => $transition->to_status,
                'actor_id' => $transition->actor_id,
                'created_at' => $transition->created_at,
            ];
        }

        if ($type === 'workflow_instance') {
            foreach (DB::table('workflow_transitions')
                ->where('instance_id', $id)
                ->orderByDesc('created_at')->orderByDesc('id')->limit(20)
                ->get(['id', 'from_status', 'to_status', 'action', 'actor_id', 'created_at']) as $transition) {
                $events[] = [
                    'id' => 'workflow-'.$transition->id,
                    'source' => 'workflow',
                    'action' => $transition->action,
                    'from' => $transition->from_status,
                    'to' => $transition->to_status,
                    'actor_id' => $transition->actor_id,
                    'created_at' => $transition->created_at,
                ];
            }
        }

        foreach (DB::table('audit_logs')
            ->where('subject_type', $type)->where('subject_id', (string) $id)
            ->orderByDesc('created_at')->orderByDesc('id')->limit(20)
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

        $threadId = DiscussionThread::query()
            ->where('resource_type', $type)->where('resource_id', $id)->value('id');
        if ($threadId !== null) {
            foreach (DB::table('discussion_comments')
                ->where('thread_id', $threadId)
                ->orderByDesc('created_at')->orderByDesc('id')->limit(20)
                ->get(['id', 'author_id', 'edited_at', 'deleted_at', 'created_at']) as $comment) {
                $events[] = [
                    'id' => 'comment-'.$comment->id,
                    'source' => 'discussion',
                    'action' => $comment->deleted_at !== null
                        ? 'comment_deleted'
                        : ($comment->edited_at !== null ? 'comment_edited' : 'comment_created'),
                    'from' => null,
                    'to' => null,
                    'actor_id' => $comment->author_id,
                    'created_at' => $comment->created_at,
                ];
            }
        }

        if ($type === 'task') {
            foreach (DB::table('task_assignment_history')
                ->where('task_id', $id)
                ->orderByDesc('created_at')->orderByDesc('id')->limit(20)
                ->get(['id', 'from_user_id', 'to_user_id', 'actor_id', 'created_at']) as $history) {
                $events[] = [
                    'id' => 'assignment-'.$history->id,
                    'source' => 'assignment',
                    'action' => 'assigned',
                    'from' => $history->from_user_id,
                    'to' => $history->to_user_id,
                    'actor_id' => $history->actor_id,
                    'created_at' => $history->created_at,
                ];
            }
        }

        usort($events, static fn (array $left, array $right): int => strcmp(
            (string) $right['created_at'],
            (string) $left['created_at'],
        ) ?: strcmp($left['id'], $right['id']));

        return array_slice($events, 0, 50);
    }

    private function assertCanModifyComment(DiscussionComment $comment, User $actor, bool $forDelete): void
    {
        $thread = $comment->thread;
        if ($thread === null) {
            abort(404, __('phase5.not_found'));
        }

        $type = (string) $thread->resource_type;
        $this->authorization->assertCanViewResource($actor, $type, (int) $thread->resource_id);

        if (! $this->authorization->allows($actor, 'collaboration.comment')) {
            abort(403, __('phase5.no_permission'));
        }

        $isAuthor = (int) $comment->author_id === $actor->id;
        $isModerator = $this->authorization->allows($actor, 'collaboration.moderate');

        if (! $isAuthor && ! ($forDelete && $isModerator)) {
            abort(403, __('phase5.no_permission'));
        }

        $window = (int) config('phase5.collaboration.edit_window_minutes', 15);
        if ($isAuthor && $comment->created_at !== null && $comment->created_at->diffInMinutes(now()) > $window) {
            abort(403, __('phase5.no_permission'));
        }
    }

    /** @return list<string> */
    private function commentableResources(): array
    {
        return array_values((array) config('phase5.collaboration.commentable_resources', []));
    }
}
