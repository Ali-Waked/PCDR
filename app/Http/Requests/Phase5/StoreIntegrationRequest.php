<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class StoreIntegrationRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('integrations.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => [
                'required', 'string', 'max:96', 'regex:/^[a-z0-9][a-z0-9_.-]*$/i',
                Rule::unique('integration_registries', 'key'),
            ],
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
        $input = $this->safe()->only(['key', 'name', 'provider', 'category', 'metadata']);

        return $input;
    }
}
