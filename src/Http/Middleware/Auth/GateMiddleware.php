<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Auth\Gate;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Factories\RedirectResponseFactory;

class GateMiddleware implements MiddlewareInterface
{
    protected ?string $permission = null;
    protected ?string $redirect = null;

    public function __construct(protected Gate $user)
    {
    }

    public function withArguments(?string $permission = null, ?string $redirect = null): static
    {
        $clone = clone $this;
        $clone->permission = $permission;
        $clone->redirect = $redirect;

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

        $permission = $this->user->can(permissionName: $this->permission);

        if (false === $this->user->current() || false === $permission) {
            if (null !== $this->redirect) {
                return RedirectResponseFactory::create(uri: $this->redirect);
            } else {
                return JsonResponseFactory::create(data: 'Access denied.');
            }
        }

        return $handler->handle($request);
    }
}
