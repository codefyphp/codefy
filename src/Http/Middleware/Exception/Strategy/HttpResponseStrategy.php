<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Strategy;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HttpResponseStrategy
{
    public function supports(\Throwable $e, ServerRequestInterface $request): bool;

    public function createResponse(\Throwable $e, ServerRequestInterface $request): ResponseInterface;
}
