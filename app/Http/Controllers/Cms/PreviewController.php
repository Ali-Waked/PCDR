<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Program;
use App\Models\Project;
use App\Models\Publication;
use App\Models\StrategicPillar;
use App\Services\CmsAccess;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Model;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Server-side draft preview for authorized CMS users. Published content is
 * never required for a preview, and previews are not publicly discoverable.
 */
final class PreviewController extends Controller
{
    /** @var array<string, array{model: class-string<Model>, permission: string}> */
    private const RESOURCES = [
        'news' => ['model' => Article::class, 'permission' => 'news.view'],
        'programs' => ['model' => Program::class, 'permission' => 'programs.view'],
        'projects' => ['model' => Project::class, 'permission' => 'programs.view'],
        'pillars' => ['model' => StrategicPillar::class, 'permission' => 'pillars.view'],
        'publications' => ['model' => Publication::class, 'permission' => 'publications.view'],
    ];

    public function __invoke(string $locale, string $resource, int $content): Response
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        $definition = self::RESOURCES[$resource];
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), $definition['permission']), 403);

        /** @var class-string<Model> $class */
        $class = $definition['model'];
        $record = $class::query()->with('translations')->findOrFail($content);
        $translation = $record->translate($locale);

        return Inertia::render('Cms/Content/Preview', [
            'resource' => $resource,
            'title' => $translation?->getAttribute('title') ?: $record->t('title') ?: '#'.$record->getKey(),
            'summary' => $translation?->getAttribute('summary') ?: $record->t('summary') ?: '',
            'body' => RichText::clean($translation?->getAttribute('body') ?: $record->t('body') ?: ''),
            'status' => $record->workflow_status ?? $record->status,
            'previewLocale' => $translation?->locale ?? $locale,
        ]);
    }
}
