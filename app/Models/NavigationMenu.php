<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'label'];

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('display_order');
    }
}
