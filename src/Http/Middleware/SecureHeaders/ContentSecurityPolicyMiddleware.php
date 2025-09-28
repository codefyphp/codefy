<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\SecureHeaders;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

class ContentSecurityPolicyMiddleware implements MiddlewareInterface
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

        $headers = new SecureHeaders($this->configContainer->getConfigKey(key: 'headers'))->headers();
        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}
