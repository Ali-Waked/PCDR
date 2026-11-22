<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Program;
use App\Models\Project;
use App\Services\ContentQuery;
use Inertia\Inertia;
use Inertia\Response;

final class ProgramController extends PublicController
{
    public function index(string $locale, ContentQuery $content): Response
    {
        return Inertia::render('Public/Programs/Index', [
            'programs' => $content->publishedPrograms()->map(fn ($program) => [
                'title' => $program->t('title', $locale),
                'summary' => $program->t('summary', $locale),
                'pillar' => $program->pillar?->t('title', $locale),
                'startDate' => $program->start_date?->toDateString(),
                'endDate' => $program->end_date?->toDateString(),
                'url' => $program->route($locale),
            ])->all(),
            'projects' => $content->publishedProjects()->map(fn ($project) => [
                'title' => $project->t('title', $locale),
                'summary' => $project->t('summary', $locale),
                'status' => $project->statusLabel($locale),
                'program' => $project->program?->t('title', $locale),
                'startDate' => $project->start_date?->toDateString(),
                'endDate' => $project->end_date?->toDateString(),
                'url' => $project->route($locale),
            ])->all(),
            'meta' => $this->seo($locale, __('website.navigation.programs', [], $locale), path: "{$locale}/programs"),
        ]);
    }

    public function show(string $locale, string $slug, ContentQuery $content): Response
    {
        $program = $content->findPublished(Program::class, $slug);
        abort_if($program === null, 404);

        /** @var Program $program */
        return Inertia::render('Public/Programs/Show', [
            'program' => [
                'title' => $program->t('title', $locale),
                'summary' => $program->t('summary', $locale),
                'description' => $program->t('description', $locale),
                'objectives' => $this->lines($program->t('objectives', $locale)),
                'targetGroups' => $this->lines($program->t('target_groups', $locale)),
                'coverage' => $program->t('geographic_coverage', $locale),
                'startDate' => $program->start_date?->toDateString(),
                'endDate' => $program->end_date?->toDateString(),
                'pillar' => $program->pillar?->t('title', $locale),
                'image' => $program->featuredImage?->url(),
            ],
            'projects' => $program->projects()->published()->with('translations')->orderBy('display_order')->get()
                ->map(fn ($project) => [
                    'title' => $project->t('title', $locale),
                    'summary' => $project->t('summary', $locale),
                    'status' => $project->statusLabel($locale),
                    'url' => $project->route($locale),
                ])->all(),
            'meta' => $this->seo($locale, (string) $program->t('title', $locale), $program->t('summary', $locale), "{$locale}/programs/{$slug}", $program->featuredImage),
        ]);
    }

    public function project(string $locale, string $slug, ContentQuery $content): Response
    {
        $project = $content->findPublished(Project::class, $slug);
        abort_if($project === null, 404);

        /** @var Project $project */
        return Inertia::render('Public/Programs/Project', [
            'project' => [
                'title' => $project->t('title', $locale),
                'summary' => $project->t('summary', $locale),
                'description' => $project->t('description', $locale),
                'objectives' => $this->lines($project->t('objectives', $locale)),
                'targetGroups' => $this->lines($project->t('target_groups', $locale)),
                'coverage' => $project->t('geographic_coverage', $locale),
                'status' => $project->statusLabel($locale),
                'startDate' => $project->start_date?->toDateString(),
                'endDate' => $project->end_date?->toDateString(),
                'program' => $project->program?->t('title', $locale),
                'pillar' => $project->pillar?->t('title', $locale),
                'image' => $project->featuredImage?->url(),
            ],
            'meta' => $this->seo($locale, (string) $project->t('title', $locale), $project->t('summary', $locale), "{$locale}/projects/{$slug}", $project->featuredImage),
        ]);
    }

    /** @return list<string> */
    private function lines(?string $value): array
    {
        return $value ? array_values(array_filter(array_map('trim', preg_split('/\R+/u', $value) ?: []))) : [];
    }
}
