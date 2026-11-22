<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingTranslation extends Model
{
    protected $guarded = [];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }
}
