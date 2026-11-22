<?php

declare(strict_types=1);

namespace App\Support;

final class ScopeValue
{
    /** @param mixed $value */
    public static function canonicalJson($value): string
    {
        return json_encode(
            self::sort($value),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    /** @param mixed $value */
    public static function hash($value): string
    {
        return hash('sha256', self::canonicalJson($value));
    }

    /**
     * Recursively sort object keys so equivalent values produce one hash.
     *
     * @param  mixed  $value
     * @return mixed
     */
    private static function sort($value)
    {
        if (! is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);

        foreach ($value as $key => $item) {
            $value[$key] = self::sort($item);
        }

        if (! $isList) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }
}
