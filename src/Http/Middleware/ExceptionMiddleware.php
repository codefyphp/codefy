<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Application;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Http\HttpException;
use Qubus\Http\Factories\RedirectResponseFactory;
use Qubus\Http\Response;
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
            //$response = new Response($this->app->flash->error($e->getMessage()), $e->getStatusCode(), $e->getHeaders());
            return RedirectResponseFactory::create(
                uri: $e->getUri() ?? $request->getServerParams()['HTTP_REFERER'] ?? '/',
                status: $e->getStatusCode(),
                headers: $e->getHeaders()
            )->withBody(new Stream($this->app->flash->error($e->getMessage())));
        } catch (Throwable $t) {
            //$response = new Response($this->app->flash->error('Internal Error'), 500, []);
            return RedirectResponseFactory::create(
                uri: $request->getServerParams()['HTTP_REFERER'] ?? '/',
                status: 500,
                headers: []
            )->withBody(new Stream($this->app->flash->error('Internal Error')));
        }

        return $response;
    }
}
