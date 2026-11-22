<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a technically possible action is blocked by an unresolved
 * governance or privacy decision. It never reveals the blocked resource.
 */
final class Phase5GovernanceException extends RuntimeException
{
    public function __construct(
        public readonly string $gate,
        public readonly string $decision,
    ) {
        parent::__construct("Blocked by governance gate {$gate} ({$decision})");
    }
}
