<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'statistics' => [
                'users' => User::count(),
                'roles' => Role::count(),
                'permissions' => Permission::where('is_retired', false)->count(),
                'recent_events' => AuditLog::count(),
            ],
        ]);
    }
}
