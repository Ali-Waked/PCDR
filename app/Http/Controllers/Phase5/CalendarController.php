<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\CalendarIndexRequest;
use App\Models\User;
use App\Services\Phase5\CalendarAggregationService;
use Inertia\Inertia;
use Inertia\Response;

final class CalendarController extends Controller
{
    public function __construct(private readonly CalendarAggregationService $calendar) {}

    public function index(CalendarIndexRequest $request): Response
    {
        $calendar = $this->calendar->calendar([
            ...$request->filters(),
            'user' => $this->actor(),
        ]);

        return Inertia::render('Phase5/Calendar/Index', [
            'events' => $calendar['events'],
            'views' => $calendar['views'],
            'sources' => $calendar['sources'],
            'timezone' => $calendar['timezone'],
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
