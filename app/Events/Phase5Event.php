<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Safe domain event used by Phase 5 automation and webhook fan-out.
 *
 * Payloads must contain scalars or scalar arrays only. Never place personal
 * data, secrets, document contents or complaint narratives in a payload.
 */
final class Phase5Event implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $name,
        public readonly array $payload = [],
        public readonly ?string $subjectType = null,
        public readonly ?int $subjectId = null,
        public readonly ?int $actorId = null,
    ) {}

    public function idempotencyKey(): string
    {
        $material = json_encode([
            $this->name,
            $this->subjectType,
            $this->subjectId,
            $this->payload,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return hash('sha256', $material);
    }
}
