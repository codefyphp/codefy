<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Codefy\Framework\Application;
use Codefy\Framework\Contracts\Http\Kernel as HttpKernel;
use Exception;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Error\Handlers\DebugErrorHandler;
use Qubus\Error\Handlers\ErrorHandler;
use Qubus\Error\Handlers\ProductionErrorHandler;
use Qubus\Routing\Router;

use function Codefy\Framework\Helpers\public_path;
use function Codefy\Framework\Helpers\router_basepath;
use function Qubus\Config\Helpers\env;
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

    /**
     * @throws \Qubus\Exception\Exception
     */
    public function __construct(Application $codefy, Router $router)
    {
        $this->codefy = $codefy;
        $this->router = $router;
        $this->router->baseMiddleware =  $this->codefy->getBaseMiddlewares();
        $this->router->setBasePath(basePath: router_basepath(path: public_path()));
        $this->router->setDefaultNamespace(namespace: $this->codefy->controllerNamespace);

        $this->registerErrorHandler();
    }

    public function codefy(): Application
    {
        return $this->codefy;
    }

    /**
     * @throws Exception
     */
    protected function dispatchRouter(ServerRequestInterface $request): void
    {
        $response = $this->handle($request);
        $responseEmitter = new SapiEmitter();
        $responseEmitter->emit($response);
    }

    /**
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->match(serverRequest: $request);
    }

    /**
     * @throws Exception
     */
    public function boot(ServerRequestInterface $request): void
    {
        if (
            version_compare(
                version1: $current = PHP_VERSION,
                version2: (string) $required = Application::MIN_PHP_VERSION,
                operator: '<'
            )
        ) {
            die(
                sprintf(
                    'You are running PHP %s, but CodefyPHP requires at least <strong>PHP %s</strong> to run.',
                    $current,
                    $required
                )
            );
        }

        __observer()->action->doAction('kernel_preboot');

        if (! $this->codefy->hasBeenBootstrapped()) {
            $this->codefy->bootstrapWith(bootstrappers: $this->bootstrappers());
        }

        $this->dispatchRouter($request);
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

    /**
     * @throws \Qubus\Exception\Exception
     */
    protected function registerErrorHandler(): ErrorHandler
    {
        if ($this->codefy()->hasDebugModeEnabled()) {
            return new DebugErrorHandler(title: env(key: 'APP_NAME', default: 'CodefyPHP') . ' Error');
        }

        return new ProductionErrorHandler();
    }
}
