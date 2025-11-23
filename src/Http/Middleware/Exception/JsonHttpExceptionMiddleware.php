<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception;

use Codefy\Framework\Application;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionUtilityAware;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\Psr7Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use ReflectionException;
use Throwable;

class JsonHttpExceptionMiddleware implements MiddlewareInterface
{
    use HttpExceptionUtilityAware;

    public function __construct(protected Application $app)
    {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws ReflectionException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpException | Psr7Exception $e) {
            $this->logException($e);

            return JsonResponseFactory::create(data: $e->getMessage(), status: (int) $e->getCode());
        } catch (Throwable $t) {
            $this->logException($t);

            return JsonResponseFactory::create(
                data: 'Internal Server Error.',
                status: 500,
            );
        }
    }
}
