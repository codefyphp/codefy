<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Request;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface FormRequestMiddleware extends MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function authorize(ServerRequestInterface $request): bool;

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function fails(ServerRequestInterface $request): bool;

    /**
     * @return array<mixed>
     */
    public function errors(): array;
}
