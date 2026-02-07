<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use function array_key_exists;
use function array_merge;
use function get_object_vars;
use function is_array;
use function is_object;
use function is_string;

class ArgsParser
{
    /**
     * Parse and merge user arguments with defaults.
     *
     * @param array<mixed>|object|string $args     Arguments passed in (array, object, or string).
     * @param array<mixed>               $defaults Default values to merge.
     * @param bool                       $deep     Whether to deep merge nested arrays.
     * @return array<mixed>
     */
    public static function parse(array|object|string $args, array $defaults = [], bool $deep = false): array
    {
        $args = self::toArray($args);

        if ($deep) {
            return self::deepMerge($defaults, $args);
        }

        return array_merge($defaults, $args);
    }

    /**
     * Convert object or iterable to array.
     *
     * @param array<mixed>|object|string $input
     * @return array<mixed>
     */
    protected static function toArray(array|object|string $input): array
    {
        if (is_object($input)) {
            return get_object_vars($input);
        }

        if (is_string($input)) {
            return StringParser::parse($input);
        }

        return $input;
    }

    /**
     * Deep merge two arrays (preserving numeric keys from args).
     *
     * @param array<mixed> $defaults
     * @param array<mixed> $args
     * @return array<mixed>
     */
    protected static function deepMerge(array $defaults, array $args): array
    {
        foreach ($args as $key => $value) {
            if (array_key_exists($key, $defaults) && is_array($defaults[$key]) && is_array($value)) {
                $defaults[$key] = self::deepMerge($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }
}
