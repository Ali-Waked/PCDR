<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Services\Phase5\Phase5Authorization;
use Illuminate\Foundation\Http\FormRequest;

final class AssignDocumentReviewRequest extends FormRequest
{
    public function authorize(Phase5Authorization $authorization): bool
    {
        $user = $this->user();

        return $user !== null && $authorization->allows($user, 'documents.review');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reviewer_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /** @return array<string, mixed> */
    public function validatedData(): array
    {
        return $this->safe()->only(['reviewer_id']);
    }
}
