<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboard) {}

    public function __invoke(): Response
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $locale = (string) request()->route('locale', app()->getLocale());

        return Inertia::render('Dashboard', [
            // Retained for existing consumers; the redesigned page renders the
            // permission-filtered `overview` payload below.
            'statistics' => $this->dashboard->legacyStatistics($user),
            'latestEvent' => $this->dashboard->latestAuditEvent($user),
            'overview' => $this->dashboard->overview($user, $locale),
        ]);
    }
}
