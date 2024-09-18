<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth;

use Codefy\Framework\Auth\Rbac\Rbac;
use Codefy\Framework\Auth\Repository\AuthUserRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionEntity;

class Auth implements Sentinel
{
    public function __construct(
        protected AuthUserRepository $repository,
        protected ConfigContainer $configContainer,
        protected Rbac $rbac,
        protected ResponseFactoryInterface $responseFactory,
    ) {
    }

    /**
     * @throws Exception
     */
    public function authenticate(ServerRequestInterface $request): ?SessionEntity
    {
        $params   = $request->getParsedBody();
        $identity = $this->configContainer->getConfigKey(key: 'auth.pdo.identity', default: 'username');
        $password = $this->configContainer->getConfigKey(key: 'auth.pdo.password', default: 'password');

        if (! isset($params[$identity]) || ! isset($params[$password])) {
            return null;
        }

        $user = $this->repository->authenticate(
            credential: $params[$identity],
            password: $params[$password]
        );

        if (null !== $user) {
            return $user;
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function unauthorized(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(code: 302)
            ->withHeader(
                name: 'Location',
                value: $this->configContainer->getConfigKey(
                    key: 'auth.http_redirect',
                    default: $this->configContainer->getConfigKey(key: 'auth.login_url')
                )
            );
    }
}
