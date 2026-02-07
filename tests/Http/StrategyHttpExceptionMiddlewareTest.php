<?php

declare(strict_types=1);

use Codefy\Framework\Http\Middleware\Exception\StrategyHttpExceptionMiddleware;
use Codefy\Framework\Tests\Http\FakeHandler;
use Qubus\Http\Response;
use Qubus\Http\ServerRequestFactory;

$app = codefy();

it('passes through if no exception is thrown', function () use ($app) {
    $middleware = $app->make(StrategyHttpExceptionMiddleware::class);

    $request = ServerRequestFactory::fromGlobals();
    $handler = new FakeHandler();

    $response = $middleware->process($request, $handler);

    expect($response)->toBeInstanceOf(Response::class);
});

it('handles HttpException with json strategy', function () use ($app) {
    $middleware = $app->make(StrategyHttpExceptionMiddleware::class);

    $request = ServerRequestFactory::fromGlobals()->withAddedHeader('Accept', 'application/json');
    $handler = new FakeHandler(
        new \Qubus\Exception\Http\HttpException('/x', 'JSON', 500)
    );

    $response = $middleware->process($request, $handler);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and((string)$response->getBody())->toContain('JSON');
});
