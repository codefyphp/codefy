<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Auth\Gate;
use Codefy\Framework\Proxy\Codefy;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Factories\RedirectResponseFactory;

final class GateMiddleware implements MiddlewareInterface
{
    private ?string $permission = null;
    private ?string $redirect = null;
    private bool|string $redirectIfAuthorized = false;

    public function __construct(private readonly Gate $user, private readonly ConfigContainer $configContainer)
    {
    }

    public function withArguments(
        ?string $permission = null,
        ?string $redirect = null,
        bool|string $redirectIfAuthorized = false
    ): self {
        $clone = clone $this;
        $clone->permission = $permission;
        $clone->redirect = $redirect;
        $clone->redirectIfAuthorized = $redirectIfAuthorized;

        return $clone;
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (null === $this->permission) {
            return JsonResponseFactory::create('Gate is not set properly.');
        }

        if (false === $this->user->can(permissionName: $this->permission)) {
            Codefy::$PHP->flash->error(
                message: $this->configContainer->getConfigKey(
                    key: 'auth.access_denied_message',
                    default: 'Access denied.'
                ),
            );

            return $this->redirect !== null
            ? RedirectResponseFactory::create(uri: $this->redirect)
            : JsonResponseFactory::create(data: 'Access denied.');
        }

        if ((bool) $this->redirectIfAuthorized && $this->redirect !== null) {
            return RedirectResponseFactory::create(uri: $this->redirect);
        }

        return $handler->handle($request);
    }
}
