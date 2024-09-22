<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Middleware;

use Codefy\Framework\Auth\Sentinel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Http\Session\SessionEntity;

use function Qubus\Support\Helpers\is_null__;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public const AUTH_ATTRIBUTE = 'USERSESSION';

    public function __construct(protected Sentinel $auth)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->auth->authenticate($request);
        if (is_null__(var: $user) || !$user instanceof SessionEntity) {
            return $this->auth->unauthorized($request);
        }
        return $handler->handle($request->withAttribute(self::AUTH_ATTRIBUTE, $user));
    }
}
