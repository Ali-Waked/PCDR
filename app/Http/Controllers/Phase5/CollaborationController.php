<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\DeleteCommentRequest;
use App\Http\Requests\Phase5\DiscussionIndexRequest;
use App\Http\Requests\Phase5\DiscussionTimelineRequest;
use App\Http\Requests\Phase5\StoreCommentRequest;
use App\Http\Requests\Phase5\ToggleFollowRequest;
use App\Http\Requests\Phase5\UpdateCommentRequest;
use App\Models\Phase5\DiscussionComment;
use App\Models\User;
use App\Services\Phase5\CollaborationService;
use App\Services\Phase5\Phase5Authorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class CollaborationController extends Controller
{
    public function __construct(
        private readonly CollaborationService $collaboration,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function index(
        string $locale,
        string $resourceType,
        int $resourceId,
        DiscussionIndexRequest $request,
    ): Response {
        $resource = $request->resource();
        $actor = $this->actor();
        $thread = $this->collaboration->threadFor($resource['type'], $resource['id'], $actor);

        $comments = $thread === null
            ? []
            : $this->collaboration->comments($thread)
                ->through(fn (DiscussionComment $comment): array => $this->collaboration->commentPayload($comment));

        return Inertia::render('Phase5/Collaboration/Index', [
            'resource' => $resource,
            'thread' => $thread === null ? null : $this->collaboration->threadPayload($thread, $actor),
            'comments' => $comments,
            'followers' => $thread === null ? [] : $this->collaboration->followers($thread),
            'canComment' => $this->authorization->canComment($actor, $resource['type'], $resource['id']),
            'canMention' => $this->authorization->allows($actor, 'collaboration.mention')
                && $this->authorization->canComment($actor, $resource['type'], $resource['id']),
            'canModerate' => $this->authorization->allows($actor, 'collaboration.moderate'),
        ]);
    }

    public function timeline(
        string $locale,
        string $resourceType,
        int $resourceId,
        DiscussionTimelineRequest $request,
    ): JsonResponse {
        $resource = $request->resource();

        return response()->json([
            'timeline' => $this->collaboration->timeline(
                $resource['type'],
                $resource['id'],
                $this->actor(),
            ),
        ]);
    }

    public function storeComment(
        string $locale,
        string $resourceType,
        int $resourceId,
        StoreCommentRequest $request,
    ): RedirectResponse {
        $resource = $request->resource();

        try {
            $thread = $this->collaboration->threadFor($resource['type'], $resource['id'], $this->actor(), true);
            if ($thread === null) {
                abort(404, __('phase5.not_found'));
            }
            $this->collaboration->storeComment($thread, $request->commentInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.comment_added'));
    }

    public function toggleFollow(
        string $locale,
        string $resourceType,
        int $resourceId,
        ToggleFollowRequest $request,
    ): RedirectResponse {
        $resource = $request->resource();
        $following = $this->collaboration->toggleFollow($resource['type'], $resource['id'], $this->actor());

        return back()->with('success', $following ? __('phase5.followed') : __('phase5.unfollowed'));
    }

    public function updateComment(
        string $locale,
        DiscussionComment $comment,
        UpdateCommentRequest $request,
    ): RedirectResponse {
        try {
            $this->collaboration->updateComment($comment, $request->commentInput(), $this->actor());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.updated'));
    }

    public function destroyComment(
        string $locale,
        DiscussionComment $comment,
        DeleteCommentRequest $request,
    ): RedirectResponse {
        $this->collaboration->deleteComment($comment, $this->actor());

        return back()->with('success', __('phase5.updated'));
    }

    private function actor(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403, __('phase5.no_permission'));
        }

        return $user;
    }
}
