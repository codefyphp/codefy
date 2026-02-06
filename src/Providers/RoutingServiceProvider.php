<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Closure;
use Codefy\Framework\Proxy\Codefy;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Exception\Data\TypeException;
use Qubus\Routing\Psr7Router;
use Qubus\Routing\Route\RouteFileRegistrar;
use Qubus\Routing\Router;
use RuntimeException;

use function file_exists;
use function is_array;
use function is_callable;
use function is_string;
use function pathinfo;
use function sprintf;

use const PATHINFO_EXTENSION;

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
     * @param Closure|callable|string|array|null $routes
     * @return $this
     */
    protected function routes(Closure|callable|string|array|null $routes): static
    {
        $this->loadRoutesUsing = $this->normalizeRoutes($routes);

        return $this;
    }

    /**
     * Register the callback that will be used to load the application's routes.
     *
     * @param Closure|callable|string|array|null $routes
     * @return void
     */
    public static function loadRoutesUsing(Closure|callable|string|array|null $routes): void
    {
        static::$alwaysLoadRoutesUsing = $routes !== null
        ? static::normalizeRoutes($routes)
        : null;
    }

    /**
     * Load the application routes.
     *
     * @return void
     * @throws TypeException
     */
    protected function loadRoutes(): void
    {
        if (static::$alwaysLoadRoutesUsing !== null) {
            $this->codefy->execute(static::$alwaysLoadRoutesUsing, [$this->codefy->router]);
        }

        if ($this->loadRoutesUsing !== null) {
            $this->codefy->execute($this->loadRoutesUsing, [$this->codefy->router]);
        }
    }

    protected static function normalizeRoutes(Closure|callable|string|array $routes): Closure
    {
        return function (Router $router) use ($routes): void {
            // Handle arrays recursively
            if (is_array($routes)) {
                foreach ($routes as $route) {
                    $callback = static::normalizeRoutes($route);
                    $callback($router);
                }
                return;
            }

            // Handle closures and callables
            if ($routes instanceof Closure || is_callable($routes)) {
                $routes($router);
                return;
            }

            // Handle string (file path)
            $ext = pathinfo($routes, PATHINFO_EXTENSION);

            if ($ext === 'php') {
                // PHP route file
                if (file_exists($routes)) {
                    $result = new RouteFileRegistrar()->register($routes);
                    $result(Codefy::$PHP->router);
                }
                return;
            }

            if ($ext === 'json') {
                // JSON routes
                if (file_exists($routes)) {
                    Codefy::$PHP->router->loadRoutesFromJson($routes);
                }
                return;
            }

            throw new RuntimeException(sprintf("Unsupported routes definition: %s", get_debug_type($routes)));
        };
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
