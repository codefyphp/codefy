<?php

declare(strict_types=1);

use Qubus\Exception\Http\HttpExceptionFactory;
use Qubus\Exception\Http\NotFoundHttpException;
use Qubus\Exception\Http\UnauthorizedHttpException;

it('creates a NotFoundHttpException', function () {
    $e = HttpExceptionFactory::make(404, '/missing', 'Not found');

    expect($e)
        ->toBeInstanceOf(NotFoundHttpException::class)
        ->and($e->getMessage())->toBe('Not found')
        ->and($e->getUri())->toBe('/missing')
        ->and($e->getCode())->toBe(404);
});

it('creates an UnauthorizedHttpException', function () {
    $e = HttpExceptionFactory::make(401, '/login', 'Unauthorized');

    expect($e)
        ->toBeInstanceOf(UnauthorizedHttpException::class)
        ->and($e->getMessage())->toBe('Unauthorized')
        ->and($e->getUri())->toBe('/login')
        ->and($e->getCode())->toBe(401);
});
