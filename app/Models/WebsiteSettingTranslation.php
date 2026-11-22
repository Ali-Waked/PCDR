<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteSettingTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['website_setting_id', 'locale', 'value'];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(WebsiteSetting::class, 'website_setting_id');
    }
}
