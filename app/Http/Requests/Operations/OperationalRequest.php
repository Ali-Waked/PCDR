<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use App\Services\CmsAccess;
use App\Services\OperationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class OperationalRequest extends FormRequest
{
    protected function catalog(): OperationCatalog
    {
        return app(OperationCatalog::class);
    }

    protected function allows(string $permission): bool
    {
        $user = $this->user();

        return $user !== null && app(CmsAccess::class)->allows($user, $permission);
    }

    protected function routeResource(): ?string
    {
        $resource = $this->route('resource');
        if (is_string($resource) && $this->catalog()->has($resource)) {
            return $resource;
        }

        // Generated resource routes carry the resource in their name rather
        // than as a path parameter, so authorization must resolve it there.
        $routeName = $this->route()?->getName();
        if (is_string($routeName) && str_starts_with($routeName, 'operations.')) {
            $candidate = explode('.', $routeName)[1] ?? null;
            if (is_string($candidate) && $this->catalog()->has($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function trimStrings(): void
    {
        $clean = [];

        foreach ($this->all() as $key => $value) {
            $clean[$key] = is_string($value) ? trim($value) : $value;
        }

        $this->replace($clean);
    }

    protected function translationRules(string $resource, bool $required): array
    {
        if (! $this->catalog()->isTranslatable($resource)) {
            return [];
        }

        $hasFields = $this->catalog()->translationFields($resource) !== [];
        $rules = [
            'translations' => $required && $hasFields
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array', 'min:1'],
        ];

        foreach (config('operations.locales', ['ar', 'en']) as $locale) {
            $rules["translations.{$locale}"] = ['sometimes', 'array'];

            foreach ($this->catalog()->translationFields($resource) as $field) {
                $base = in_array($field, $this->catalog()->richTextFields(), true)
                    ? ['sometimes', 'nullable', 'string', 'max:20000']
                    : ['sometimes', 'nullable', 'string', 'max:255'];
                $rules["translations.{$locale}.{$field}"] = $base;
            }
        }

        return $rules;
    }

    protected function uniqueRules(string $resource): array
    {
        $rules = [];
        $table = $this->catalog()->table($resource);

        foreach (['code', 'reference', 'internal_reference', 'sku'] as $field) {
            if (! array_key_exists($field, $this->catalog()->validationRules($resource))) {
                continue;
            }

            $rule = Rule::unique($table, $field);
            $record = $this->route('record');
            if (is_numeric($record)) {
                $rule->ignore((int) $record);
            } elseif (is_object($record) && method_exists($record, 'getKey')) {
                $rule->ignore($record->getKey());
            }
            $rules[$field] = [$rule];
        }

        return $rules;
    }

    protected function requestAction(): string
    {
        return match (strtoupper($this->method())) {
            'GET', 'HEAD' => 'view',
            'POST' => str_ends_with($this->getPathInfo(), '/status') ? 'transition' : 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'update',
            default => 'view',
        };
    }

    protected function requiresCmsPermission(?string $resource = null): bool
    {
        $resource ??= $this->routeResource();
        if ($resource === null) {
            return false;
        }

        return $this->allows($this->catalog()->permission($resource, $this->requestAction()));
    }

    /** @return list<\Closure> */
    protected function gatedStatusRules(): array
    {
        return [
            function (Validator $validator): void {
                $resource = $this->routeResource();
                if ($resource === null || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $approvalStates = config('operations.approval_statuses.'.$resource, []);
                if (! is_array($approvalStates) || config('operations.gates.institutional_approvals_enabled', false)) {
                    return;
                }

                if (in_array((string) $this->input('status'), $approvalStates, true)) {
                    $validator->errors()->add('status', 'Institutional approval statuses are unavailable.');
                }
            },
        ];
    }

    protected function safeLocale(): string
    {
        $locale = $this->route('locale');

        return is_string($locale) && in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
    }

    protected function ulidPrefix(string $name): string
    {
        return Str::upper(Str::limit(Str::slug($name, ''), 4, ''));
    }
}
