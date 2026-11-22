<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class UpdateIntegrationRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('integrations.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:96'],
            'category' => ['required', Rule::in((array) config('phase5.integrations.categories', []))],
            'metadata' => ['sometimes', 'array', 'max:20'],
        ];
    }

    /** @return array<string, mixed> */
    public function integrationInput(): array
    {
        /** @var array<string, mixed> $input */
        $input = $this->safe()->only(['name', 'provider', 'category', 'metadata']);

        return $input;
    }
}
