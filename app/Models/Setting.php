<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_localized' => 'boolean',
            'is_secret_reference' => 'boolean',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SettingTranslation::class);
    }
}
