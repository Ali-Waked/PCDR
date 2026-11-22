<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Exceptions\Phase5GovernanceException;
use App\Jobs\Phase5\DispatchExternalNotificationJob;
use App\Models\Phase5\NotificationDelivery;
use App\Models\Phase5\NotificationPreference;
use App\Models\User;
use App\Notifications\Phase5\InAppNotification;
use App\Services\AuditService;
use App\Support\Phase5Support;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

final class NotificationCenterService
{
    /** @var array<string, string> */
    private const ROUTES = [
        'task' => 'phase5.tasks.show',
        'workflow_instance' => 'phase5.workflows.instances.show',
        'workflow_step' => 'phase5.workflows.instances.show',
        'document_review' => 'phase5.documents.show',
        'accountability_case' => 'phase5.accountability.show',
        'referral' => 'phase5.referrals.show',
    ];

    /** @var array<string, string> */
    private const ROUTE_PARAMETERS = [
        'task' => 'task',
        'workflow_instance' => 'instance',
        'workflow_step' => 'instance',
        'document_review' => 'review',
        'accountability_case' => 'case',
        'referral' => 'referral',
    ];

    public function __construct(
        private readonly Phase5Authorization $authorization,
        private readonly ExternalNotificationDispatcher $dispatcher,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  list<int>  $userIds
     * @param  array<string, mixed>  $message
     */
    public function notifyUsers(array $userIds, array $message, ?string $dedupeKey = null): int
    {
        $ids = Phase5Support::uniqueStrings(array_map('strval', $userIds));
        if (count($ids) > 50) {
            $ids = array_slice($ids, 0, 50);
        }

        $users = User::query()->whereIn('id', $ids)->orderBy('id')->get();
        $notified = 0;
        $resourceType = isset($message['resource_type']) ? (string) $message['resource_type'] : null;
        $resourceId = isset($message['resource_id']) ? (int) $message['resource_id'] : null;

        foreach ($users as $user) {
            if ($resourceType !== null && $resourceId !== null
                && ! $this->authorization->canViewResource($user, $resourceType, $resourceId)) {
                continue;
            }

            $key = $dedupeKey ?? hash('sha256', implode('|', [
                $message['category'] ?? '',
                $message['title_key'] ?? '',
                $message['resource_type'] ?? '',
                (string) ($message['resource_id'] ?? ''),
                (string) $user->id,
            ]));

            if ($this->notifyUser($user, $message, $key)) {
                $notified++;
            }
        }

        return $notified;
    }

    /** @param array<string, mixed> $message */
    public function notifyUser(User $user, array $message, ?string $dedupeKey = null): bool
    {
        $category = (string) ($message['category'] ?? 'system');
        $preferences = $this->preferences($user);

        if (! isset($preferences[$category]['database']['enabled'])
            || $preferences[$category]['database']['enabled'] !== true) {
            return false;
        }

        $resourceType = isset($message['resource_type']) ? (string) $message['resource_type'] : null;
        $resourceId = isset($message['resource_id']) && $message['resource_id'] !== null
            ? (int) $message['resource_id']
            : null;

        $notification = new InAppNotification(
            $category,
            (string) ($message['priority'] ?? 'normal'),
            (string) ($message['title_key'] ?? ''),
            (string) ($message['preview_key'] ?? ''),
            $resourceType,
            $resourceId,
            $this->urlFor($resourceType, $resourceId),
            $dedupeKey,
        );

        try {
            return DB::transaction(function () use ($user, $notification, $message, $dedupeKey): bool {
                if (DB::table('notifications')
                    ->where('type', $notification->databaseType($user))
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->id)
                    ->exists()) {
                    return false;
                }

                $user->notify($notification);
                $now = now();

                DispatchExternalNotificationJob::dispatch($user->id, [
                    'category' => (string) ($message['category'] ?? 'system'),
                    'priority' => (string) ($message['priority'] ?? 'normal'),
                    'title_key' => (string) ($message['title_key'] ?? ''),
                    'preview_key' => (string) ($message['preview_key'] ?? ''),
                ], $dedupeKey)->afterCommit();

                NotificationDelivery::query()->create([
                    'user_id' => $user->id,
                    'channel' => 'database',
                    'provider' => 'local',
                    'status' => 'sent',
                    'provider_reference' => null,
                    'attempts' => 1,
                    'error_code' => null,
                    'queued_at' => $now,
                    'sent_at' => $now,
                    'delivered_at' => null,
                ]);

                return true;
            });
        } catch (Phase5GovernanceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            NotificationDelivery::query()->create([
                'user_id' => $user->id,
                'channel' => 'database',
                'provider' => 'local',
                'status' => 'failed',
                'attempts' => 1,
                'error_code' => Phase5Support::errorCode($exception),
            ]);

            return false;
        }
    }

    /** @param array<string, mixed> $filters @return LengthAwarePaginator<int, mixed> */
    public function inbox(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id);

        if (! empty($filters['category'])) {
            $query->where('data->category', (string) $filters['category']);
        }

        if (! empty($filters['priority'])) {
            $query->where('data->priority', (string) $filters['priority']);
        }

        if (! empty($filters['unread'])) {
            $query->whereNull('read_at');
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', (string) $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', (string) $filters['to']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        $paginator = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($filters['page'] ?? 1)));

        $paginator->setCollection($paginator->getCollection()->map(
            fn (object $row): array => $this->presentNotification($row),
        ));

        return $paginator;
    }

    public function unreadCount(User $user): int
    {
        return (int) DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, string $id): bool
    {
        $updated = DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->update(['read_at' => now()]);

        return $updated > 0;
    }

    public function markAllRead(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** @param array<string, mixed> $filters @return LengthAwarePaginator<int, mixed> */
    public function deliveries(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = NotificationDelivery::query()->where('user_id', $user->id);

        if (! empty($filters['channel'])) {
            $query->where('channel', (string) $filters['channel']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        $paginator = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($filters['page'] ?? 1)));

        $paginator->setCollection($paginator->getCollection()->map(
            fn (NotificationDelivery $delivery): array => [
                'id' => $delivery->id,
                'channel' => $delivery->channel,
                'provider' => $delivery->provider,
                'status' => $delivery->status,
                'attempts' => $delivery->attempts,
                'error_code' => $delivery->error_code,
                'created_at' => $delivery->created_at?->toIso8601String(),
                'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            ],
        ));

        return $paginator;
    }

    /** @return array<string, array<string, array<string, mixed>>> */
    public function preferences(User $user): array
    {
        $rows = NotificationPreference::query()->where('user_id', $user->id)->get();
        $mandatory = (array) config('phase5.notification.mandatory_categories', []);
        $defaults = (array) config('phase5.notification.default_preferences', []);
        $result = [];

        foreach ((array) config('phase5.notification.categories', []) as $category) {
            foreach ((array) config('phase5.notification.channels', []) as $channel) {
                $row = $rows->first(
                    fn (NotificationPreference $preference): bool => $preference->category === $category
                        && $preference->channel === $channel,
                );
                $result[$category][$channel] = [
                    'enabled' => in_array($category, $mandatory, true)
                        ? true
                        : ($row?->enabled ?? (bool) ($defaults[$channel] ?? false)),
                    'digest' => $row?->digest ?? 'instant',
                    'quiet_start' => $row?->quiet_start,
                    'quiet_end' => $row?->quiet_end,
                ];
            }
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $preferences @return array<string, array<string, array<string, mixed>>> */
    public function updatePreferences(User $user, array $preferences): array
    {
        $mandatory = (array) config('phase5.notification.mandatory_categories', []);

        DB::transaction(function () use ($user, $preferences, $mandatory): void {
            foreach ($preferences as $item) {
                $category = (string) $item['category'];
                $channel = (string) $item['channel'];
                $enabled = in_array($category, $mandatory, true) ? true : (bool) $item['enabled'];

                NotificationPreference::query()->updateOrCreate(
                    ['user_id' => $user->id, 'category' => $category, 'channel' => $channel],
                    [
                        'enabled' => $enabled,
                        'digest' => (string) $item['digest'],
                        'quiet_start' => $item['quiet_start'] ?? null,
                        'quiet_end' => $item['quiet_end'] ?? null,
                    ],
                );

                $this->audit->record(
                    'phase5.notification_preferences.updated',
                    'user',
                    (string) $user->id,
                    null,
                    ['category' => $category, 'channel' => $channel, 'enabled' => $enabled],
                );
            }
        });

        return $this->preferences($user);
    }

    /** @return array<string, mixed> */
    private function presentNotification(object $row): array
    {
        $data = Phase5Support::decode($row->data !== null ? (string) $row->data : null);
        $titleKey = (string) ($data['title_key'] ?? '');
        $previewKey = (string) ($data['preview_key'] ?? '');
        $url = (string) ($data['url'] ?? '');

        return [
            'id' => (string) $row->id,
            'category' => in_array($data['category'] ?? null, (array) config('phase5.notification.categories', []), true)
                ? (string) $data['category']
                : 'system',
            'priority' => in_array($data['priority'] ?? null, (array) config('phase5.notification.priorities', []), true)
                ? (string) $data['priority']
                : 'normal',
            'title_key' => $this->safeKey($titleKey),
            'preview_key' => $this->safeKey($previewKey),
            'resource_type' => $this->safeKey((string) ($data['resource_type'] ?? '')),
            'resource_id' => isset($data['resource_id']) ? (int) $data['resource_id'] : null,
            'url' => str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : null,
            'sensitive' => false,
            'read_at' => $row->read_at,
            'created_at' => $row->created_at,
        ];
    }

    private function safeKey(string $key): string
    {
        return preg_match('/^[a-z0-9][a-z0-9_.-]{0,119}$/i', $key) ? $key : '';
    }

    private function urlFor(?string $type, ?int $id): ?string
    {
        if ($type === null || $id === null || ! isset(self::ROUTES[$type])) {
            return null;
        }

        try {
            return route(self::ROUTES[$type], [
                'locale' => app()->getLocale(),
                self::ROUTE_PARAMETERS[$type] => $id,
            ], false);
        } catch (Throwable) {
            return null;
        }
    }
}
