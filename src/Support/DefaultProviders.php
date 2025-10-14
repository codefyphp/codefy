<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Support\Traits\CollectionStackAware;

class DefaultProviders
{
    use CollectionStackAware;

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
