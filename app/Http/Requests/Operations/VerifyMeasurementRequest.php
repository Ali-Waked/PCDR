<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

final class VerifyMeasurementRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->allows('meal.verify');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'verified_value' => ['required', 'numeric', 'decimal:0,4'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
            'evidence_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    /** @return array<string, mixed> */
    public function verificationInput(): array
    {
        return $this->safe()->only(['verified_value', 'verification_notes', 'evidence_media_id']);
    }
}
