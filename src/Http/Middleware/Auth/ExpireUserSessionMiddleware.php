<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\CookiesResponse;
use Qubus\Http\Cookies\Factory\HttpCookieFactory;

class ExpireUserSessionMiddleware implements MiddlewareInterface
{
    public const string SESSION_ATTRIBUTE = 'EXPIRE_USERSESSION';

    public function __construct(protected ConfigContainer $configContainer, protected HttpCookieFactory $cookie)
    {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookieName = $this->configContainer->getConfigKey(key: 'auth.cookie_name', default: 'USERSESSID');

        $request = $request->withAttribute(self::SESSION_ATTRIBUTE, '');
        $response = $handler->handle($request);

        return CookiesResponse::set(
            response: $response,
            setCookieCollection: $this->cookie->make(
                name: $cookieName,
                value: '',
                maxAge: 0
            )
        );
    }
}
