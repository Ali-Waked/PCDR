<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use Illuminate\Validation\Rule;

final class InventoryMovementRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->allows('inventory.'.$this->permissionSuffix());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id', 'different:warehouse_id'],
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'movement_type' => ['required', 'string', Rule::in($this->catalog()->inventoryMovementTypes())],
            'direction' => ['nullable', 'string', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:999999999.999', 'decimal:0,3'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'source_reference' => ['nullable', 'string', 'max:128'],
        ];
    }

    /** @return array<string, mixed> */
    public function movementInput(): array
    {
        return $this->safe()->only([
            'warehouse_id', 'destination_warehouse_id', 'inventory_item_id',
            'movement_type', 'direction', 'quantity', 'idempotency_key',
            'reason', 'source_reference',
        ]);
    }

    private function permissionSuffix(): string
    {
        $type = (string) $this->input('movement_type');

        return match ($type) {
            'opening', 'receipt', 'return' => 'receive',
            'transfer' => 'transfer',
            'issue', 'reservation', 'release' => 'issue',
            default => 'adjust',
        };
    }
}
