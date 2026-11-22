<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Page;
use App\Models\Program;
use App\Models\Project;
use App\Models\Publication;
use App\Models\StrategicPillar;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * The single read boundary for public content. Every query here is limited to
 * published records; callers cannot escalate to drafts through request input.
 */
final class ContentQuery
{
    public function publishedPillars(int $limit = 11): Collection
    {
        return StrategicPillar::query()
            ->published()
            ->with('translations', 'featuredImage')
            ->orderBy('display_order')
            ->orderBy('code')
            ->limit($limit)
            ->get();
    }

    public function featuredPillars(int $limit = 4): Collection
    {
        return StrategicPillar::query()
            ->published()
            ->where('is_featured', true)
            ->with('translations')
            ->orderBy('display_order')
            ->limit($limit)
            ->get();
    }

    public function publishedArticles(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Article::query()
            ->published()
            ->with('translations', 'category.translations', 'pillar.translations', 'cover');

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['pillar'])) {
            $query->whereHas('pillar', fn (Builder $q) => $q->where('code', $filters['pillar']));
        }

        if (! empty($filters['search'])) {
            $term = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->whereHas('translations', function (Builder $q) use ($term): void {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(COALESCE(excerpt, \'\')) LIKE ?', [$term]);
            });
        }

        return $query->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function featuredArticles(int $limit = 3): Collection
    {
        return Article::query()
            ->published()
            ->with('translations', 'category.translations', 'cover')
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function publishedPrograms(int $limit = 12): Collection
    {
        return Program::query()
            ->published()
            ->with('translations', 'pillar.translations', 'featuredImage')
            ->orderBy('display_order')
            ->limit($limit)
            ->get();
    }

    public function publishedProjects(int $limit = 12): Collection
    {
        return Project::query()
            ->published()
            ->with('translations', 'program.translations', 'pillar.translations', 'featuredImage')
            ->orderBy('display_order')
            ->limit($limit)
            ->get();
    }

    public function publishedPublications(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Publication::query()
            ->published()
            ->with('translations', 'document', 'cover', 'pillar.translations');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $term = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->whereHas('translations', fn (Builder $q) => $q->whereRaw('LOWER(title) LIKE ?', [$term]));
        }

        return $query->orderByDesc('publication_date')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function featuredPublications(int $limit = 4): Collection
    {
        return Publication::query()
            ->published()
            ->with('translations', 'document', 'cover')
            ->orderByDesc('publication_date')
            ->limit($limit)
            ->get();
    }

    public function categories(): Collection
    {
        return ArticleCategory::query()->with('translations')->orderBy('display_order')->get();
    }

    public function findPublished(string $modelClass, string $slug): ?Model
    {
        /** @var class-string<Model> $modelClass */
        return $modelClass::query()
            ->published()
            ->with('translations')
            ->whereHas('translations', function (Builder $q) use ($slug): void {
                $q->where('slug', $slug)->where('locale', app()->getLocale());
            })
            ->first();
    }

    public function findPublishedPage(string $slug): ?Page
    {
        return Page::query()
            ->published()
            ->with('translations')
            ->whereHas('translations', function (Builder $q) use ($slug): void {
                $q->where('slug', $slug)->where('locale', app()->getLocale());
            })
            ->first();
    }
}
