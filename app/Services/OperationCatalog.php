<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidDomainTransition;
use App\Models\OperationalRecord;
use InvalidArgumentException;

/**
 * Read-only access to the Phase 4 operational contract.
 *
 * Every controller, request, and service resolves resources through this
 * class so an undeclared table, column, status, or permission cannot be
 * reached from an HTTP request.
 */
final class OperationCatalog
{
    /** @return array<string, array<string, mixed>> */
    public function resources(): array
    {
        $resources = config('operations.resources', []);

        return is_array($resources) ? $resources : [];
    }

    /** @return array<string, mixed> */
    public function definition(string $resource): array
    {
        $definition = $this->resources()[$resource] ?? null;
        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown operational resource [{$resource}].");
        }

        return $definition;
    }

    public function has(string $resource): bool
    {
        return array_key_exists($resource, $this->resources());
    }

    public function record(string $resource): OperationalRecord
    {
        return OperationalRecord::forResource($resource);
    }

    /** @param array<string, mixed> $attributes */
    public function newRecord(string $resource, array $attributes = []): OperationalRecord
    {
        /** @var OperationalRecord $record */
        $record = $this->record($resource)->newInstance($attributes);

        return $record;
    }

    /** @return list<string> */
    public function fields(string $resource): array
    {
        return array_keys($this->definition($resource)['fields'] ?? []);
    }

    /** @return array<string, mixed> */
    public function validationRules(string $resource): array
    {
        $rules = $this->definition($resource)['fields'] ?? [];

        return is_array($rules) ? $rules : [];
    }

    /** @return list<string> */
    public function searchFields(string $resource): array
    {
        $fields = $this->definition($resource)['search'] ?? [];

        return is_array($fields) ? array_values(array_filter($fields, 'is_string')) : [];
    }

    /** @return list<string> */
    public function filterFields(string $resource): array
    {
        $fields = $this->definition($resource)['filters'] ?? [];

        return is_array($fields) ? array_values(array_filter($fields, 'is_string')) : [];
    }

    /** @return list<string> */
    public function sortFields(string $resource): array
    {
        $fields = $this->definition($resource)['sort'] ?? ['id'];

        return is_array($fields) ? array_values(array_filter($fields, 'is_string')) : ['id'];
    }

    /** @return list<string> */
    public function translationFields(string $resource): array
    {
        if (! $this->isTranslatable($resource)) {
            return [];
        }

        $fields = $this->definition($resource)['translation_fields'] ?? [];

        return is_array($fields) ? array_values(array_filter($fields, 'is_string')) : [];
    }

    /** @return list<string> */
    public function richTextFields(): array
    {
        $fields = config('operations.rich_text_fields', []);

        return is_array($fields) ? array_values(array_filter($fields, 'is_string')) : [];
    }

    public function isTranslatable(string $resource): bool
    {
        return (bool) ($this->definition($resource)['translatable'] ?? false);
    }

    public function isSensitive(string $resource): bool
    {
        $declared = (bool) ($this->definition($resource)['sensitive'] ?? false);
        $global = config('operations.sensitive_resources', []);

        return $declared || (is_array($global) && in_array($resource, $global, true));
    }

    /** @return list<string> */
    public function statuses(string $resource): array
    {
        $rule = $this->validationRules($resource)['status'] ?? null;
        if (is_array($rule)) {
            foreach ($rule as $item) {
                if (is_string($item) && str_starts_with($item, 'in:')) {
                    return array_values(array_filter(explode(',', substr($item, 3)), 'strlen'));
                }
            }
        }

        $transitions = config('operations.transitions.'.$resource, []);

        return is_array($transitions) ? array_keys($transitions) : [];
    }

    /** @return array<string, list<string>> */
    public function transitions(string $resource): array
    {
        $transitions = config('operations.transitions.'.$resource, []);

        return is_array($transitions) ? $transitions : [];
    }

    public function permission(string $resource, string $action): string
    {
        $family = (string) ($this->definition($resource)['permission'] ?? $resource);

        return $family.'.'.$action;
    }

    public function table(string $resource): string
    {
        return (string) ($this->definition($resource)['table'] ?? $resource);
    }

    public function assertTransition(string $resource, ?string $from, string $to): void
    {
        $transitions = config('operations.transitions.'.$resource, []);
        $allowed = is_array($transitions) ? ($transitions[$from] ?? []) : [];

        if (! is_array($allowed) || ! in_array($to, $allowed, true)) {
            throw new InvalidDomainTransition($resource, $from, $to);
        }

        $approvalStates = config('operations.approval_statuses.'.$resource, []);
        if (is_array($approvalStates) && in_array($to, $approvalStates, true)
            && ! config('operations.gates.institutional_approvals_enabled', false)) {
            throw new InvalidDomainTransition($resource, $from, $to);
        }
    }

    /** @return list<string> */
    public function reportCategories(): array
    {
        $categories = config('operations.report_categories', []);

        return is_array($categories) ? array_values(array_filter($categories, 'is_string')) : [];
    }

    /** @return list<string> */
    public function inventoryMovementTypes(): array
    {
        $types = config('operations.inventory_movement_types', []);

        return is_array($types) ? array_values(array_filter($types, 'is_string')) : [];
    }

    public function requiresEvidence(string $resource, string $status): bool
    {
        $states = config('operations.evidence_required_statuses.'.$resource, []);

        return is_array($states) && in_array($status, $states, true);
    }

    public function gate(string $key): bool
    {
        return (bool) config('operations.gates.'.$key, false);
    }
}
