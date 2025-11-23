<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Laminas\Diactoros\ResponseFactory;
use Qubus\Routing\Psr7Router;
use Qubus\Routing\Route\InjectorMiddlewareResolver;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;

use function Codefy\Framework\Helpers\public_path;
use function Codefy\Framework\Helpers\router_basepath;

class RouterServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->singleton(key: Router::class, value: function () {
            $router = new Router(
                routeCollector: new RouteCollector(
                    routes: [],
                    basePath: $this->codefy->basePath(),
                    matchTypes: []
                ),
                container: $this->codefy,
                responseFactory: new ResponseFactory(),
                resolver: new InjectorMiddlewareResolver(container: $this->codefy),
            );

            $router->baseMiddleware =  $this->codefy->getBaseMiddlewares();
            $router->setBasePath(basePath: router_basepath(path: public_path()));
            $router->setDefaultNamespace(
                namespace: $this->codefy->configContainer->getConfigKey(
                    key: 'app.controller_namespace',
                    default: $this->codefy->controllerNamespace
                )
            );
            return $router;
        });

        $this->codefy->alias(original: Psr7Router::class, alias: Router::class);
        $this->codefy->alias(original: 'router', alias: Router::class);
        $this->codefy->share(nameOrInstance: Psr7Router::class);
        $this->codefy->share(nameOrInstance: Router::class);
        $this->codefy->share(nameOrInstance: 'router');
    }
}
