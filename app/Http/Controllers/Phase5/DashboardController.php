<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Phase5\Phase5DashboardService;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(private readonly Phase5DashboardService $dashboard) {}

    public function index(): Response
    {
        return Inertia::render('Phase5/Dashboard', $this->dashboard->dashboard($this->actor()));
    }

    private function actor(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403, __('phase5.no_permission'));
        }

        return $user;
    }
}
