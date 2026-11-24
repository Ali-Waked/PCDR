<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Publication;
use App\Services\CmsAccess;
use App\Services\ContentQuery;
use App\Support\RichText;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicationController extends PublicController
{
    public function index(string $locale, ContentQuery $content): Response
    {
        $filters = [
            'category' => request()->string('category')->toString(),
            'search' => request()->string('q')->toString(),
        ];
        $publications = $content->publishedPublications($filters, (int) config('website.pagination', 12));

        $categories = collect(['annual_report', 'strategic_plan', 'research', 'study',
            'needs_assessment', 'policy', 'institutional'])
            ->map(fn ($key) => ['key' => $key, 'label' => __("website.publication.categories.{$key}", [], $locale)])
            ->all();

        return Inertia::render('Public/Publications/Index', [
            'publications' => $publications->through(fn ($publication) => [
                'id' => $publication->id,
                'title' => $publication->t('title', $locale),
                'description' => $publication->t('description', $locale),
                'category' => $publication->categoryLabel($locale),
                'categoryKey' => $publication->category,
                'date' => $publication->publication_date?->toDateString(),
                'version' => $publication->version,
                'restricted' => $publication->is_restricted,
                'hasFile' => $publication->document !== null,
                'downloadUrl' => $this->downloadUrl($publication, $locale),
                'url' => $publication->route($locale),
            ])->items(),
            'pagination' => [
                'currentPage' => $publications->currentPage(),
                'lastPage' => $publications->lastPage(),
                'total' => $publications->total(),
                'links' => $publications->getUrlRange(
                    max(1, $publications->currentPage() - 2),
                    min($publications->lastPage(), $publications->currentPage() + 2),
                ),
            ],
            'categories' => $categories,
            'filters' => $filters,
            'meta' => $this->seo($locale, __('website.navigation.publications', [], $locale), path: "{$locale}/publications"),
        ]);
    }

    public function show(string $locale, string $slug, ContentQuery $content): Response
    {
        $publication = $content->findPublished(Publication::class, $slug);
        abort_if($publication === null, 404);

        /** @var Publication $publication */
        return Inertia::render('Public/Publications/Show', [
            'publication' => [
                'title' => $publication->t('title', $locale),
                'descriptionHtml' => RichText::render($publication->t('description', $locale)),
                'descriptionText' => RichText::plainText($publication->t('description', $locale)),
                'category' => $publication->categoryLabel($locale),
                'date' => $publication->publication_date?->toDateString(),
                'version' => $publication->version,
                'restricted' => $publication->is_restricted,
                'hasFile' => $publication->document !== null,
                'downloadUrl' => $this->downloadUrl($publication, $locale),
                'pillar' => $publication->pillar?->t('title', $locale),
            ],
            'meta' => $this->seo($locale, (string) $publication->t('title', $locale), $publication->t('description', $locale), "{$locale}/publications/{$slug}", $publication->cover),
        ]);
    }

    public function download(string $locale, int $publication): StreamedResponse
    {
        $record = Publication::query()->published()->findOrFail($publication);

        /** @var Publication $record */
        abort_unless(request()->hasValidSignature(), 403);

        if ($record->is_restricted || $record->document?->is_restricted) {
            abort_unless(auth()->check(), 403);
            abort_unless(app(CmsAccess::class)->allows(auth()->user(), 'publications.view'), 403);
        }

        $document = $record->document;
        abort_if($document === null, 404);

        return Storage::disk($document->disk)->response(
            $document->path,
            $this->safeFileName($document->original_name, $document->extension),
            ['Content-Type' => $document->mime_type, 'Cache-Control' => 'private, max-age=300'],
        );
    }

    private function downloadUrl(Publication $publication, string $locale): ?string
    {
        if ($publication->document === null) {
            return null;
        }

        return URL::temporarySignedRoute(
            'public.publications.download',
            now()->addMinutes(30),
            ['locale' => $locale, 'publication' => $publication->getKey()],
        );
    }

    private function safeFileName(string $name, string $extension): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $clean = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '', $base) ?: 'document';

        return mb_substr($clean, 0, 100).'.'.$extension;
    }
}
