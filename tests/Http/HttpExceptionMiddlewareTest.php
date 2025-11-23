<?php

declare(strict_types=1);

use Codefy\Framework\Http\Middleware\Exception\HttpExceptionMiddleware;
use Codefy\Framework\Http\Middleware\Exception\JsonHttpExceptionMiddleware;
use Codefy\Framework\Http\Middleware\Exception\RedirectionHttpExceptionMiddleware;
use Codefy\Framework\Tests\Http\FakeHandler;
use Qubus\Exception\Http\HttpException;
use Qubus\Http\Response;
use Qubus\Http\ServerRequestFactory;

$app = codefy();

it('passes through if no exception is thrown', function () use ($app) {

    $middleware = new JsonHttpExceptionMiddleware($app);

    $request = ServerRequestFactory::fromGlobals();
    $handler = new FakeHandler(null);

    $response = $middleware->process($request, $handler);

    expect($response)->toBeInstanceOf(Response::class);
});

it('handles json with JsonHttpExceptionMiddleware', function () use ($app) {
    $middleware = new JsonHttpExceptionMiddleware($app);

    $request = ServerRequestFactory::fromGlobals();
    $handler = new FakeHandler(new HttpException('/x', 'Boom', 500));

    $response = $middleware->process($request, $handler);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getHeaderLine('Content-Type'))->toContain('application/json')
        ->and((string) $response->getBody())->toContain('Boom');
});

it('handles json with HttpExceptionMiddleware', function () use ($app) {
    $middleware = $app->make(name: HttpExceptionMiddleware::class);

    $request = ServerRequestFactory::fromGlobals()->withAddedHeader('Accept', 'application/json');
    $handler = new FakeHandler(new HttpException('/x', 'Boom', 500));

    $response = $middleware->process($request, $handler);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getHeaderLine('Content-Type'))->toContain('application/json')
        ->and((string) $response->getBody())->toContain('Boom');
});

it('handles redirection with HttpExceptionMiddleware', function () use ($app) {
    $middleware = $app->make(name: HttpExceptionMiddleware::class);

    $request = ServerRequestFactory::fromGlobals();
    $handler = new FakeHandler(new HttpException('/x', 'Boom', 302));

    $response = $middleware->process($request, $handler);

    expect($response->getStatusCode())->toBe(302)
            ->and($response->getHeaderLine('Location'))->toContain('/x');
});

it('handles redirection with RedirectionHttpExceptionMiddleware', function () use ($app) {
    $middleware = $app->make(name: RedirectionHttpExceptionMiddleware::class);

    $request = ServerRequestFactory::fromGlobals();
    $handler = new FakeHandler(new HttpException('/x', 'Boom', 302));

    $response = $middleware->process($request, $handler);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->getHeaderLine('Location'))->toContain('/x');
});
