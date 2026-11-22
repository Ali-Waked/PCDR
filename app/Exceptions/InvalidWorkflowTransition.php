<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ContentStatus;
use RuntimeException;

final class InvalidWorkflowTransition extends RuntimeException
{
    public function __construct(
        public readonly ContentStatus $from,
        public readonly ContentStatus $to,
    ) {
        parent::__construct("Invalid workflow transition: {$from->value} -> {$to->value}");
    }
}
