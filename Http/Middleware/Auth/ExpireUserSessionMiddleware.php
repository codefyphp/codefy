<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Auth\UserSession;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\CookiesResponse;
use Qubus\Http\Session\SessionService;

class ExpireUserSessionMiddleware implements MiddlewareInterface
{
    public const string SESSION_ATTRIBUTE = 'EXPIRE_USERSESSION';

    public function __construct(protected SessionService $sessionService)
    {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->sessionService::$options = [
                'cookie-name' => 'USERSESSID',
                'cookie-lifetime' => 0,
        ];
        $session = $this->sessionService->makeSession($request);

        /** @var UserSession $user */
        $user = $session->get(type: UserSession::class);
        $user
            ->clear();

        $request = $request->withAttribute(self::SESSION_ATTRIBUTE, $session);

        $response = $handler->handle($request);

        $response = CookiesResponse::set(
            response: $response,
            setCookieCollection: $this->sessionService->cookie->make(
                name: 'USERSESSID',
                value: '',
                maxAge: 0
            )
        );

        return $this->sessionService->commitSession($response, $session);
    }
}
