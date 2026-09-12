<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Http\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BindRequestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $previous = RequestContext::has() ? RequestContext::get() : null;
        RequestContext::set($request);
        try {
            return $handler->handle($request);
        } finally {
            if ($previous !== null) {
                RequestContext::set($previous);
            } else {
                RequestContext::clear();
            }
        }
    }
}
