<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception;

use Codefy\Framework\Application;
use Codefy\Framework\Http\Middleware\Exception\Strategy\HttpResponseStrategy;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionUtilityAware;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Http\Factories\JsonResponseFactory;
use Throwable;

use function is_string;

class ExceptionHandler
{
    use HttpExceptionUtilityAware;

    /**
     * @param array<class-string<HttpResponseStrategy>>|HttpResponseStrategy[] $strategies
     */
    public function __construct(protected array $strategies, protected Application $app)
    {
    }

    /**
     * @throws Exception
     */
    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->strategies as $strategy) {
            if (is_string($strategy)) {
                $strategy = $this->app->make($strategy);
            }
            if ($strategy->supports($e, $request)) {
                $this->logException($e);
                return $strategy->createResponse($e, $request);
            }
        }

        $this->logException($e);

        return JsonResponseFactory::create(data: 'Internal Server Error', status: 500);
    }
}
