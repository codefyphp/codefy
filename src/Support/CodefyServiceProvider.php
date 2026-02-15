<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Application;
use Qubus\Inheritance\ForwardCallAware;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;

abstract class CodefyServiceProvider extends BaseServiceProvider
{
    use ForwardCallAware;

    /**
     * All the registered booting callbacks.
     *
     * @var array<\Closure>
     */
    protected array $bootingCallbacks = [];

    /**
     * All the registered booted callbacks.
     *
     * @var array<\Closure>
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
     * @inheritDoc
     */
    public function booting(\Closure $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * @inheritDoc
     */
    public function booted(\Closure $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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

    /**
     * @inheritDoc
     */
    public function publishes(array $paths, ?string $group = null): void
    {
        foreach ($paths as $from => $tag) {
            $tag = $group ?? $tag; // if group given, override
            $this->publishes[$tag][$from] = $tag;
        }
    }

    /**
     * @inheritDoc
     */
    public function pathsToPublish(?string $tag = null): array
    {
        if ($tag !== null) {
            return $this->publishes[$tag] ?? [];
        }

        return array_merge(...array_values($this->publishes ?: [[]]));
    }

    /**
     * List all tags defined by this provider.
     *
     * @return array<string>
     */
    public function publishTags(): array
    {
        return array_keys($this->publishes);
    }
}
