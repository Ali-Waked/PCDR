<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'locale', 'slug', 'title', 'summary', 'description',
        'objectives', 'target_groups', 'geographic_coverage', 'seo_title', 'seo_description',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
