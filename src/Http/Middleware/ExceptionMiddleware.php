<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Application;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Error\Handlers\Psr3ErrorHandler;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Http\HttpException;
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
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (HttpException $e) {
            $this->logException(
                $e,
                [
                    'uri' => $e->getUri(),
                    'code' => $e->getStatusCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'method' => $request->getMethod(),
                    'message' => $e->getMessage(),
                ]
            );

            return RedirectResponseFactory::create(
                uri: $e->getUri() ?? $request->getServerParams()['HTTP_REFERER'] ?? '/',
                status: $e->getStatusCode(),
                headers: $e->getHeaders()
            )->withBody(new Stream($this->app->flash->error($e->getMessage())));
        } catch (\Exception $e) {
            $this->logException(
                $e,
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'previous message' => $e->getPrevious()->getMessage()
                ]
            );

            return RedirectResponseFactory::create(
                uri: $request->getServerParams()['HTTP_REFERER'] ?? '/',
                status: 500,
                headers: []
            )->withBody(new Stream($this->app->flash->error('Internal Error')));
        } catch (Throwable $t) {
            $this->logException(
                $t,
                [
                    'message' => $t->getMessage(),
                    'file' => $t->getFile(),
                    'line' => $t->getLine(),
                    'previous message' => $t->getPrevious()->getMessage()
                ]
            );

            return RedirectResponseFactory::create(
                uri: $request->getServerParams()['HTTP_REFERER'] ?? '/',
                status: 500,
                headers: []
            )->withBody(new Stream($this->app->flash->error('Internal Error')));
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
