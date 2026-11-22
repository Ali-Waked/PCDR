<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Contracts\Phase5\ExternalNotificationAdapter;
use App\Models\Phase5\NotificationDelivery;
use App\Models\Phase5\NotificationPreference;
use App\Models\User;
use App\Support\Phase5Support;
use Throwable;

/** Routes optional external channels through configured adapters; never claims provider delivery. */
final class ExternalNotificationDispatcher
{
    public function dispatch(User $user, array $message, ?string $dedupeKey = null): void
    {
        $categories = (array) config('phase5.notification.categories', []);
        $channels = array_diff(
            (array) config('phase5.notification.channels', []),
            ['database'],
        );
        $statuses = (array) config('phase5.notification.delivery_statuses', []);
        $defaults = (array) config('phase5.notification.default_preferences', []);

        $enabled = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->get(['category', 'channel', 'enabled'])
            ->keyBy(fn (NotificationPreference $preference): string => $preference->category.'.'.$preference->channel);

        $category = (string) ($message['category'] ?? 'system');

        foreach ($channels as $channel) {
            $channel = (string) $channel;
            $preference = $enabled->get($category.'.'.$channel);
            $isEnabled = $preference?->enabled ?? (bool) ($defaults[$channel] ?? false);

            if (in_array($category, (array) config('phase5.notification.mandatory_categories', []), true)) {
                $isEnabled = true;
            }

            if (! $isEnabled || ! in_array($category, $categories, true)) {
                $this->record($user, $channel, 'disabled', 'none');

                continue;
            }

            $adapterClass = (array) config('phase5.notification.adapters', []);
            $adapterClass = $adapterClass[$channel] ?? null;

            if (! is_string($adapterClass) || ! is_subclass_of($adapterClass, ExternalNotificationAdapter::class)) {
                $this->record($user, $channel, 'disabled', 'none');

                continue;
            }

            if (! app(Phase5Authorization::class)->gateEnabled('external_channels_enabled')) {
                $this->record($user, $channel, 'disabled', 'none');

                continue;
            }

            /** @var ExternalNotificationAdapter $adapter */
            $adapter = app($adapterClass, ['channel' => $channel]);
            $provider = 'none';
            $status = 'unknown';
            $reference = null;
            $errorCode = null;

            try {
                $result = $adapter->dispatch(
                    ['user_id' => $user->id, 'locale' => $user->locale],
                    [
                        'category' => $category,
                        'priority' => (string) ($message['priority'] ?? 'normal'),
                        'title_key' => (string) ($message['title_key'] ?? ''),
                        'preview_key' => (string) ($message['preview_key'] ?? ''),
                        'dedupe_key' => $dedupeKey,
                    ],
                );
                $status = in_array($result['status'], $statuses, true) ? $result['status'] : 'unknown';
                $provider = $result['provider'];
                $reference = $result['provider_reference'] ?? null;
                $errorCode = $result['error_code'] ?? null;
            } catch (Throwable $exception) {
                $status = 'failed';
                $errorCode = Phase5Support::errorCode($exception);
            }

            $this->record($user, $channel, $status, $provider, $reference, $errorCode);
        }
    }

    public function recordForDisabledGate(User $user, string $channel): void
    {
        if ($channel !== 'database') {
            $this->record($user, $channel, 'disabled', 'none');
        }
    }

    private function record(
        User $user,
        string $channel,
        string $status,
        string $provider,
        ?string $reference = null,
        ?string $errorCode = null,
    ): void {
        $now = now();

        NotificationDelivery::query()->create([
            'user_id' => $user->id,
            'channel' => $channel,
            'provider' => $provider,
            'status' => $status,
            'provider_reference' => $reference,
            'attempts' => $status === 'disabled' ? 0 : 1,
            'error_code' => $errorCode,
            'queued_at' => $status === 'queued' ? $now : null,
            'sent_at' => in_array($status, ['sent', 'delivered'], true) ? $now : null,
            'delivered_at' => $status === 'delivered' ? $now : null,
        ]);
    }
}
