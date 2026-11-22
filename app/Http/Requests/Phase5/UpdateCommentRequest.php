<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

final class UpdateCommentRequest extends DiscussionRequest
{
    public function authorize(): bool
    {
        if ($this->routeComment() === null) {
            abort(404, __('phase5.not_found'));
        }

        return $this->user() !== null && $this->allows('collaboration.comment');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.(int) config('phase5.collaboration.max_comment_chars', 5000)],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return $this->commentViewChecks();
    }

    /** @return array<string, mixed> */
    public function commentInput(): array
    {
        return $this->safe()->only(['body']);
    }
}
