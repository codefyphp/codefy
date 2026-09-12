<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Strategy;

use Codefy\Framework\Application;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionUtilityAware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\RedirectResponseFactory;

class RedirectHttpResponseStrategy implements HttpResponseStrategy
{
    use HttpExceptionUtilityAware;

    public function __construct(protected Application $app)
    {
    }

    public function supports(\Throwable $e, ServerRequestInterface $request): bool
    {
        return $request->hasHeader('Referer') && !$this->isJson($request);
    }

    /**
     * @throws Exception
     */
    public function createResponse(\Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $this->app->flash->error(\Qubus\Security\Helpers\esc_html($this->publicErrorMessage($e)));

        $uri = $e instanceof \Qubus\Exception\Http\HttpException
        || $e instanceof \Qubus\Exception\Http\Psr7Exception ? $e->getUri() : '';

        return RedirectResponseFactory::create(
            uri: $this->safeRedirectUri($request, $uri ?: $request->getHeaderLine('Referer'))
        );
    }
}
