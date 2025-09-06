<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Laminas\Diactoros\ResponseFactory;
use Qubus\Routing\Psr7Router;
use Qubus\Routing\Route\InjectorMiddlewareResolver;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;

final class RouterServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->singleton(Router::class, function () {
            $router = new Router(
                routeCollector: new RouteCollector(
                    routes: [],
                    basePath: $this->codefy->basePath(),
                    matchTypes: []
                ),
                container: $this->codefy,
                responseFactory: new ResponseFactory(),
                resolver: new InjectorMiddlewareResolver($this->codefy),
            );
            $router->setDefaultNamespace($this->codefy->controllerNamespace);

            return $router;
        });

        $this->codefy->alias(Psr7Router::class, Router::class);
        $this->codefy->alias(Router::class, 'router');
        $this->codefy->share(nameOrInstance: Psr7Router::class);
        $this->codefy->share(nameOrInstance: Router::class);
        $this->codefy->share(nameOrInstance: 'router');
    }
}
