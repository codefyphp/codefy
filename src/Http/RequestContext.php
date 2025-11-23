<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Psr\Http\Message\ServerRequestInterface;

final class RequestContext
{
    private static ?ServerRequestInterface $request = null;

    public static function set(ServerRequestInterface $request): void
    {
        self::$request = $request;
    }

    public static function get(): ?ServerRequestInterface
    {
        return self::$request;
    }
}
