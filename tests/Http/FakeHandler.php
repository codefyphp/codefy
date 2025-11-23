<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Http\Response;
use Throwable;

class FakeHandler implements RequestHandlerInterface
{
    public function __construct(private ?Throwable $throws = null)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->throws) {
            throw $this->throws;
        }
        return new Response();
    }
}
