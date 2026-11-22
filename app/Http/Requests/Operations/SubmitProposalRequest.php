<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

final class SubmitProposalRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->allows('grants.submit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'evidence_media_id' => ['required', 'integer', 'exists:media,id'],
            'submitted_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, mixed> */
    public function submissionInput(): array
    {
        return $this->safe()->only(['evidence_media_id', 'submitted_at', 'reason']);
    }
}
