<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\NotificationIndexRequest;
use App\Http\Requests\Phase5\UpdateNotificationPreferencesRequest;
use App\Models\User;
use App\Services\Phase5\NotificationCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController extends Controller
{
    public function __construct(private readonly NotificationCenterService $notifications) {}

    public function index(NotificationIndexRequest $request): Response
    {
        $user = $this->actor();

        return Inertia::render('Phase5/Notifications/Index', [
            'notifications' => $this->notifications->inbox($user, $request->filters()),
            'unreadCount' => $this->notifications->unreadCount($user),
            'categories' => array_values((array) config('phase5.notification.categories', [])),
            'priorities' => array_values((array) config('phase5.notification.priorities', [])),
        ]);
    }

    public function deliveries(NotificationIndexRequest $request): JsonResponse
    {
        return response()->json($this->notifications->deliveries($this->actor(), $request->filters()));
    }

    public function preferences(): Response
    {
        $user = $this->actor();

        return Inertia::render('Phase5/Notifications/Preferences', [
            'preferences' => $this->notifications->preferences($user),
            'categories' => array_values((array) config('phase5.notification.categories', [])),
            'channels' => array_values((array) config('phase5.notification.channels', [])),
            'digestOptions' => array_values((array) config('phase5.notification.digest_options', [])),
            'mandatoryCategories' => array_values((array) config('phase5.notification.mandatory_categories', [])),
        ]);
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $this->notifications->updatePreferences($this->actor(), $request->preferenceInput());

        return back()->with('success', __('phase5.notification_preferences_saved'));
    }

    public function markAllRead(): RedirectResponse
    {
        $this->notifications->markAllRead($this->actor());

        return back()->with('success', __('phase5.notifications_marked_read'));
    }

    public function markRead(Request $request): RedirectResponse
    {
        $notification = (string) $request->route('notification');

        if (! $this->notifications->markRead($this->actor(), $notification)) {
            return back()->with('error', __('phase5.not_found'));
        }

        return back()->with('success', __('phase5.notification_marked_read'));
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
