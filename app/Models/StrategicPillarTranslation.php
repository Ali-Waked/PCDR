<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategicPillarTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategic_pillar_id', 'locale', 'slug', 'title', 'short_title',
        'description', 'objectives', 'target_groups', 'seo_title', 'seo_description',
    ];

    public function pillar(): BelongsTo
    {
        return $this->belongsTo(StrategicPillar::class, 'strategic_pillar_id');
    }
}
