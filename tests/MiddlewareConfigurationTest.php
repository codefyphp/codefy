<?php

declare(strict_types=1);

use Codefy\Framework\Configuration\ApplicationBuilder;
use Codefy\Framework\Configuration\Middleware;
use Codefy\Framework\Http\Middleware\CorsMiddleware;
use Codefy\Framework\Http\Middleware\BindRequestMiddleware;

it('merges middleware aliases as a flat map without sharing instance state', function () {
    $middleware = new Middleware();
    $middleware->alias(['custom' => CorsMiddleware::class])->alias(['other' => BindRequestMiddleware::class]);
    expect($middleware->getAliases())
        ->toBe(['custom' => CorsMiddleware::class, 'other' => BindRequestMiddleware::class])
        ->and(new Middleware()->getAliases())->toBe([]);
});

it('lets explicit middleware aliases override configured defaults', function () {
    $app = codefy();
    $old = $app->configContainer->getConfigKey('app.middlewares', []);
    try {
        new ApplicationBuilder($app)->withMiddleware(fn (Middleware $middleware) => $middleware->alias([
            'cors' => BindRequestMiddleware::class,
            'second-name' => BindRequestMiddleware::class,
        ]));
        $aliases = $app->configContainer->getConfigKey('app.middlewares');
        expect($aliases['cors'])
            ->toBe(BindRequestMiddleware::class)
            ->and($aliases['second-name'])->toBe(BindRequestMiddleware::class);
    } finally {
        $app->configContainer->setConfigKey('app', ['middlewares' => $old]);
    }
});
