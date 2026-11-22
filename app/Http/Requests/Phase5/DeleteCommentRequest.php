<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

final class DeleteCommentRequest extends DiscussionRequest
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
        return [];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return $this->commentViewChecks();
    }
}
