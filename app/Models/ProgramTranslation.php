<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id', 'locale', 'slug', 'title', 'summary', 'description',
        'objectives', 'target_groups', 'geographic_coverage', 'seo_title', 'seo_description',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
