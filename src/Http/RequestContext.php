<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Fiber;
use Psr\Http\Message\ServerRequestInterface;
use WeakMap;

final class RequestContext
{
    private static ?ServerRequestInterface $request = null;
    /** @var WeakMap<object, ServerRequestInterface>|null */
    private static ?WeakMap $fibers = null;

    public static function set(ServerRequestInterface $request): void
    {
        if (($fiber = Fiber::getCurrent()) !== null) {
            self::$fibers ??= new WeakMap();
            self::$fibers[$fiber] = $request;
        } else {
            self::$request = $request;
        }
    }

    public static function has(): bool
    {
        $fiber = Fiber::getCurrent();
        return $fiber === null ? self::$request !== null : isset(self::$fibers[$fiber]);
    }

    public static function get(): ServerRequestInterface
    {
        if (!self::has()) {
            throw new \LogicException('No request is bound to the current execution context.');
        }
        $fiber = Fiber::getCurrent();
        return $fiber === null ? self::$request : self::$fibers[$fiber];
    }

    public static function clear(): void
    {
        if (($fiber = Fiber::getCurrent()) !== null) {
            if (self::$fibers !== null) {
                unset(self::$fibers[$fiber]);
            }
        } else {
            self::$request = null;
        }
    }
}
