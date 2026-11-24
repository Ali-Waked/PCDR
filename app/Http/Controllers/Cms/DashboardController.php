<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\ContentRevision;
use App\Models\EmailTemplate;
use App\Models\Media;
use App\Models\Publication;
use App\Models\StrategicPillar;
use App\Models\VolunteerInquiry;
use App\Services\CmsAccess;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(string $locale): Response
    {
        abort_unless(auth()->check() && app(CmsAccess::class)->allows(auth()->user(), 'pillars.view'), 403);

        return Inertia::render('Cms/Dashboard', [
            'metrics' => [
                'articles' => Article::query()->where('status', ContentStatus::Published->value)->count(),
                'publications' => Publication::query()->count(),
                'pillars' => StrategicPillar::query()->count(),
                'media' => Media::query()->count(),
                'emailTemplates' => EmailTemplate::query()->where('is_active', true)->count(),
            ],
            'inquiries' => [
                'contact' => ContactMessage::query()->where('status', 'new')->count(),
                'volunteer' => VolunteerInquiry::query()->where('status', 'new')->count(),
            ],
            'recentActivity' => ContentRevision::query()
                ->with('actor')
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->map(fn ($revision) => [
                    'id' => $revision->id,
                    'title' => $revision->title,
                    'status' => $revision->status,
                    'note' => $revision->note,
                    'actor' => $revision->actor?->name,
                    'createdAt' => $revision->created_at?->toDateTimeString(),
                ])->all(),
        ]);
    }
}
