<?php

declare(strict_types=1);

use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\NotFoundHttpException;
use Qubus\Exception\Http\Psr7Exception;

it('stores uri, message, and code', function () {
    $e = new HttpException('/a', 'Test', 400);

    expect($e->getUri())->toBe('/a')
        ->and($e->getMessage())->toBe('Test')
        ->and($e->getCode())->toBe(400);
});

it('extends base exception types correctly', function () {
    $e = new NotFoundHttpException('/missing', 'Nope');

    expect($e)
        ->toBeInstanceOf(NotFoundHttpException::class)
        ->and($e instanceof Psr7Exception)->toBeTrue()
        ->and($e instanceof HttpException)->toBeTrue()
        ->and($e->getCode())->toBe(404);
});
