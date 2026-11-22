<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'warehouse_id', 'destination_warehouse_id', 'inventory_item_id', 'movement_type',
        'direction', 'quantity', 'before_quantity', 'after_quantity', 'source_type', 'source_id',
        'source_reference', 'reason', 'actor_id', 'reversal_of', 'idempotency_key',
        'occurred_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'warehouse_id' => 'integer',
            'destination_warehouse_id' => 'integer',
            'inventory_item_id' => 'integer',
            'actor_id' => 'integer',
            'reversal_of' => 'integer',
            'quantity' => 'decimal:3',
            'before_quantity' => 'decimal:3',
            'after_quantity' => 'decimal:3',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): bool {
            return false;
        });

        static::deleting(function (): bool {
            return false;
        });
    }
}
