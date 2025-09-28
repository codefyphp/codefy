<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Http\Session\SessionEntity;

interface Sentinel
{
    /**
     * Authenticates user if the user exists.
     *
     * @param ServerRequestInterface $request
     * @return SessionEntity|null
     */
    public function authenticate(ServerRequestInterface $request): ?SessionEntity;

    /**
     * If user does not exist, return an unauthorized response.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function unauthorized(ServerRequestInterface $request): ResponseInterface;
}
