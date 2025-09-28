<?php

declare(strict_types=1);

namespace Codefy\Framework\Factory\Traits;

use Qubus\Exception\Exception;
use ReflectionException;
use Stringable;

trait FileLoggerAware
{
    /**
     * System is unusable.
     *
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public static function emergency(string|Stringable $message, array $context = []): void
    {
        self::getLogger()->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public static function alert(string|Stringable $message, array $context = []): void
    {
        self::getLogger()->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public static function critical(string|Stringable $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public static function error(string|Stringable $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public static function warning(string|Stringable $message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public static function notice(string|Stringable $message, array $context = []): void
    {
        self::getLogger()->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public static function info(string|Stringable $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string|Stringable $message
     * @param array $context
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public static function debug(string|Stringable $message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }
}
