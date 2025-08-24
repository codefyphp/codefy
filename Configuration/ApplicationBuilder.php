<?php

declare(strict_types=1);

namespace Codefy\Framework\Configuration;

use Codefy\Framework\Application;
use Codefy\Framework\Bootstrap\RegisterProviders;
use Qubus\Exception\Data\TypeException;

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
     * Return the application instance.
     *
     * @return Application
     */
    public function return(): Application
    {
        return $this->app;
    }
}
