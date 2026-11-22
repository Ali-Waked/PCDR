<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

final class ReconcileDistributionRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->allows('distributions.reconcile');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'verified_quantity' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'reconciliation_notes' => ['nullable', 'string', 'max:2000'],
            'evidence_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    /** @return array<string, mixed> */
    public function reconciliationInput(): array
    {
        return $this->safe()->only(['verified_quantity', 'reconciliation_notes', 'evidence_media_id']);
    }
}
