<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Strategy;

use Codefy\Framework\Application;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionRenderAware;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionUtilityAware;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Http\Factories\JsonResponseFactory;
use Throwable;

final class JsonHttpResponseStrategy implements HttpResponseStrategy
{
    public function __construct(protected Application $app)
    {
    }

    public function supports(Throwable $e, ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeaderLine('Accept'), 'application/json');
    }

    /**
     * @throws Exception
     */
    public function createResponse(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $status = ($e->getCode() >= 400 && $e->getCode() < 600)
        ? $e->getCode()
        : 500;

        return JsonResponseFactory::create(
            data: [
                'error' => $e->getMessage(),
                'type' => $e::class,
                'status' => $status,
            ],
            status: $status
        );
    }
}
