<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Codefy\Framework\Application;
use Codefy\Framework\Contracts\Kernel as HttpKernel;
use Qubus\Error\Handlers\DebugErrorHandler;
use Qubus\Error\Handlers\ErrorHandler;
use Qubus\Error\Handlers\ProductionErrorHandler;
use Qubus\Http\HttpPublisher;
use Qubus\Http\ServerRequestFactory as ServerRequest;
use Qubus\Routing\Router;

use function Codefy\Framework\Helpers\public_path;
use function Codefy\Framework\Helpers\router_basepath;
use function Qubus\Security\Helpers\__observer;
use function sprintf;
use function version_compare;

use const PHP_VERSION;

final class Kernel implements HttpKernel
{
    public readonly Application $codefy;

    public readonly Router $router;

    protected array $bootstrappers = [
        \Codefy\Framework\Bootstrap\RegisterProviders::class,
        \Codefy\Framework\Bootstrap\BootProviders::class,
    ];

    public function __construct(Application $codefy, Router $router)
    {
        $this->codefy = $codefy;
        $this->router = $router;
        $this->router->setBaseMiddleware(middleware: $this->codefy->getBaseMiddlewares());
        $this->router->setBasePath(basePath: router_basepath(path: public_path()));
        $this->router->setDefaultNamespace(namespace: $this->codefy->getControllerNamespace());

        $this->registerErrorHandler();
    }

    public function codefy(): Application
    {
        return $this->codefy;
    }

    protected function dispatchRouter(): bool
    {
        return (new HttpPublisher())->publish(
            content: $this->router->match(
                serverRequest: ServerRequest::fromGlobals(
                    server: $_SERVER,
                    query: $_GET,
                    body: $_POST,
                    cookies: $_COOKIE,
                    files: $_FILES
                )
            ),
            emitter: null
        );
    }

    public function boot(): bool
    {
        if (version_compare(
            version1: $current = PHP_VERSION,
            version2: (string) $required = Application::MIN_PHP_VERSION,
            operator: '<'
        )) {
            die(
                sprintf(
                    'You are running PHP %s, but CodefyPHP requires at least <strong>PHP %s</strong> to run.',
                    $current,
                    $required
                )
            );
        }

        __observer()->action->doAction('kernel.preboot');

        if (! $this->codefy->hasBeenBootstrapped()) {
            $this->codefy->bootstrapWith(bootstrappers: $this->bootstrappers());
        }

        return $this->dispatchRouter();
    }

    /**
     * Get the bootstrappers.
     *
     * @return string[]
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    protected function registerErrorHandler(): ErrorHandler
    {
        if ($this->codefy()->hasDebugModeEnabled()) {
            return new DebugErrorHandler();
        }

        return new ProductionErrorHandler();
    }
}
