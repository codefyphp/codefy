<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Closure;
use Codefy\Framework\Http\Throttle\Condition;
use Codefy\Framework\Http\Throttle\RateException;
use Codefy\Framework\Http\Throttle\RateLimiter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Http\Factories\JsonResponseFactory;

class ThrottleMiddleware implements MiddlewareInterface
{
    private readonly ?Closure $identifierResolver;

    /** @param callable(ServerRequestInterface): string|null $identifierResolver */
    public function __construct(
        protected ConfigContainer $configContainer,
        protected RateLimiter $rateLimiter,
        ?callable $identifierResolver = null,
    ) {
        $this->identifierResolver = $identifierResolver === null ? null : Closure::fromCallable($identifierResolver);
        $this->rateLimiter->add(new Condition(
            $this->configContainer->getConfigKey('throttle.ttl', 60),
            $this->configContainer->getConfigKey('throttle.max_attempts', 60),
        ));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identifier = $this->identifierResolver !== null
        ? ($this->identifierResolver)($request)
        : ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
        if (!is_string($identifier) || $identifier === '') {
            throw new \InvalidArgumentException('The throttle identifier must be a non-empty string.');
        }

        try {
            $this->rateLimiter->increment($identifier);
        } catch (RateException $e) {
            return JsonResponseFactory::create(
                data: sprintf('You can only make %d requests in %d seconds', $e->condition->limit, $e->condition->ttl),
                status: 429
            )->withHeader('Retry-After', (string) $e->retryAfter)->withHeader('Cache-Control', 'no-store');
        }

        return $handler->handle($request);
    }
}
