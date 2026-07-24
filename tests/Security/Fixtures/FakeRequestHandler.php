<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Security\Fixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FakeRequestHandler implements RequestHandlerInterface
{
    public int $calls = 0;

    public ?ServerRequestInterface $lastRequest = null;

    public function __construct(
        private readonly ResponseInterface $response
    ) {
    }

    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        $this->calls++;
        $this->lastRequest = $request;

        return $this->response;
    }
}
