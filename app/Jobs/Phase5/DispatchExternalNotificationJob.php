<?php

declare(strict_types=1);

namespace App\Jobs\Phase5;

use App\Models\User;
use App\Services\Phase5\ExternalNotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[Tries(2)]
#[Backoff([5, 30])]
final class DispatchExternalNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 20;

    /** @param array<string, mixed> $message */
    public function __construct(
        public readonly int $userId,
        public readonly array $message,
        public readonly ?string $dedupeKey = null,
    ) {}

    public function handle(ExternalNotificationDispatcher $dispatcher): void
    {
        $user = User::query()->find($this->userId);
        if ($user === null) {
            return;
        }

        $dispatcher->dispatch($user, [
            'category' => (string) ($this->message['category'] ?? 'system'),
            'priority' => (string) ($this->message['priority'] ?? 'normal'),
            'title_key' => (string) ($this->message['title_key'] ?? ''),
            'preview_key' => (string) ($this->message['preview_key'] ?? ''),
        ], $this->dedupeKey);
    }
}
