<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

final class OperationIndexRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->requiresCmsPermission();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $resource = $this->routeResource();
        if ($resource === null) {
            return [];
        }

        $rules = [
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:'.implode(',', $this->catalog()->sortFields($resource))],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ];

        foreach ($this->catalog()->filterFields($resource) as $field) {
            $rules[$field] = ['nullable', 'string', 'max:128'];
        }

        return $rules;
    }

    /** @return array<string, mixed> */
    public function queryParameters(): array
    {
        return $this->safe()->except(['_token']);
    }
}
