<?php

declare(strict_types=1);

namespace Codefy\Framework;

use Codefy\Framework\Support\Paths;
use Psr\Container\ContainerInterface;
use Qubus\Dbal\Connection;
use Qubus\Dbal\DB;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmBuilder;
use Qubus\Inheritance\InvokerAware;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Psr11\Container;
use Qubus\Injector\ServiceContainer;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;
use Qubus\Injector\ServiceProvider\Bootable;
use Qubus\Injector\ServiceProvider\Serviceable;

use function Codefy\Framework\Helpers\env;
use function get_class;
use function is_string;
use function rtrim;

use const DIRECTORY_SEPARATOR;

class Application extends Container
{
    use InvokerAware;

    public const APP_VERSION = '1.0.2';

    public const MIN_PHP_VERSION = '8.2';

    public const DS = DIRECTORY_SEPARATOR;

    public static ?Application $APP = null;

    public static string $ROOT_PATH;

    protected ?string $basePath = null;

    protected ?string $appPath = null;

    protected array $serviceProviders = [];

    protected array $serviceProvidersRegistered = [];

    protected string $locale = 'en';

    protected string $controllerNamespace = 'App\\Infrastructure\\Http\\Controllers';

    protected array $baseMiddlewares = [];

    private bool $booted = false;

    private bool $hasBeenBootstrapped = false;

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    /**
     * @throws TypeException
     */
    public function __construct(array $params)
    {
        if (isset($params['basePath'])) {
            $this->withBasePath(basePath: $params['basePath']);
        }

        self::$APP = $this;
        self::$ROOT_PATH = $this->basePath;

        parent::__construct(InjectorFactory::create($this->coreAliases()));
        $this->registerDefaultServiceProviders();
    }

    /**
     * @throws Exception
     */
    public function getDbConnection(): Connection
    {
        $config = $this->make(name: 'codefy.config');

        $connection = env(key: 'DB_CONNECTION', default: 'default');

        return DB::connection([
            'driver' => $config->getConfigKey("database.connections.{$connection}.driver"),
            'host' => $config->getConfigKey("database.connections.{$connection}.host", 'localhost'),
            'port' => $config->getConfigKey("database.connections.{$connection}.port", 3306),
            'charset' => $config->getConfigKey("database.connections.{$connection}.charset", 'utf8mb4'),
            'collation' => $config->getConfigKey("database.connections.{$connection}.collation", 'utf8mb4_unicode_ci'),
            'username' => $config->getConfigKey("database.connections.{$connection}.username"),
            'password' => $config->getConfigKey("database.connections.{$connection}.password"),
            'dbname' => $config->getConfigKey("database.connections.{$connection}.dbname"),
            'prefix' => $config->getConfigKey("database.connections.{$connection}.prefix", ''),
        ]);
    }

    /**
     * @return OrmBuilder|null
     * @throws Exception
     */
    public function getDB(): ?OrmBuilder
    {
        $config = $this->make(name: 'codefy.config');

        $connection = env(key: 'DB_CONNECTION', default: 'default');

        return new OrmBuilder(
            connection: $this->getDbConnection(),
            tablePrefix: $config->getConfigKey("database.connections.{$connection}.prefix", '')
        );
    }

    /**
     * Return the version of the Application's Framework.
     *
     * @return string
     */
    public function version(): string
    {
        return static::APP_VERSION;
    }

    /**
     * Ensure a value or object will remain globally unique.
     *
     * @param string $key The value or object name
     * @param callable $value The closure that defines the object
     * @return void
     */
    public function singleton(string $key, callable $value): void
    {
        $this->proxy($key, function ($c) use ($value) {
            static $object;

            if (null === $object) {
                $object = $value($c);
            }

            return $object;
        });
    }

    /**
     * @throws TypeException
     */
    protected function registerDefaultServiceProviders(): void
    {
        foreach (
            [
             Providers\ConfigServiceProvider::class,
             Providers\FlysystemServiceProvider::class,
            ] as $serviceProvider
        ) {
            $this->registerServiceProvider(serviceProvider: $serviceProvider);
        }
    }


    public function bootstrapWith(array $bootstrappers): void
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make(name: \Qubus\EventDispatcher\EventDispatcher::class)->dispatch("bootstrapping.{$bootstrapper}");

            $this->make(name: $bootstrapper)->bootstrap($this);

            $this->make(name: \Qubus\EventDispatcher\EventDispatcher::class)->dispatch("bootstrapped.{$bootstrapper}");
        }
    }

    public function getContainer(): ServiceContainer|ContainerInterface
    {
        return $this;
    }

    public function setBoot(bool $bool = false): void
    {
        $this->booted = $bool;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * @return void
     * @throws TypeException
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootServiceProvider(provider: $p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Force register a service provider with the application.
     *
     * @param string|Serviceable|Bootable $provider
     * @return Serviceable|Bootable
     * @throws TypeException
     */
    public function forceRegisterServiceProvider(string|Serviceable|Bootable $provider): Serviceable|Bootable
    {
        return $this->registerServiceProvider(serviceProvider: $provider, force: true);
    }

    /**
     * Register all configured service providers via config/app.php.
     *
     * @return void
     * @throws TypeException
     */
    public function registerConfiguredServiceProviders(): void
    {
        $config = $this->make(name: 'codefy.config');

        $providers = $config->getConfigKey('app.providers');

        foreach ($providers as $serviceProvider) {
            $this->registerServiceProvider($serviceProvider);
        }
    }

    /**
     * Register a Service Provider to the application.
     *
     * @param string|Serviceable|Bootable $serviceProvider
     * @param bool $force
     * @return Serviceable|Bootable|string
     * @throws TypeException
     */
    public function registerServiceProvider(
        string|Serviceable|Bootable $serviceProvider,
        bool $force = false
    ): Serviceable|Bootable|string {
        // If the Service Provider had already been registered, then return it.
        if (($registered = $this->getRegisteredServiceProvider(provider: $serviceProvider)) && !$force) {
            return $registered;
        }

        if (is_string(value: $serviceProvider)) {
            $serviceProvider = $this->resolveServiceProvider(provider: $serviceProvider);
        }

        $serviceProvider->register();

        $this->markServiceProviderAsRegistered(registered: $registered, serviceProvider: $serviceProvider);
        // If application is booted, call the boot method on the service provider
        // if it exists.
        if ($this->isBooted()) {
            $this->bootServiceProvider(provider: $serviceProvider);
        };

        return $serviceProvider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  Serviceable|Bootable|string $provider
     * @return string|null
     */
    public function getRegisteredServiceProvider(Serviceable|Bootable|string $provider): string|null
    {
        return $this->serviceProviders[$provider] ?? null;
    }

    /**
     * Forget the ServiceProvider from the application.
     *
     * @param string|Serviceable|Bootable $serviceProvider
     * @return void
     */
    public function forgetServiceProvider(string|Serviceable|Bootable $serviceProvider): void
    {
        $registered = is_string(value: $serviceProvider) ? $serviceProvider : get_class(object: $serviceProvider);

        unset($this->serviceProviders[$registered]);
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return BaseServiceProvider
     */
    public function resolveServiceProvider(string $provider): BaseServiceProvider
    {
        return new $provider($this);
    }

    /**
     * Get the service providers that have been registered.
     *
     * @return array
     */
    public function getRegisteredProviders(): array
    {
        return $this->serviceProvidersRegistered;
    }

    /**
     * Determine if the given service provider is registered.
     *
     * @param  string  $provider
     * @return bool
     */
    public function providerIsRegistered(string $provider): bool
    {
        return isset($this->serviceProvidersRegistered[$provider]);
    }

    /**
     * Boot the given service provider.
     *
     * @param Serviceable|Bootable $provider
     * @return void
     * @throws TypeException
     */
    protected function bootServiceProvider(Serviceable|Bootable $provider): void
    {
        if (method_exists(object_or_class: $provider, method: 'boot')) {
            $this->execute(callableOrMethodStr: [$provider, 'boot']);
        }
    }

    /**
     * Register a new boot listener.
     *
     * @param  callable  $callback
     * @return void
     */
    public function booting(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  callable  $callback
     * @return void
     */
    public function booted(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param callable[]  $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array &$callbacks): void
    {
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);

            $index++;
        }
    }

    /**
     * Mark the particular ServiceProvider as having been registered.
     *
     * @param string|null $registered
     * @param Serviceable|Bootable $serviceProvider
     * @return void
     */
    protected function markServiceProviderAsRegistered(
        string|null $registered,
        Serviceable|Bootable $serviceProvider
    ): void {
        $this->serviceProviders[$registered] = $serviceProvider;
        $this->serviceProvidersRegistered[$registered] = true;
    }

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * Set the Application's base directory.
     *
     * @param string $basePath
     * @return mixed
     */
    public function withBasePath(string $basePath): self
    {
        $this->basePath = rtrim(string: $basePath, characters: '\/');

        $this->bindPathsToContainer();

        return $this;
    }

    protected function bindPathsToContainer(): void
    {
        $this->define(name: Paths::class, args: [
            ':paths' => [
                'path' => $this->path(),
                'base' => $this->basePath(),
                'bootstrap' => $this->bootStrapPath(),
                'config' => $this->configPath(),
                'database' => $this->databasePath(),
                'locale' => $this->localePath(),
                'public' => $this->publicPath(),
                'storage' => $this->storagePath(),
                'resource' => $this->resourcePath(),
                'view' => $this->viewPath(),
            ]
        ]);
    }

    /**
     * Set the Application's "app" directory.
     *
     * @param string $appPath
     * @return self
     */
    public function withAppPath(string $appPath): self
    {
        $this->appPath = $appPath;

        return $this;
    }

    /**
     * Get the path to the application's "app" directory.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->appPath ?: $this->basePath . self::DS . 'app';
    }

    /**
     * Get the Application's base directory.
     *
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the path to the application's "bootstrap" directory.
     *
     * @return string
     */
    public function bootStrapPath(): string
    {
        return $this->basePath . self::DS . 'bootstrap';
    }

    /**
     * Get the path to the application's "config" directory.
     *
     * @return string
     */
    public function configPath(): string
    {
        return $this->basePath . self::DS . 'config';
    }

    /**
     * Get the path to the application's "database" directory.
     *
     * @return string
     */
    public function databasePath(): string
    {
        return $this->basePath . self::DS . 'database';
    }

    /**
     * Get the path to the application's "locale" directory.
     *
     * @return string
     */
    public function localePath(): string
    {
        return $this->basePath . self::DS . 'locale';
    }

    /**
     * Get the path to the application's "public" directory.
     *
     * @return string
     */
    public function publicPath(): string
    {
        return $this->basePath . self::DS . 'public';
    }

    /**
     * Get the path to the application's "storage" directory.
     *
     * @return string
     */
    public function storagePath(): string
    {
        return $this->basePath . self::DS . 'storage';
    }

    /**
     * Get the path to the application's "resources" directory.
     *
     * @return string
     */
    public function resourcePath(): string
    {
        return $this->basePath . self::DS . 'resources';
    }

    /**
     * Get the path to the application's "view" directory.
     *
     * @return string
     */
    public function viewPath(): string
    {
        return $this->basePath . self::DS . 'views';
    }

    /**
     * @param string $locale
     * @return void
     */
    public function withLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    public function withControllerNamespace(string $namespace): void
    {
        $this->controllerNamespace = $namespace;
    }

    public function getControllerNamespace(): string
    {
        return $this->controllerNamespace;
    }

    public function withBaseMiddlewares(array $middlewares): void
    {
        $this->baseMiddlewares = $middlewares;
    }

    public function getBaseMiddlewares(): array
    {
        return array_merge([], $this->baseMiddlewares);
    }

    public function isRunningInConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() == 'phpdbg';
    }

    /**
     * Determine if the application is running with debug mode enabled.
     *
     * @return bool
     */
    public function hasDebugModeEnabled(): bool
    {
        $config = $this->make(name: 'codefy.config');

        if ($config->getConfigKey('app.debug') === 'true') {
            return true;
        }

        return false;
    }

    protected function coreAliases(): array
    {
        return [
            self::SHARED_ALIASES => [
                \Psr\Container\ContainerInterface::class => self::class,
                \Qubus\Injector\ServiceContainer::class => self::class,
                \Psr\Http\Message\ServerRequestInterface::class => \Qubus\Http\ServerRequest::class,
                \Psr\Http\Message\ServerRequestFactoryInterface::class => \Qubus\Http\ServerRequestFactory::class,
                \Psr\Http\Message\RequestInterface::class => \Qubus\Http\Request::class,
                \Psr\Http\Message\ResponseInterface::class => \Qubus\Http\Response::class,
                \Psr\Cache\CacheItemInterface::class => \Qubus\Cache\Psr6\Item::class,
                \Psr\Cache\CacheItemPoolInterface::class => \Qubus\Cache\Psr6\ItemPool::class,
                \Qubus\Cache\Psr6\TaggableCacheItem::class => \Qubus\Cache\Psr6\TaggablePsr6ItemAdapter::class,
                \Qubus\Cache\Psr6\TaggableCacheItemPool::class => \Qubus\Cache\Psr6\TaggablePsr6PoolAdapter::class,
                \Qubus\Config\Path\Path::class => \Qubus\Config\Path\ConfigPath::class,
                \Qubus\Config\ConfigContainer::class => \Qubus\Config\Collection::class,
                \Qubus\EventDispatcher\EventDispatcher::class => \Qubus\EventDispatcher\Dispatcher::class,
                'mailer' => \Qubus\Mail\Mailer::class,
                'dir.path' => Support\Paths::class,
                'container' => self::class,
                'codefy' => self::class,
                \Qubus\Routing\Interfaces\Collector::class => \Qubus\Routing\Route\RouteCollector::class,
                'router' => \Qubus\Routing\Router::class,
                \Codefy\Framework\Contracts\Kernel::class => \Codefy\Framework\Http\Kernel::class,
                \Codefy\Framework\Contracts\RoutingController::class => \Codefy\Framework\Http\BaseController::class,
                \League\Flysystem\FilesystemOperator::class => \Qubus\FileSystem\FileSystem::class,
                \League\Flysystem\FilesystemAdapter::class => \Qubus\FileSystem\Adapter\LocalFlysystemAdapter::class,
                \Qubus\Cache\Adapter\CacheAdapter::class => \Qubus\Cache\Adapter\FileSystemCacheAdapter::class,
                \Codefy\Framework\Scheduler\Mutex\Locker::class
                => \Codefy\Framework\Scheduler\Mutex\CacheLocker::class,
                \DateTimeZone::class => \Qubus\Support\DateTime\QubusDateTimeZone::class,
                \Symfony\Component\Console\Input\InputInterface::class
                => \Symfony\Component\Console\Input\ArgvInput::class,
                \Symfony\Component\Console\Output\OutputInterface::class
                => \Symfony\Component\Console\Output\ConsoleOutput::class,
            ]
        ];
    }

    public function __destruct()
    {
        $this->bootedCallbacks = [];
        $this->bootingCallbacks = [];
        $this->serviceProviders = [];
        $this->serviceProvidersRegistered = [];
        $this->baseMiddlewares = [];
    }
}
