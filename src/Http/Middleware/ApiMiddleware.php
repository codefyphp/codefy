<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;

use function sprintf;

class ApiMiddleware implements MiddlewareInterface
{
    public function __construct(protected ConfigContainer $configContainer)
    {
    }

    /**
     * @inheritDoc
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            str_starts_with($request->getHeaderLine('authorization'), 'Bearer ') &&
                $request->getHeaderLine('authorization') ===
                sprintf('Bearer %s', $this->configContainer->getConfigKey(key: 'app.api_key'))
        ) {
            return $handler->handle($request);
        }

        return JsonResponseFactory::create(data: 'Unauthorized.', status: 401);
    }
}
