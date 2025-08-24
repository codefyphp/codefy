<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Auth\UserSession;
use Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Session\SessionService;

use function Qubus\Support\Helpers\is_true__;

class UserAuthorizationMiddleware implements MiddlewareInterface
{
    public const string HEADER_HTTP_STATUS_CODE = 'AUTH_STATUS_CODE';

    public function __construct(
        protected SessionService $sessionService,
        protected ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_true__($this->isLoggedIn($request))) {
            return $handler->handle($request->withHeader(self::HEADER_HTTP_STATUS_CODE, 'ok'));
        }

        return $handler->handle($request->withHeader(self::HEADER_HTTP_STATUS_CODE, 'not_authorized'));
    }

    /**
     * @throws TypeException
     * @throws Exception
     */
    private function isLoggedIn(ServerRequestInterface $request): bool
    {
        $this->sessionService::$options = [
                'cookie-name' => 'USERSESSID',
        ];
        $session = $this->sessionService->makeSession($request);

        /** @var UserSession $user */
        $user = $session->get(type: UserSession::class);
        if ($user->isEmpty()) {
            return false;
        }

        return true;
    }
}
