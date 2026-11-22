<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

final class DiscussionIndexRequest extends DiscussionRequest
{
    public function authorize(): bool
    {
        return $this->authorizeView();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return $this->resourceViewChecks();
    }
}
