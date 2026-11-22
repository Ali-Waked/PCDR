<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Services\Phase5\Phase5Authorization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransitionAccountabilityCaseRequest extends FormRequest
{
    public function authorize(Phase5Authorization $authorization): bool
    {
        $user = $this->user();

        return $user !== null && $authorization->allows($user, 'accountability.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'to_status' => ['required', 'string', Rule::in((array) config('phase5.accountability.statuses', []))],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, mixed> */
    public function validatedData(): array
    {
        return $this->safe()->only(['to_status', 'note']);
    }
}
