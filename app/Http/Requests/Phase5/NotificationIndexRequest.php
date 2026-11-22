<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;

final class NotificationIndexRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('notifications.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => ['sometimes', Rule::in((array) config('phase5.notification.categories', []))],
            'priority' => ['sometimes', Rule::in((array) config('phase5.notification.priorities', []))],
            'channel' => ['sometimes', Rule::in((array) config('phase5.notification.channels', []))],
            'status' => ['sometimes', Rule::in((array) config('phase5.notification.delivery_statuses', []))],
            'unread' => ['sometimes', 'boolean'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        /** @var array<string, mixed> $filters */
        $filters = $this->safe()->only([
            'category', 'priority', 'channel', 'status', 'from', 'to', 'page', 'per_page',
        ]);
        $filters['unread'] = $this->boolean('unread');

        return $filters;
    }
}
