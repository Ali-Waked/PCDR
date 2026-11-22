<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Transactional, append-only inventory ledger.
 *
 * Balances are derived only from movements created here. Corrections are new
 * reversal entries; historical movements are never edited.
 */
final class InventoryLedgerService
{
    private const IN_TYPES = ['opening', 'receipt', 'return'];

    private const OUT_TYPES = ['issue', 'transfer', 'damage', 'expiry'];

    public function __construct(private readonly AuditService $audit) {}

    /** @param array<string, mixed> $data @return array<int, InventoryMovement> */
    public function post(array $data): array
    {
        $type = (string) ($data['movement_type'] ?? '');
        $allowed = config('operations.inventory_movement_types', []);
        if (! in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported inventory movement type.');
        }

        $quantity = round((float) ($data['quantity'] ?? 0), 3);
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Movement quantity must be greater than zero.');
        }

        $direction = (string) ($data['direction'] ?? $this->defaultDirection($type));
        if (! in_array($direction, ['in', 'out'], true)) {
            throw new InvalidArgumentException('Movement direction must be in or out.');
        }

        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        $itemId = (int) ($data['inventory_item_id'] ?? 0);
        if ($warehouseId < 1 || $itemId < 1) {
            throw new InvalidArgumentException('A warehouse and inventory item are required.');
        }

        if ($type === 'transfer' && (int) ($data['destination_warehouse_id'] ?? 0) < 1) {
            throw new InvalidArgumentException('A destination warehouse is required for transfers.');
        }

        $idempotencyKey = $data['idempotency_key'] ?? null;

        return DB::transaction(function () use ($data, $type, $direction, $quantity, $warehouseId, $itemId, $idempotencyKey): array {
            if (is_string($idempotencyKey) && $idempotencyKey !== '') {
                $existing = InventoryMovement::query()->where('idempotency_key', $idempotencyKey)->get();
                if ($existing->isNotEmpty()) {
                    return $existing->all();
                }
            }

            $balance = $this->balance($warehouseId, $itemId);
            $before = (float) $balance->quantity;
            $reserved = (float) $balance->reserved_quantity;
            $after = $before;
            $newReserved = $reserved;
            $damaged = (float) $balance->damaged_quantity;
            $expired = (float) $balance->expired_quantity;

            if ($type === 'reservation') {
                if ($before - $reserved < $quantity) {
                    throw new InvalidArgumentException('Insufficient unreserved stock for reservation.');
                }
                $newReserved = $reserved + $quantity;
            } elseif ($type === 'release') {
                if ($reserved < $quantity) {
                    throw new InvalidArgumentException('Insufficient reserved stock for release.');
                }
                $newReserved = $reserved - $quantity;
            } elseif ($direction === 'out') {
                if ($before - $reserved < $quantity) {
                    throw new InvalidArgumentException('Insufficient available stock.');
                }
                $after = $before - $quantity;
                if ($type === 'damage') {
                    $damaged += $quantity;
                }
                if ($type === 'expiry') {
                    $expired += $quantity;
                }
            } else {
                $after = $before + $quantity;
            }

            $balance->forceFill([
                'quantity' => $after,
                'reserved_quantity' => $newReserved,
                'damaged_quantity' => $damaged,
                'expired_quantity' => $expired,
                'updated_at' => now(),
            ])->save();

            $movement = $this->createMovement(array_merge($data, [
                'warehouse_id' => $warehouseId,
                'inventory_item_id' => $itemId,
                'movement_type' => $type,
                'direction' => $direction,
                'quantity' => $quantity,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'idempotency_key' => $idempotencyKey,
            ]));

            $movements = [$movement];

            if ($type === 'transfer') {
                $destinationId = (int) $data['destination_warehouse_id'];
                $destinationBalance = $this->balance($destinationId, $itemId);
                $destinationBefore = (float) $destinationBalance->quantity;
                $destinationAfter = $destinationBefore + $quantity;

                $destinationBalance->forceFill([
                    'quantity' => $destinationAfter,
                    'updated_at' => now(),
                ])->save();

                $movements[] = $this->createMovement(array_merge($data, [
                    'warehouse_id' => $destinationId,
                    'destination_warehouse_id' => null,
                    'inventory_item_id' => $itemId,
                    'movement_type' => 'transfer',
                    'direction' => 'in',
                    'quantity' => $quantity,
                    'before_quantity' => $destinationBefore,
                    'after_quantity' => $destinationAfter,
                    'idempotency_key' => is_string($idempotencyKey) && $idempotencyKey !== ''
                        ? $idempotencyKey.':destination'
                        : null,
                ]));
            }

            $this->audit->record(
                'inventory.movement_posted',
                'inventory_movements',
                (string) $movement->getKey(),
                null,
                [
                    'movement_type' => $type,
                    'warehouse_id' => $warehouseId,
                    'inventory_item_id' => $itemId,
                    'quantity' => $quantity,
                ],
            );

            return $movements;
        });
    }

    public function reverse(int $movementId, ?int $actorId = null, ?string $reason = null): InventoryMovement
    {
        /** @var InventoryMovement $original */
        $original = InventoryMovement::query()->findOrFail($movementId);

        if (InventoryMovement::query()->where('reversal_of', $movementId)->exists()) {
            throw new InvalidArgumentException('This movement has already been reversed.');
        }

        $reverseType = match ($original->movement_type) {
            'reservation' => 'release',
            'release' => 'reservation',
            default => $original->direction === 'in' ? 'adjustment' : 'adjustment',
        };

        $movements = $this->post([
            'warehouse_id' => $original->warehouse_id,
            'destination_warehouse_id' => $original->destination_warehouse_id,
            'inventory_item_id' => $original->inventory_item_id,
            'movement_type' => $reverseType,
            'direction' => $original->direction === 'in' ? 'out' : 'in',
            'quantity' => $original->quantity,
            'source_type' => 'inventory_movement',
            'source_id' => $original->getKey(),
            'source_reference' => 'reversal-of-'.$original->getKey(),
            'reason' => $reason ?: 'Correction reversal',
            'actor_id' => $actorId,
            'reversal_of' => $original->getKey(),
            'idempotency_key' => 'reversal:'.$original->getKey(),
        ]);

        /** @var InventoryMovement $reversal */
        return $movements[0];
    }

    /** @return array<int, array<string, mixed>> */
    public function balances(): array
    {
        $warehouses = DB::table('warehouses')->pluck('name', 'id');
        $items = DB::table('inventory_items')->pluck('name', 'id');
        $skus = DB::table('inventory_items')->pluck('sku', 'id');
        $units = DB::table('inventory_items')->pluck('unit', 'id');

        return InventoryBalance::query()
            ->orderBy('warehouse_id')
            ->orderBy('inventory_item_id')
            ->get()
            ->map(fn (InventoryBalance $balance): array => [
                'id' => $balance->getKey(),
                'warehouse_id' => $balance->warehouse_id,
                'warehouse' => (string) ($warehouses[$balance->warehouse_id] ?? ''),
                'inventory_item_id' => $balance->inventory_item_id,
                'item' => (string) ($items[$balance->inventory_item_id] ?? ''),
                'sku' => (string) ($skus[$balance->inventory_item_id] ?? ''),
                'unit' => (string) ($units[$balance->inventory_item_id] ?? ''),
                'quantity' => (string) $balance->quantity,
                'reserved_quantity' => (string) $balance->reserved_quantity,
                'damaged_quantity' => (string) $balance->damaged_quantity,
                'expired_quantity' => (string) $balance->expired_quantity,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function movements(int $limit = 100): array
    {
        $warehouses = DB::table('warehouses')->pluck('name', 'id');
        $items = DB::table('inventory_items')->pluck('name', 'id');

        return InventoryMovement::query()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (InventoryMovement $movement): array => [
                'id' => $movement->getKey(),
                'warehouse' => (string) ($warehouses[$movement->warehouse_id] ?? ''),
                'destination' => (string) ($warehouses[$movement->destination_warehouse_id] ?? ''),
                'item' => (string) ($items[$movement->inventory_item_id] ?? ''),
                'movement_type' => $movement->movement_type,
                'direction' => $movement->direction,
                'quantity' => (string) $movement->quantity,
                'before_quantity' => (string) $movement->before_quantity,
                'after_quantity' => (string) $movement->after_quantity,
                'source_reference' => $movement->source_reference,
                'reversal_of' => $movement->reversal_of,
                'created_at' => $movement->created_at?->toISOString(),
            ])
            ->all();
    }

    public function balance(int $warehouseId, int $itemId): InventoryBalance
    {
        InventoryBalance::query()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'inventory_item_id' => $itemId],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'expired_quantity' => 0,
                'updated_at' => now(),
            ],
        );

        /** @var InventoryBalance $balance */
        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('inventory_item_id', $itemId)
            ->lockForUpdate()
            ->firstOrFail();

        return $balance;
    }

    /** @param array<string, mixed> $data */
    private function createMovement(array $data): InventoryMovement
    {
        return InventoryMovement::query()->create([
            'warehouse_id' => $data['warehouse_id'],
            'destination_warehouse_id' => $data['destination_warehouse_id'] ?? null,
            'inventory_item_id' => $data['inventory_item_id'],
            'movement_type' => $data['movement_type'],
            'direction' => $data['direction'],
            'quantity' => $data['quantity'],
            'before_quantity' => $data['before_quantity'],
            'after_quantity' => $data['after_quantity'],
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'source_reference' => $data['source_reference'] ?? null,
            'reason' => $data['reason'] ?? null,
            'actor_id' => $data['actor_id'] ?? auth()->id(),
            'reversal_of' => $data['reversal_of'] ?? null,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'created_at' => now(),
        ]);
    }

    private function defaultDirection(string $type): string
    {
        if (in_array($type, self::IN_TYPES, true)) {
            return 'in';
        }

        if (in_array($type, self::OUT_TYPES, true)) {
            return 'out';
        }

        return 'in';
    }
}
