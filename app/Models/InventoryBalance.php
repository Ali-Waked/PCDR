<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBalance extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'warehouse_id', 'inventory_item_id', 'quantity', 'reserved_quantity',
        'damaged_quantity', 'expired_quantity', 'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'warehouse_id' => 'integer',
            'inventory_item_id' => 'integer',
            'quantity' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'damaged_quantity' => 'decimal:3',
            'expired_quantity' => 'decimal:3',
            'updated_at' => 'datetime',
        ];
    }
}
