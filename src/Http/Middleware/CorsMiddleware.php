<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

use function implode;
use function in_array;

class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(protected ConfigContainer $configContainer)
    {
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $origin = $response->getHeaderLine('origin');

        if ($this->configContainer->getConfigKey(key: 'cors.access-control-allow-origin')[0] !== '*') {
            if (
                    !$origin || !in_array(
                        $origin,
                        $this->configContainer->getConfigKey(key: 'cors.access-control-allow-origin')
                    )
            ) {
                return $response;
            }
        }

        $headers = $this->configContainer->getConfigKey(key: 'cors');

        foreach ($headers as $key => $value) {
            $response = $response->withAddedHeader($key, implode(separator: ',', array: $value));
        }

        return $response;
    }
}
