<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Error\Handlers\Psr3ErrorHandler;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\Psr7Exception;
use Qubus\Http\Factories\RedirectResponseFactory;
use ReflectionException;
use Throwable;

class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(protected Application $app)
    {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws ReflectionException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (HttpException | Psr7Exception $e) {
            $this->app->flash->error($e->getMessage());

            $this->logException($e);

            return RedirectResponseFactory::create(
                uri: $e->getUri() ?? $request->getServerParams()['HTTP_REFERER'] ?? '/',
            );
        } catch (Throwable $t) {
            $this->app->flash->error('Internal Error');

            $this->logException($t);

            return RedirectResponseFactory::create(
                uri: $request->getServerParams()['HTTP_REFERER'] ?? '/',
            );
        }

        return $response;
    }

    /**
     * @throws ReflectionException
     * @throws TypeException
     */
    protected function logException(Throwable $t, array $context = []): void
    {
        $psrLogger = new Psr3ErrorHandler($this->app->getLogger());
        $psrLogger->handle($t, $context);
    }
}
