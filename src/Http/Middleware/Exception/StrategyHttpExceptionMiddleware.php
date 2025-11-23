<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\Psr7Exception;
use Throwable;

class StrategyHttpExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected ExceptionHandler $handler
    ) {
    }

    /**
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpException | Psr7Exception $e) {
            return $this->handler->handle($e, $request);
        } catch (Throwable $t) {
            $safeException = new HttpException(
                uri: '/',
                message: 'Internal Server Error.',
                code: 500,
            );

            return $this->handler->handle($safeException, $request);
        }
    }
}
