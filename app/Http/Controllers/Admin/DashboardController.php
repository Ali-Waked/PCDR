<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\EmailTemplate;
use App\Models\Media;
use App\Models\Page;
use App\Models\PartnershipInquiry;
use App\Models\Permission;
use App\Models\Phase5\Task;
use App\Models\Phase5\WorkflowInstance;
use App\Models\Role;
use App\Models\User;
use App\Models\VolunteerInquiry;
use App\Services\OperationalDashboardService;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(private readonly OperationalDashboardService $operationalDashboard) {}

    public function __invoke(): Response
    {
        $latestEvent = AuditLog::query()->latest('created_at')->first();
        $operationalRecordCount = collect($this->operationalDashboard->summary()['modules'])
            ->sum('records');

        return Inertia::render('Dashboard', [
            'statistics' => [
                'users' => User::count(),
                'active_users' => User::where('status', User::STATUS_ACTIVE)->count(),
                'roles' => Role::count(),
                'permissions' => Permission::where('is_retired', false)->count(),
                'recent_events' => AuditLog::count(),
                'content_items' => Article::count() + Page::count(),
                'inquiries' => ContactMessage::count() + VolunteerInquiry::count() + PartnershipInquiry::count(),
                'email_templates' => EmailTemplate::count(),
                'media_items' => Media::count(),
                'operational_records' => $operationalRecordCount,
                'phase5_tasks' => Task::count(),
                'phase5_workflows' => WorkflowInstance::count(),
            ],
            'latestEvent' => $latestEvent === null ? null : [
                'action' => $latestEvent->action,
                'created_at' => $latestEvent->created_at?->toIso8601String(),
            ],
        ]);
    }
}
