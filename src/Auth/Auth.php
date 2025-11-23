<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth;

use Codefy\Framework\Auth\Rbac\Rbac;
use Codefy\Framework\Auth\Repository\AuthUserRepository;
use Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Factories\RedirectResponseFactory;
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
        $identity = $this->configContainer->getConfigKey(key: 'auth.pdo.fields.identity', default: 'username');
        $password = $this->configContainer->getConfigKey(key: 'auth.pdo.fields.password', default: 'password');

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
        $location = $this->configContainer->getConfigKey(key: 'auth.http_redirect');
        if (!empty($location)) {
            return RedirectResponseFactory::create($location);
        }

        return JsonResponseFactory::create(data: 'Forbidden.', status: 403);
    }
}
