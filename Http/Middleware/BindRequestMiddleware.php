<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Http\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class BindRequestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        RequestContext::set($request);

        return $handler->handle($request);
    }
}
