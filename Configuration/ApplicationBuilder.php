<?php

declare(strict_types=1);

namespace Codefy\Framework\Configuration;

use Closure;
use Codefy\Framework\Application;
use Codefy\Framework\Bootstrap\RegisterProviders;
use Codefy\Framework\Providers\RoutingServiceProvider;
use Qubus\Exception\Data\TypeException;
use Qubus\Routing\Route\RouteFileRegistrar;

use function is_array;
use function is_callable;
use function is_string;
use function Qubus\Support\Helpers\is_null__;
use function realpath;

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
     * @param array $providers
     * @return $this
     * @throws TypeException
     */
    public function withProviders(array $providers = []): self
    {
        RegisterProviders::merge($providers);

        foreach ($providers as $provider) {
            $this->app->registerServiceProvider($provider);
        }

        return $this;
    }

    /**
     * Register an array of singletons that are resource intensive
     * or are not called often.
     *
     * @param array $singletons
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
     * Register the routing services for the application.
     *
     * @param callable|Closure|null $using
     * @param array|string|null $web
     * @param array|null $class
     * @param array|string|null $api
     * @param callable|null $then
     * @return $this
     * @throws TypeException
     */
    public function withRouting(
        callable|Closure|null $using = null,
        array|string|null $web = null,
        ?array $class = null,
        array|string|null $api = null,
        ?callable $then = null,
    ): self {
        if (
            is_null__($using) &&
                (is_string($web) || is_array($web) || is_string($api) || is_array($api)) ||
            is_callable($then)
        ) {
            $using = $this->buildRoutingCallback($web, $api, $then);
        }

        RoutingServiceProvider::loadRoutesUsing($using);

        $this->app->booting(function () {
            $this->app->registerServiceProvider(serviceProvider: RoutingServiceProvider::class, force: true);
        });

        if (!is_null__($class) && is_array($class)) {
            foreach ($class as $route) {
                $this->app->execute([$route, 'handle']);
            }
        }

        return $this;
    }

    /**
     * Create the routing callback for the application.
     *
     * @param array|string|null  $web
     * @param array|string|null  $api
     * @param callable|null      $then
     * @return Closure
     */
    protected function buildRoutingCallback(
        array|string|null $web = null,
        array|string|null $api = null,
        ?callable $then = null
    ): Closure {
        return function () use ($web, $api, $then) {
            if (is_string($api) || is_array($api)) {
                if (is_array($api)) {
                    foreach ($api as $apiRoute) {
                        if (realpath($apiRoute) !== false) {
                            new RouteFileRegistrar($this->app->router)->register($apiRoute);
                        }
                    }
                } else {
                    require $api;
                }
            }

            if (is_string($web) || is_array($web)) {
                if (is_array($web)) {
                    foreach ($web as $webRoute) {
                        if (realpath($webRoute) !== false) {
                            new RouteFileRegistrar($this->app->router)->register($webRoute);
                        }
                    }
                } else {
                    new RouteFileRegistrar($this->app->router)->register($web);
                }
            }

            if (is_callable($then)) {
                $then($this->app);
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
