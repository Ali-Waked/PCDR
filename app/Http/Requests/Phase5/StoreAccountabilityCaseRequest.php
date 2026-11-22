<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Services\Phase5\Phase5Authorization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAccountabilityCaseRequest extends FormRequest
{
    public function authorize(Phase5Authorization $authorization): bool
    {
        $user = $this->user();

        return $user !== null && $authorization->allows($user, 'accountability.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var array<string, mixed> $enabled */
        $enabled = (array) config('phase5.accountability.enabled_categories', []);
        /** @var array<string, mixed> $disabled */
        $disabled = (array) config('phase5.accountability.disabled_categories', []);

        return [
            // Disabled categories pass validation so the service can raise the
            // OQ-29 governance exception instead of a generic validation error.
            'category' => ['required', 'string', 'max:64', Rule::in([...array_keys($enabled), ...array_keys($disabled)])],
            'channel' => ['required', 'string', Rule::in((array) config('phase5.accountability.channels', []))],
            'summary' => [
                'required',
                'string',
                'max:'.(int) config('phase5.accountability.max_summary_chars', 4000),
            ],
            'preferred_contact_method' => ['nullable', 'string', 'max:32', 'regex:/^[a-z_]+$/'],
            'language_preference' => ['nullable', 'string', Rule::in(['ar', 'en'])],
        ];
    }

    /** @return array<string, mixed> */
    public function validatedData(): array
    {
        return $this->safe()->only([
            'category',
            'channel',
            'summary',
            'preferred_contact_method',
            'language_preference',
        ]);
    }
}
