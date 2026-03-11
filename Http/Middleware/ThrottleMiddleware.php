<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

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
    public function __construct(protected ConfigContainer $configContainer, protected RateLimiter $rateLimiter)
    {
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->rateLimiter->add(
            new Condition(
                $this->configContainer->integer(key: 'throttle.ttl'),
                $this->configContainer->integer(key: 'throttle.max_attempts'),
            )
        );

        $identifier = $request->getHeaderLine($this->configContainer->string(key: 'csrf.header'));

        try {
            $this->rateLimiter->increment($identifier);
        } catch (RateException $e) {
            $condition = $e->getCondition();
            return JsonResponseFactory::create(
                data: sprintf(
                    'You can only make %d requests in %d seconds',
                    $condition->getLimit(),
                    $condition->getTtl()
                ),
                status: 401
            );
        }

        return $handler->handle($request);
    }
}
