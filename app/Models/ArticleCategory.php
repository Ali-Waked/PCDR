<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['slug', 'display_order'];

    public function translationModel(): string
    {
        return ArticleCategoryTranslation::class;
    }

    public function translationForeignKey(): string
    {
        return 'article_category_id';
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
