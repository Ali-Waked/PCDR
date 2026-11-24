<?php

declare(strict_types=1);

namespace App\Support;

use JsonException;
use RuntimeException;

final class CsvSafeWriter
{
    /**
     * Neutralize spreadsheet formula injection while preserving UTF-8 text.
     */
    public static function field(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        }

        $text = str_replace(["\0", "\r\n"], ['', "\n"], (string) $value);

        if (! is_numeric($text) && preg_match('/^\s*[=+\-@\t]/u', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }

    /** @param array<int, mixed> $values */
    public static function row(array $values): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV temporary stream.');
        }

        try {
            $written = fputcsv($handle, array_map(self::field(...), $values));
            if ($written === false) {
                throw new RuntimeException('Unable to write CSV row.');
            }

            rewind($handle);
            $line = stream_get_contents($handle);
            if ($line === false) {
                throw new RuntimeException('Unable to read CSV row.');
            }

            return $line;
        } finally {
            fclose($handle);
        }
    }

    public static function json(mixed $value): string
    {
        try {
            return json_encode(
                $value,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return json_encode('[UNSERIALIZABLE]', JSON_THROW_ON_ERROR);
        }
    }
}
