<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use function in_array;
use function strtoupper;

final class RequestMethod
{
    public const string GET = 'GET';

    public const string POST = 'POST';

    public const string PUT = 'PUT';

    public const string DELETE = 'DELETE';

    public const string PATCH = 'PATCH';

    public const string HEAD = 'HEAD';

    public const string OPTIONS = 'OPTIONS';

    public const string CONNECT = 'CONNECT';

    public const string TRACE = 'TRACE';

    public const array ANY = [
        self::GET,
        self::POST,
        self::PUT,
        self::DELETE,
        self::PATCH,
        self::HEAD,
        self::OPTIONS,
        self::CONNECT,
        self::TRACE,
    ];

    /**
     * Checks if a given http method is not a safe method.
     *
     * @param string $method
     * @return bool
     */
    public static function isUnsafe(string $method): bool
    {
        return self::isSafe($method) === false;
    }

    /**
     * Checks if a given http method is a safe method.
     *
     * @param string $method
     * @return bool
     */
    public static function isSafe(string $method): bool
    {
        return in_array(strtoupper($method), [self::GET, self::HEAD, self::OPTIONS, self::TRACE]);
    }
}
