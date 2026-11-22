<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\ActivityIndexRequest;
use App\Models\User;
use App\Services\Phase5\Phase5ActivityService;
use Inertia\Inertia;
use Inertia\Response;

final class ActivityController extends Controller
{
    public function __construct(private readonly Phase5ActivityService $activity) {}

    public function index(ActivityIndexRequest $request): Response
    {
        return Inertia::render('Phase5/Activity/Index', [
            'activity' => $this->activity->activity($this->actor(), $request->filters()),
            'filters' => $this->activity->filters(),
        ]);
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
