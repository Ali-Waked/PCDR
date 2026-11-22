<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidDomainTransition extends RuntimeException
{
    public function __construct(
        public readonly string $resource,
        public readonly ?string $from,
        public readonly string $to,
    ) {
        parent::__construct("Invalid {$resource} transition: ".($from ?? 'null')." -> {$to}");
    }
}
