<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class MediaInUseException extends RuntimeException
{
    public function __construct(public readonly int $mediaId)
    {
        parent::__construct("Media {$mediaId} is referenced by published or draft content and cannot be deleted.");
    }
}
