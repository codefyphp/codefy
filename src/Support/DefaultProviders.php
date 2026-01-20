<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Support\Traits\CollectionStackAware;
use Qubus\Injector\ServiceProvider\Bootable;
use Qubus\Injector\ServiceProvider\Serviceable;

final class DefaultProviders
{
    use CollectionStackAware;

    /**
     * @param array<Serviceable|Bootable> $collection
     */
    public function __construct(array $collection = [])
    {
        $this->collection = $collection ?: [
            \Codefy\Framework\Providers\AssetsServiceProvider::class,
            \Codefy\Framework\Providers\EventDispatcherServiceProvider::class,
            \Codefy\Framework\Providers\DatabaseConnectionServiceProvider::class,
            \Codefy\Framework\Providers\PdoServiceProvider::class,
            \Codefy\Framework\Providers\LocalizationServiceProvider::class,
        ];
    }
}
