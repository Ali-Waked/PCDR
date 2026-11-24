<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class AuditRedactor
{
    public const REDACTED = '[REDACTED]';

    /** @var list<string> */
    private const SENSITIVE_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'session',
        'api_key',
        'apikey',
        'credential',
        'private_key',
        'passphrase',
    ];

    /** @param mixed $value @return mixed */
    public static function redact($value)
    {
        if (! is_array($value)) {
            return is_string($value) ? Str::limit($value, 2000) : $value;
        }

        $clean = [];

        foreach ($value as $key => $item) {
            if (self::isSensitiveKey((string) $key)) {
                $clean[$key] = self::REDACTED;

                continue;
            }

            $clean[$key] = self::redact($item);
        }

        return $clean;
    }

    public static function isSensitiveKey(string $key): bool
    {
        $normalized = Str::lower(str_replace(['-', ' '], '_', $key));

        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
