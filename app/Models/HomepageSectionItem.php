<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageSectionItem extends Model
{
    use HasFactory;

    protected $fillable = ['homepage_section_id', 'item_type', 'item_id', 'sort_order'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(HomepageSection::class, 'homepage_section_id');
    }

    public function resolve(): ?Model
    {
        return match ($this->item_type) {
            'pillar' => StrategicPillar::query()->published()->find($this->item_id),
            'article' => Article::query()->published()->find($this->item_id),
            'publication' => Publication::query()->published()->find($this->item_id),
            'page' => Page::query()->published()->find($this->item_id),
            'project' => Project::query()->published()->find($this->item_id),
            default => null,
        };
    }
}
