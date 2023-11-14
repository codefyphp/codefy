<?php

declare(strict_types=1);

namespace Codefy\Framework;

use Codefy\Framework\Support\Paths;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Dbal\Connection;
use Qubus\Dbal\DB;
use Qubus\EventDispatcher\EventDispatcher;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmBuilder;
use Qubus\Http\Session\Flash;
use Qubus\Http\Session\PhpSession;
use Qubus\Inheritance\InvokerAware;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Psr11\Container;
use Qubus\Injector\ServiceContainer;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;
use Qubus\Injector\ServiceProvider\Bootable;
use Qubus\Injector\ServiceProvider\Serviceable;
use Qubus\Mail\Mailer;
use Qubus\Support\Assets;

use function Codefy\Framework\Helpers\env;
use function get_class;
use function is_string;
use function rtrim;

use const DIRECTORY_SEPARATOR;

final class Application extends Container
{
    use InvokerAware;

    public const APP_VERSION = '2.0.0';

    public const MIN_PHP_VERSION = '8.2';

    public const DS = DIRECTORY_SEPARATOR;

    public static ?Application $APP = null;

    public readonly ServerRequestInterface $request;

    public readonly ResponseInterface $response;

    public readonly Assets $assets;

    public readonly Mailer $mailer;

    public readonly CacheInterface $psr16Cache;

    public readonly PhpSession $session;

    public readonly Flash $flash;

    public readonly EventDispatcher $event;

    public string $charset = 'UTF-8';

    public string $locale = 'en';

    public string $controllerNamespace = 'App\\Infrastructure\\Http\\Controllers';

    public static string $ROOT_PATH;

    protected ?string $basePath = null;

    protected ?string $appPath = null;

    protected array $serviceProviders = [];

    protected array $serviceProvidersRegistered = [];

    protected array $baseMiddlewares = [];

    private bool $booted = false;

    private bool $hasBeenBootstrapped = false;

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    private array $param = [];

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

        parent::__construct(InjectorFactory::create(config: $this->coreAliases()));
        $this->registerDefaultServiceProviders();

        $this->init();
    }

    private function init(): void
    {
        /** @var $this ServerRequestInterface */
        $this->request = $this->make(name: ServerRequestInterface::class);
        /** @var $this ResponseInterface */
        $this->response = $this->make(name: ResponseInterface::class);
        /** @var $this Assets */
        $this->assets = $this->make(name: Assets::class);
        /** @var $this Mailer */
        $this->mailer = $this->make(name: Mailer::class);
        /** @var $this CacheInterface */
        $this->psr16Cache = $this->make(name: CacheInterface::class);
        /** @var $this PhpSession */
        $this->session = $this->make(name: PhpSession::class);
        /** @var $this Flash */
        $this->flash = $this->make(name: Flash::class);
        /** @var $this EventDispatcher */
        $this->event = $this->make(name: EventDispatcher::class);

        Codefy::$PHP = $this;
    }

    /**
     * @throws Exception
     */
    public function getDbConnection(): Connection
    {
        /** @var $config ConfigContainer */
        $config = $this->make(name: 'codefy.config');

        $connection = env(key: 'DB_CONNECTION', default: 'default');

        return DB::connection([
            'driver' => $config->getConfigKey(key: "database.connections.{$connection}.driver"),
            'host' => $config->getConfigKey(key: "database.connections.{$connection}.host", default: 'localhost'),
            'port' => $config->getConfigKey(key: "database.connections.{$connection}.port", default: 3306),
            'charset' => $config->getConfigKey(key: "database.connections.{$connection}.charset", default: 'utf8mb4'),
            'collation' => $config->getConfigKey(
                key: "database.connections.{$connection}.collation",
                default: 'utf8mb4_unicode_ci'
            ),
            'username' => $config->getConfigKey(key: "database.connections.{$connection}.username"),
            'password' => $config->getConfigKey(key: "database.connections.{$connection}.password"),
            'dbname' => $config->getConfigKey(key: "database.connections.{$connection}.dbname"),
            'prefix' => $config->getConfigKey(key: "database.connections.{$connection}.prefix", default: ''),
        ]);
    }

    /**
     * @return OrmBuilder|null
     * @throws Exception
     */
    public function getDB(): ?OrmBuilder
    {
        /** @var $config ConfigContainer */
        $config = $this->make(name: 'codefy.config');

        $connection = env(key: 'DB_CONNECTION', default: 'default');

        return new OrmBuilder(
            connection: $this->getDbConnection(),
            tablePrefix: $config->getConfigKey(key: "database.connections.{$connection}.prefix", default: '')
        );
    }

    /**
     * Return the version of the Application's Framework.
     *
     * @return string
     */
    public function version(): string
    {
        return Application::APP_VERSION;
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
     * @throws Exception
     */
    public function registerConfiguredServiceProviders(): void
    {
        /** @var $config ConfigContainer */
        $config = $this->make(name: 'codefy.config');

        $providers = $config->getConfigKey(key: 'app.providers');

        foreach ($providers as $serviceProvider) {
            $this->registerServiceProvider(serviceProvider: $serviceProvider);
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

    /**
     * @throws Exception
     */
    public function getBaseMiddlewares(): array
    {
        /** @var $config ConfigContainer */
        $config = $this->make(name: 'codefy.config');

        return array_merge($config->getConfigKey(key: 'app.base_middlewares'), $this->baseMiddlewares);
    }

    public function isRunningInConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() == 'phpdbg';
    }

    /**
     * Determine if the application is running with debug mode enabled.
     *
     * @return bool
     * @throws Exception
     */
    public function hasDebugModeEnabled(): bool
    {
        /** @var $config ConfigContainer */
        $config = $this->make(name: 'codefy.config');

        if ($config->getConfigKey(key: 'app.debug') === 'true') {
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
                \Psr\Http\Server\RequestHandlerInterface::class => \Relay\Runner::class,
                \Psr\Http\Message\ResponseInterface::class => \Qubus\Http\Response::class,
                \Psr\Cache\CacheItemInterface::class => \Qubus\Cache\Psr6\Item::class,
                \Psr\Cache\CacheItemPoolInterface::class => \Qubus\Cache\Psr6\ItemPool::class,
                \Qubus\Cache\Psr6\TaggableCacheItem::class => \Qubus\Cache\Psr6\TaggablePsr6ItemAdapter::class,
                \Qubus\Cache\Psr6\TaggableCacheItemPool::class => \Qubus\Cache\Psr6\TaggablePsr6PoolAdapter::class,
                \Qubus\Config\Path\Path::class => \Qubus\Config\Path\ConfigPath::class,
                \Qubus\Config\ConfigContainer::class => \Qubus\Config\Collection::class,
                \Qubus\EventDispatcher\EventDispatcher::class => \Qubus\EventDispatcher\Dispatcher::class,
                \Qubus\Mail\Mailer::class => \Codefy\Framework\Support\CodefyMailer::class,
                'mailer' => \Qubus\Mail\Mailer::class,
                'dir.path' => \Codefy\Framework\Support\Paths::class,
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
                \Psr\SimpleCache\CacheInterface::class => \Qubus\Cache\Psr16\SimpleCache::class,
                \Qubus\Http\Session\PhpSession::class => \Qubus\Http\Session\NativeSession::class,
                \DateTimeInterface::class => \Qubus\Support\DateTime\QubusDateTimeImmutable::class,
                \Qubus\Support\DateTime\Date::class => \Qubus\Support\DateTime\QubusDate::class,
                \Symfony\Component\Console\Input\InputInterface::class
                => \Symfony\Component\Console\Input\ArgvInput::class,
                \Symfony\Component\Console\Output\OutputInterface::class
                => \Symfony\Component\Console\Output\ConsoleOutput::class,
                \Qubus\Http\Cookies\Factory\HttpCookieFactory::class
                => \Qubus\Http\Cookies\Factory\CookieFactory::class,
                \Qubus\Http\Session\Storage\SessionStorage::class
                => \Qubus\Http\Session\Storage\SimpleCacheStorage::class,
            ]
        ];
    }

    public function __get(mixed $name)
    {
        return $this->param[$name];
    }

    public function __isset(mixed $name): bool
    {
        return isset($this->data[$name]);
    }
    public function __set(mixed $name, mixed $value): void
    {
        $this->param[$name] = $value;
    }

    public function __unset(mixed $name): void
    {
        unset($this->param[$name]);
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
