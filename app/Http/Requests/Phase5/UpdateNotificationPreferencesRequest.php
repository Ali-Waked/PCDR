<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class UpdateNotificationPreferencesRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('notifications.preferences');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1', 'max:'.(count((array) config('phase5.notification.categories', [])) * count((array) config('phase5.notification.channels', [])))],
            'preferences.*.category' => ['required', Rule::in((array) config('phase5.notification.categories', []))],
            'preferences.*.channel' => ['required', Rule::in((array) config('phase5.notification.channels', []))],
            'preferences.*.enabled' => ['required', 'boolean'],
            'preferences.*.digest' => ['required', Rule::in((array) config('phase5.notification.digest_options', []))],
            'preferences.*.quiet_start' => ['sometimes', 'nullable', 'date_format:H:i'],
            'preferences.*.quiet_end' => ['sometimes', 'nullable', 'date_format:H:i'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function preferenceInput(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->safe()->only(['preferences']);

        $preferences = $validated['preferences'] ?? [];

        return is_array($preferences) ? array_values($preferences) : [];
    }
}
