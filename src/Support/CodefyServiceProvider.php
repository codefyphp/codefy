<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Closure;
use Codefy\Framework\Application;
use Qubus\Inheritance\ForwardCallAware;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;

abstract class CodefyServiceProvider extends BaseServiceProvider
{
    use ForwardCallAware;

    /**
     * All the registered booting callbacks.
     */
    protected array $bootingCallbacks = [];

    /**
     * All the registered booted callbacks.
     */
    protected array $bootedCallbacks = [];

    /** @var array<string, array<string,string>> */
    protected array $publishes = [];

    /** @var array<string, array<string>> */
    protected array $publishGroups = [];

    public function __construct(protected Application $codefy)
    {
        parent::__construct($codefy);
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * @param Closure $callback
     * @return void
     */
    public function booting(Closure $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * @param Closure $callback
     * @return void
     */
    public function booted(Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     *
     * @return void
     */
    public function callBootingCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->bootingCallbacks)) {
            $this->codefy->call($this->bootingCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Call the registered booted callbacks.
     *
     * @return void
     */
    public function callBootedCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->bootedCallbacks)) {
            $this->codefy->call($this->bootedCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Get the default providers for a CodefyPHP application.
     *
     * @return DefaultProviders
     */
    public static function defaultProviders(): DefaultProviders
    {
        return new DefaultProviders();
    }
}
