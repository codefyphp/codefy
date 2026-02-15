<?php

declare(strict_types=1);

namespace Codefy\Framework\Configuration;

use Codefy\Framework\Application;
use Codefy\Framework\Bootstrap\RegisterProviders;
use Codefy\Framework\Providers\RoutingServiceProvider;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Injector\ServiceProvider\Bootable;
use Qubus\Injector\ServiceProvider\Serviceable;
use Qubus\Routing\Route\RoutingRegistrar;
use Qubus\Routing\Router;

use function array_merge;
use function array_unique;
use function is_array;
use function is_callable;
use function is_string;
use function Qubus\Security\Helpers\__observer;
use function Qubus\Support\Helpers\is_null__;

final class ApplicationBuilder
{
    public function __construct(protected Application $app)
    {
    }

    /**
     * Register the kernels for the application.
     *
     * @return $this
     */
    public function withKernels(): self
    {
        $this->app->singleton(
            \Codefy\Framework\Contracts\Http\Kernel::class,
            fn() => $this->app->make(name: \Codefy\Framework\Http\Kernel::class)
        );

        $this->app->singleton(
            key: \Codefy\Framework\Contracts\Console\Kernel::class,
            value: fn() => $this->app->make(name: \Codefy\Framework\Console\ConsoleKernel::class)
        );

        return $this;
    }

    /**
     * Register additional service providers.
     *
     * @param array<Serviceable|Bootable|string> $providers
     * @param bool $withBootstrapProviders
     * @return $this
     * @throws TypeException
     */
    public function withProviders(array $providers = [], bool $withBootstrapProviders = true): self
    {
        RegisterProviders::merge(
            providers: $providers,
            bootstrapProviderPath: $withBootstrapProviders
                    ? $this->app->getBootstrapProvidersPath()
                    : null
        );

        foreach ($providers as $provider) {
            $this->app->registerServiceProvider($provider);
        }

        return $this;
    }

    /**
     * Register an array of singletons that are resource intensive
     * or are not called often.
     *
     * @param array<class-string|string, callable> $singletons
     * @return $this
     */
    public function withSingletons(array $singletons = []): self
    {
        if (empty($singletons)) {
            return $this;
        }

        foreach ($singletons as $key => $callable) {
            $this->app->singleton($key, $callable);
        };

        return $this;
    }

    /**
     * Register custom middleware aliases.
     *
     * @throws Exception
     */
    public function withMiddleware(?callable $callback = null): self
    {
        $middleware = new Middleware();
        if (!is_null__($callback)) {
            $callback($middleware);
        }

        $arrayMerge = array_unique(
            array_merge(
                $middleware->getAliases(),
                $this->app->configContainer->getConfigKey(
                    key: 'app.middlewares',
                    default: Middleware::defaultMiddlewares()->toArray()
                )
            )
        );

        $this->app->configContainer->setConfigKey(key: 'app', value: ['middlewares' => $arrayMerge]);
        return $this;
    }

    /**
     * Register the routing services for the application.
     *
     * @param callable|\Closure|null     $using
     * @param array<string>|string|null  $web
     * @param array<class-string>|null   $class
     * @param array<string>|string|null  $api
     * @param string                     $apiPrefix
     * @param callable|null              $then
     * @return $this
     * @throws TypeException
     */
    public function withRouting(
        callable|\Closure|null $using = null,
        array|string|null $web = null,
        ?array $class = null,
        array|string|null $api = null,
        string $apiPrefix = 'api',
        ?callable $then = null,
    ): self {
        if (
                is_null__($using) &&
                (is_string($web) || is_array($web) || is_string($api) || is_array($api)) ||
                is_callable($then)
        ) {
            $using = $this->buildRoutingCallback($web, $api, $apiPrefix, $then);
        }

        RoutingServiceProvider::loadRoutesUsing($using);

        $this->app->booting(function () {
            $this->app->registerServiceProvider(serviceProvider: RoutingServiceProvider::class, force: true);
        });

        // Class-based routes
        if (!is_null__($class)) {
            foreach ($class as $route) {
                $this->app->execute([$route, 'handle']);
            }
        }

        return $this;
    }

    /**
     * Set whether environment variables
     * should be encrypted.
     *
     * @param bool $bool Default: false.
     * @return $this
     */
    public function withEncryptedEnv(bool $bool = false): self
    {
        $this->app::$encryptedEnv = $bool;

        return $this;
    }

    /**
     * Create the routing callback for the application.
     *
     * @param array<string>|string|null $web
     * @param array<string>|string|null $api
     * @param string                    $apiPrefix
     * @param callable|null             $then
     * @return \Closure
     */
    protected function buildRoutingCallback(
        array|string|null $web = null,
        array|string|null $api = null,
        string $apiPrefix = 'api',
        ?callable $then = null
    ): \Closure {
        return function (Router $router) use ($web, $api, $apiPrefix, $then) {
            $registrar = new RoutingRegistrar($router);

            // Web routes
            if ($web) {
                $registrar->group($web);
            }

            // API routes
            if ($api) {
                /**
                 * API middleware filter.
                 */
                $apiMiddleware = __observer()->filter->applyFilter('rest.api', ['api']);
                $registrar->group($api, middleware: $apiMiddleware, prefix: $apiPrefix);
            }

            // Final callback
            if (is_callable($then)) {
                $then($router);
            }
        };
    }

    /**
     * Register a callback to be invoked when the application's
     * service providers are registered.
     *
     * @param callable $callback
     * @return $this
     */
    public function registered(callable $callback): self
    {
        $this->app->registered(callback: $callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booting".
     *
     * @param callable $callback
     * @return $this
     */
    public function booting(callable $callback): self
    {
        $this->app->booting($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booted".
     *
     * @param callable $callback
     * @return $this
     */
    public function booted(callable $callback): self
    {
        $this->app->booted($callback);

        return $this;
    }

    /**
     * Return the application instance.
     *
     * @return Application
     */
    public function return(): Application
    {
        return $this->app;
    }
}
