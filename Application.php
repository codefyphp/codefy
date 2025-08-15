<?php

declare(strict_types=1);

namespace Codefy\Framework;

use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Factory\FileLoggerSmtpFactory;
use Codefy\Framework\Pipeline\PipelineBuilder;
use Codefy\Framework\Support\BasePathDetector;
use Codefy\Framework\Support\LocalStorage;
use Codefy\Framework\Support\Paths;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Dbal\Connection;
use Qubus\Dbal\DB;
use Qubus\EventDispatcher\ActionFilter\Observer;
use Qubus\EventDispatcher\EventDispatcher;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmBuilder;
use Qubus\Http\Cookies\Factory\HttpCookieFactory;
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
use Qubus\Support\ArrayHelper;
use Qubus\Support\Assets;
use Qubus\Support\StringHelper;
use ReflectionException;

use function dirname;
use function get_class;
use function is_string;
use function Qubus\Config\Helpers\env;
use function Qubus\Support\Helpers\is_null__;
use function rtrim;

use const DIRECTORY_SEPARATOR;

/**
 * @property-read ServerRequestInterface $request
 * @property-read ResponseInterface $response
 * @property-read Assets $assets
 * @property-read Mailer $mailer
 * @property-read PhpSession $session
 * @property-read Flash $flash
 * @property-read EventDispatcher $event
 * @property-read HttpCookieFactory $httpCookie
 * @property-read LocalStorage $localStorage
 * @property-read ConfigContainer $configContainer
 * @property-read PipelineBuilder $pipeline
 * @property-read Observer $hook
 * @property-read StringHelper $string
 * @property-read ArrayHelper $array
 */
final class Application extends Container
{
    use InvokerAware;

    public const APP_VERSION = '2.1.2';

    public const MIN_PHP_VERSION = '8.2';

    public const DS = DIRECTORY_SEPARATOR;

    /**
     * The current globally available Application (if any).
     *
     * @var ?self
     */
    public static ?Application $APP = null;

    public string $charset = 'UTF-8';

    public string $locale = 'en';

    public string $controllerNamespace = 'App\\Infrastructure\\Http\\Controllers';

    public static string $ROOT_PATH = '';

    protected string $basePath = '';

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

        parent::__construct(InjectorFactory::create(config: $this->coreAliases()));
        $this->registerBaseBindings();
        $this->registerDefaultServiceProviders();
        $this->registerPropertyBindings();
    }

    private function registerBaseBindings(): void
    {
        self::$APP = $this;
        self::$ROOT_PATH = $this->basePath;
        $this->alias(original: 'app', alias: self::class);
    }

    /**
     * Dynamically created properties.
     *
     * @return void
     * @throws TypeException
     */
    private function registerPropertyBindings(): void
    {
        $contracts = [
            'request' => ServerRequestInterface::class,
            'response' => ResponseInterface::class,
            'assets' => Assets::class,
            'mailer' => Mailer::class,
            'session' => PhpSession::class,
            'flash' => Flash::class,
            'event' => EventDispatcher::class,
            'httpCookie' => HttpCookieFactory::class,
            'localStorage' => Support\LocalStorage::class,
            'configContainer' => ConfigContainer::class,
            'pipeline' => PipelineBuilder::class,
            'hook' => Observer::class,
            'string' => StringHelper::class,
            'array' => ArrayHelper::class,
        ];

        foreach ($contracts as $property => $name) {
            $this->{$property} = $this->make(name: $name);
        }

        Codefy::$PHP = $this::getInstance();
    }

    /**
     * Infer the application's base directory
     * from the environment and server.
     *
     * @return string|null
     */
    protected static function inferBasePath(): ?string
    {
        $basePath = (new BasePathDetector())->getBasePath();

        return match (true) {
            (env('APP_BASE_PATH') !== null && env('APP_BASE_PATH') !== false) => env('APP_BASE_PATH'),
            $basePath !== '' => $basePath,
            default => dirname(path: __FILE__, levels: 2),
        };
    }

    /**
     * FileLogger
     *
     * @throws ReflectionException
     */
    public static function getLogger(): LoggerInterface
    {
        return FileLoggerFactory::getLogger();
    }

    /**
     * FileLogger with SMTP support.
     *
     * @throws ReflectionException
     * @throws TypeException
     */
    public static function getSmtpLogger(): LoggerInterface
    {
        return FileLoggerSmtpFactory::getLogger();
    }

    /**
     * @throws Exception
     */
    public function getDbConnection(): Connection
    {
        /** @var ConfigContainer $config */
        $config = $this->make(name: 'codefy.config');

        $connection = $config->getConfigKey(key: 'database.default');

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
        ]);
    }

    /**
     * @return OrmBuilder|null
     * @throws Exception
     */
    public function getDB(): ?OrmBuilder
    {
        return new OrmBuilder(connection: $this->getDbConnection());
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
     * @return Application
     */
    public function singleton(string $key, callable $value): self
    {
        $this->proxy(name: $key, callableOrMethodStr: function ($c) use ($value) {
            static $object;

            if (null === $object) {
                $object = $value($c);
            }

            return $object;
        });

        return $this;
    }

    /**
     * @throws TypeException
     */
    protected function registerDefaultServiceProviders(): void
    {
        foreach (
            [
                Providers\ConfigServiceProvider::class,
                Providers\PdoServiceProvider::class,
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
            $this->make(name: EventDispatcher::class)->dispatch("bootstrapping.{$bootstrapper}");

            $this->make(name: $bootstrapper)->bootstrap($this);

            $this->make(name: EventDispatcher::class)->dispatch("bootstrapped.{$bootstrapper}");
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
        /** @var ConfigContainer $config */
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
        /** @var ConfigContainer $config */
        $config = $this->make(name: 'codefy.config');

        return array_merge($config->getConfigKey(key: 'app.base_middlewares'), $this->baseMiddlewares);
    }

    public function isRunningInConsole(): bool
    {
        return in_array(php_sapi_name(), ['cli', 'phpdbg']);
    }

    /**
     * Determine if the application is running with debug mode enabled.
     *
     * @return bool
     * @throws Exception
     */
    public function hasDebugModeEnabled(): bool
    {
        /** @var ConfigContainer $config */
        $config = $this->make(name: 'codefy.config');

        if ($config->getConfigKey(key: 'app.debug') === true) {
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
                \Psr\Http\Message\ResponseFactoryInterface::class => \Laminas\Diactoros\ResponseFactory::class,
                \Psr\Cache\CacheItemInterface::class => \Qubus\Cache\Psr6\Item::class,
                \Psr\Cache\CacheItemPoolInterface::class => \Qubus\Cache\Psr6\ItemPool::class,
                \Qubus\Cache\Psr6\TaggableCacheItem::class => \Qubus\Cache\Psr6\TaggablePsr6ItemAdapter::class,
                \Qubus\Cache\Psr6\TaggableCacheItemPool::class => \Qubus\Cache\Psr6\TaggablePsr6PoolAdapter::class,
                \Qubus\Config\Path\Path::class => \Qubus\Config\Path\ConfigPath::class,
                \Qubus\Config\ConfigContainer::class => \Qubus\Config\Collection::class,
                \Qubus\EventDispatcher\EventDispatcher::class => \Qubus\EventDispatcher\Dispatcher::class,
                \Qubus\Mail\Mailer::class => \Codefy\Framework\Support\CodefyMailer::class,
                'mailer' => Mailer::class,
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

    /**
     * Load environment file(s).
     *
     * @return void
     */
    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable(
            paths: $this->basePath(),
            names: ['.env','.env.local','.env.staging','.env.development','.env.production'],
            shortCircuit: false
        );
        $dotenv->safeLoad();
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

    /**
     * Determine if the application is in production.
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return env(key: 'APP_ENV') === 'production';
    }

    /**
     * Determine if the application is in development.
     *
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return env(key: 'APP_ENV') === 'development';
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     * @throws TypeException
     */
    public static function getInstance(?string $path = null): self
    {
        $basePath = match (true) {
            self::$ROOT_PATH !== '' => self::$ROOT_PATH,
            is_string($path) && $path !== '' => $path,
            default => self::inferBasePath(),
        };

        if (is_null__(self::$APP)) {
            self::$APP = new self(
                params: [
                    'basePath' => $basePath,
                ]
            );
        }

        return self::$APP;
    }
}
