<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Auth\UserSession;
use Codefy\Framework\Http\Middleware\Auth\AuthenticationMiddleware;
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
    public const string SESSION_ATTRIBUTE = 'USERSESSION';

    public function __construct(protected ConfigContainer $configContainer, protected SessionService $sessionService)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $userDetails = $request->getAttribute(AuthenticationMiddleware::AUTH_ATTRIBUTE);

            $expire = isset($request->getParsedBody()['rememberme'])
            && $request->getParsedBody()['rememberme'] === 'yes'
            ? $this->configContainer->getConfigKey(key: 'cookies.remember')
            : $this->configContainer->getConfigKey(key: 'cookies.lifetime');

            $this->sessionService::$options = [
                'cookie-name' => $this->configContainer->getConfigKey(key: 'auth.cookie_name', default: 'USERSESSID'),
                'cookie-lifetime' => (int) $expire,
            ];
            $session = $this->sessionService->makeSession($request);

            /** @var UserSession $user */
            $user = $session->get(type: UserSession::class);
            $user
                ->withToken(token: $userDetails->token);

            $request = $request->withAttribute(self::SESSION_ATTRIBUTE, $user);

            $response = $handler->handle($request);

            return $this->sessionService->commitSession($response, $session);
        } catch (TypeException | \Exception $e) {
            return $handler->handle($request);
        }
    }
}
