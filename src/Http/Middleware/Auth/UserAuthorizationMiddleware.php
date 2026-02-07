<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Factories\RedirectResponseFactory;

use function Qubus\Support\Helpers\is_false__;

class UserAuthorizationMiddleware implements MiddlewareInterface
{
    public const string HEADER_HTTP_STATUS_CODE = 'AUTH_STATUS_CODE';

    public function __construct(
        protected ConfigContainer $configContainer,
        protected ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_false__($this->isLoggedIn($request))) {
            return RedirectResponseFactory::create(
                $this->configContainer->getConfigKey(key: 'auth.redirect_guests_to')
            )->withAddedHeader(self::HEADER_HTTP_STATUS_CODE, 'not_authorized');
        }

        return $handler->handle($request);
    }

    /**
     * @throws \Exception
     */
    private function isLoggedIn(ServerRequestInterface $request): bool
    {
        $userCookie = $this->configContainer->getConfigKey(key: 'auth.cookie_name', default: 'USERSESSID');
        $user = $request->getCookieParams()[$userCookie];

        if (empty($user)) {
            return false;
        }

        return true;
    }
}
