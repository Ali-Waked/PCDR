<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Phase5\DiscussionComment;
use App\Models\Phase5\DiscussionThread;
use App\Services\Phase5\CollaborationService;
use Illuminate\Validation\Validator;

final class StoreCommentRequest extends DiscussionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeComment();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.(int) config('phase5.collaboration.max_comment_chars', 5000)],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'min:1', 'exists:discussion_comments,id'],
            'mentions' => ['sometimes', 'array', 'max:'.(int) config('phase5.collaboration.max_mentions_per_comment', 10)],
            'mentions.*' => ['integer', 'min:1', 'exists:users,id', 'distinct'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [
            ...$this->resourceViewChecks(),
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();
                if ($user === null) {
                    return;
                }

                $resource = $this->resource();
                $parentId = $this->input('parent_id');

                if ($parentId !== null) {
                    $threadId = DiscussionThread::query()
                        ->where('resource_type', $resource['type'])
                        ->where('resource_id', $resource['id'])
                        ->value('id');

                    $parentBelongs = $threadId !== null && DiscussionComment::query()
                        ->whereKey((int) $parentId)
                        ->where('thread_id', (int) $threadId)
                        ->exists();

                    if (! $parentBelongs) {
                        $validator->errors()->add('parent_id', __('phase5.not_found'));
                    }
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $mentions = array_values(array_unique(array_map('intval', (array) $this->input('mentions', []))));
                app(CollaborationService::class)->assertMentionsAllowed(
                    $user,
                    $mentions,
                    $resource['type'],
                    $resource['id'],
                );
            },
        ];
    }

    /** @return array<string, mixed> */
    public function commentInput(): array
    {
        return $this->safe()->only(['body', 'parent_id', 'mentions']);
    }
}
