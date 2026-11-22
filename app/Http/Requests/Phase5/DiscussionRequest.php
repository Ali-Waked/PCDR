<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Phase5\DiscussionComment;
use Illuminate\Validation\Validator;

abstract class DiscussionRequest extends Phase5Request
{
    protected function authorizeView(): bool
    {
        $this->assertRouteResourceIsValid();

        return $this->user() !== null && $this->allows('collaboration.view');
    }

    protected function authorizeComment(): bool
    {
        $this->assertRouteResourceIsValid();

        return $this->user() !== null && $this->allows('collaboration.comment');
    }

    /** @return list<\Closure> */
    protected function resourceViewChecks(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty()) {
                    $this->assertResourceViewable();
                }
            },
        ];
    }

    /** @return list<\Closure> */
    protected function commentViewChecks(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $comment = $this->routeComment();
                $user = $this->user();
                if ($comment === null || $user === null) {
                    abort(404, __('phase5.not_found'));
                }

                $thread = $comment->thread;
                if ($thread === null) {
                    abort(404, __('phase5.not_found'));
                }

                $this->phase5Auth()->assertCanViewResource(
                    $user,
                    (string) $thread->resource_type,
                    (int) $thread->resource_id,
                );
            },
        ];
    }

    /** @return array{type: string, id: int} */
    public function resource(): array
    {
        $this->assertRouteResourceIsValid();

        return [
            'type' => (string) $this->resourceType(),
            'id' => $this->resourceId(),
        ];
    }

    protected function routeComment(): ?DiscussionComment
    {
        $comment = $this->route('comment');

        return $comment instanceof DiscussionComment ? $comment : null;
    }

    private function assertRouteResourceIsValid(): void
    {
        if ($this->resourceType() === null || $this->resourceId() <= 0) {
            abort(404, __('phase5.not_found'));
        }
    }
}
