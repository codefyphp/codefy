<?php

declare(strict_types=1);

namespace Codefy\Framework\Traits;

use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Interfaces\Routable;
use Qubus\Routing\Router;

trait RouterAware
{
    protected function getRouter(): Router
    {
        return $this->make(name: Router::class);
    }

    public function group(array|string $params, callable $callback): Router
    {
        return $this->getRouter()->group($params, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function get(string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->get($uri, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function post(string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->post($uri, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function head(string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->head($uri, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function delete(string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->delete($uri, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function connect(string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->connect($uri, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function options(string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->options($uri, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function patch(string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->patch($uri, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function trace(string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->trace($uri, $callback);
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function map(array $verbs, string $uri, callable|string $callback): Routable
    {
        return $this->getRouter()->map($verbs, $uri, $callback);
    }
}
