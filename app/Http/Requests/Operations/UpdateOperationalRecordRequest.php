<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

final class UpdateOperationalRecordRequest extends OperationalRequest
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
            return ['resource' => ['required', 'string']];
        }

        $this->trimStrings();

        $rules = [];
        foreach ($this->catalog()->validationRules($resource) as $field => $rule) {
            $rules[$field] = array_map(
                fn (mixed $item): mixed => $item === 'required' ? 'sometimes' : $item,
                $rule,
            );
        }

        return array_merge(
            $rules,
            $this->uniqueRules($resource),
            $this->translationRules($resource, false),
        );
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return $this->gatedStatusRules();
    }

    /** @return array<string, mixed> */
    public function validatedAttributes(): array
    {
        $resource = $this->routeResource();

        return $resource === null ? [] : $this->safe()->only($this->catalog()->fields($resource));
    }

    /** @return array<string, mixed> */
    public function validatedTranslations(): array
    {
        $translations = $this->safe()->only(['translations'])['translations'] ?? [];

        return is_array($translations) ? $translations : [];
    }
}
