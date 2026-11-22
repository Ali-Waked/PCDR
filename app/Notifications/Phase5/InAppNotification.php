<?php

declare(strict_types=1);

namespace App\Notifications\Phase5;

use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Safe notification payload for the shared Laravel notifications table.
 *
 * Only translation keys and identifiers are stored. Narratives, names,
 * document contents and personal data never enter the data column.
 */
final class InAppNotification extends Notification
{
    private const KEY_PATTERN = '/^[a-z0-9][a-z0-9_.-]{0,119}$/i';

    public function __construct(
        public readonly string $category,
        public readonly string $priority,
        public readonly string $titleKey,
        public readonly string $previewKey,
        public readonly ?string $resourceType = null,
        public readonly ?int $resourceId = null,
        public readonly ?string $url = null,
        public readonly ?string $dedupeKey = null,
    ) {
        if (! in_array($category, (array) config('phase5.notification.categories', []), true)) {
            throw new InvalidArgumentException('Unsupported notification category.');
        }

        if (! in_array($priority, (array) config('phase5.notification.priorities', []), true)) {
            throw new InvalidArgumentException('Unsupported notification priority.');
        }

        foreach ([$titleKey, $previewKey] as $key) {
            if (! preg_match(self::KEY_PATTERN, $key)) {
                throw new InvalidArgumentException('Notification keys must be safe translation keys.');
            }
        }

        if ($resourceType !== null && ! preg_match(self::KEY_PATTERN, $resourceType)) {
            throw new InvalidArgumentException('Unsupported resource type.');
        }

        if ($url !== null && (! str_starts_with($url, '/') || str_starts_with($url, '//') || preg_match('/[\r\n]/', $url))) {
            throw new InvalidArgumentException('Notification URL must be an internal path.');
        }
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'priority' => $this->priority,
            'title_key' => $this->titleKey,
            'preview_key' => $this->previewKey,
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'url' => $this->url,
            'sensitive' => false,
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->dedupeKey === null
            ? self::class
            : 'phase5:'.$this->dedupeKey;
    }
}
