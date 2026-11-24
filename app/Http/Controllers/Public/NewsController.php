<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Article;
use App\Services\ContentQuery;
use App\Support\RichText;
use Inertia\Inertia;
use Inertia\Response;

final class NewsController extends PublicController
{
    public function index(string $locale, ContentQuery $content): Response
    {
        $filters = [
            'category' => request()->string('category')->toString(),
            'search' => request()->string('q')->toString(),
        ];
        $articles = $content->publishedArticles($filters, (int) config('website.pagination', 12));

        return Inertia::render('Public/News/Index', [
            'articles' => $articles->through(fn ($article) => [
                'id' => $article->id,
                'title' => $article->t('title', $locale),
                'excerpt' => $article->t('excerpt', $locale),
                'publishedAt' => $article->published_at?->toDateString(),
                'category' => $article->category?->t('name', $locale),
                'image' => $article->cover?->url(),
                'url' => $article->route($locale),
            ])->items(),
            'pagination' => [
                'currentPage' => $articles->currentPage(),
                'lastPage' => $articles->lastPage(),
                'total' => $articles->total(),
                'links' => $articles->getUrlRange(
                    max(1, $articles->currentPage() - 2),
                    min($articles->lastPage(), $articles->currentPage() + 2),
                ),
            ],
            'categories' => $content->categories()->map(fn ($category) => [
                'slug' => $category->slug,
                'name' => $category->t('name', $locale),
            ])->all(),
            'filters' => $filters,
            'featured' => $content->featuredArticles(2)->map(fn ($article) => [
                'title' => $article->t('title', $locale),
                'excerpt' => $article->t('excerpt', $locale),
                'url' => $article->route($locale),
                'image' => $article->cover?->url(),
            ])->all(),
            'meta' => $this->seo($locale, __('website.navigation.news', [], $locale), path: "{$locale}/news"),
        ]);
    }

    public function show(string $locale, string $slug, ContentQuery $content): Response
    {
        $article = $content->findPublished(Article::class, $slug);
        abort_if($article === null, 404);

        /** @var Article $article */
        $article->load('tags');

        $related = Article::query()->published()
            ->where('id', '!=', $article->id)
            ->when($article->strategic_pillar_id, fn ($q) => $q->where('strategic_pillar_id', $article->strategic_pillar_id))
            ->with('translations')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(fn ($item) => [
                'title' => $item->t('title', $locale),
                'url' => $item->route($locale),
                'publishedAt' => $item->published_at?->toDateString(),
            ])->all();

        return Inertia::render('Public/News/Show', [
            'article' => [
                'title' => $article->t('title', $locale),
                'excerpt' => $article->t('excerpt', $locale),
                'bodyHtml' => RichText::render($article->t('body', $locale)),
                'publishedAt' => $article->published_at?->toDateString(),
                'category' => $article->category?->t('name', $locale),
                'author' => $article->author?->name,
                'image' => $article->cover?->url(),
                'imageAlt' => $article->cover?->t('alt_text', $locale),
                'tags' => $article->tags->map(fn ($tag) => $tag->t('name', $locale))->all(),
            ],
            'related' => $related,
            'shareUrl' => url("/{$locale}/news/{$slug}"),
            'meta' => $this->seo(
                $locale,
                (string) $article->t('title', $locale),
                $article->t('excerpt', $locale),
                "{$locale}/news/{$slug}",
                $article->cover,
                ['robots' => 'index,follow,max-snippet:-1'],
            ),
        ]);
    }
}
