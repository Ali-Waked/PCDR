<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalTranslation extends Model
{
    protected $fillable = [
        'resource', 'owner_id', 'locale', 'title', 'name', 'summary', 'description',
        'objectives', 'target_groups', 'methodology', 'findings', 'recommendations',
        'lessons_learned', 'conditions', 'deliverables', 'reporting_requirements', 'notes',
    ];

    protected function casts(): array
    {
        return ['owner_id' => 'integer'];
    }
}
