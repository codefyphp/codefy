<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Strategy;

use Codefy\Framework\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Http\Factories\RedirectResponseFactory;

class RedirectHttpResponseStrategy implements HttpResponseStrategy
{
    public function __construct(protected Application $app)
    {
    }

    public function supports(\Throwable $e, ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeaderLine('Accept'), 'Referer');
    }

    public function createResponse(\Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $this->app->flash->error($e->getMessage());

        // @phpstan-ignore method.notFound
        $uri = $e->getUri()
        ?: $request->getHeaderLine('Referer')
        ?: '/';

        return RedirectResponseFactory::create(uri: $uri);
    }
}
