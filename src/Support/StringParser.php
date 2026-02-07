<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use function array_merge;
use function explode;
use function ltrim;
use function substr;
use function urldecode;

class StringParser
{
    /**
     * Parse a query string into an associative array, and merge with defaults.
     *
     * @param string        $query    The query string to parse.
     * @param array<mixed>  $defaults An array of default values.
     * @param bool          $strict   If true, skip malformed entries.
     * @return array<mixed>
     */
    public static function parse(string $query, array $defaults = [], bool $strict = false): array
    {
        $parsed = [];

        // Remove leading "?" if present
        $query = ltrim($query, '?');

        foreach (explode('&', $query) as $pair) {
            if (empty($pair)) {
                continue;
            }

            $parts = explode('=', $pair, 2);
            $key = urldecode($parts[0]);
            $value = urldecode($parts[1]);

            if ($strict && $key === '') {
                continue;
            }

            self::assignValue($parsed, $key, $value);
        }

        return array_merge($defaults, $parsed);
    }

    /**
     * Assign a value to an array, handling PHP-style array keys (e.g., foo[]=bar).
     *
     * @param array<mixed>  &$target
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    protected static function assignValue(array &$target, string $key, mixed $value): void
    {
        if (str_ends_with($key, '[]')) {
            $cleanKey = substr($key, 0, -2);
            $target[$cleanKey][] = $value;
        } else {
            $target[$key] = $value;
        }
    }
}
