<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Middleware;

use Codefy\Framework\Auth\UserSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionService;

final class UserSessionMiddleware implements MiddlewareInterface
{
    public const SESSION_ATTRIBUTE = 'USERSESSION';

    public function __construct(protected ConfigContainer $configContainer, protected SessionService $sessionService)
    {
    }

    /**
     * @throws TypeException
     * @throws Exception
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userDetails = $request->getAttribute(AuthenticationMiddleware::AUTH_ATTRIBUTE);

        $this->sessionService::$options = [
            'cookie-name' => 'USERSESSID',
            'cookie-lifetime' => (int) $this->configContainer->getConfigKey(key: 'cookies.lifetime', default: 86400),
        ];
        $session = $this->sessionService->makeSession($request);

        /** @var UserSession $user */
        $user = $session->get(type: UserSession::class);
        $user
            ->withToken(token: $userDetails->token)
            ->withRole(role: $userDetails->role);

        $request = $request->withAttribute(self::SESSION_ATTRIBUTE, $user);

        $response = $handler->handle($request);

        return $this->sessionService->commitSession($response, $session);
    }
}
