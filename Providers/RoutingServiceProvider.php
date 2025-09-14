<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Closure;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Exception\Data\TypeException;
use Qubus\Routing\Psr7Router;

use function Qubus\Support\Helpers\is_null__;

class RoutingServiceProvider extends CodefyServiceProvider
{
    /**
     * The callback that should be used to load the application's routes.
     */
    protected ?Closure $loadRoutesUsing = null;

    /**
     * The global callback that should be used to load the application's routes.
     */
    protected static ?Closure $alwaysLoadRoutesUsing = null;

    public function register(): void
    {
        $this->booting(fn () => $this->loadRoutes());
    }

    /**
     * Register the callback that will be used to load the application's routes.
     *
     * @param Closure $routesCallback
     * @return $this
     */
    protected function routes(Closure $routesCallback): static
    {
        $this->loadRoutesUsing = $routesCallback;

        return $this;
    }

    /**
     * Register the callback that will be used to load the application's routes.
     *
     * @param Closure|null $routesCallback
     * @return void
     */
    public static function loadRoutesUsing(?Closure $routesCallback = null): void
    {
        self::$alwaysLoadRoutesUsing = $routesCallback;
    }

    /**
     * Load the application routes.
     *
     * @return void
     * @throws TypeException
     */
    protected function loadRoutes(): void
    {
        if (! is_null__(self::$alwaysLoadRoutesUsing)) {
            $this->codefy->execute(self::$alwaysLoadRoutesUsing, [$this->codefy->router]);
        }

        if (! is_null__($this->loadRoutesUsing)) {
            $this->codefy->execute($this->loadRoutesUsing, [$this->codefy->router]);
        }
    }

    /**
     * Pass dynamic methods onto the router instance.
     *
     * @param string $method
     * @param array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo(
            $this->codefy->make(name: Psr7Router::class),
            $method,
            $parameters
        );
    }
}
