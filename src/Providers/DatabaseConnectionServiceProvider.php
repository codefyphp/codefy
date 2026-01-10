<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Expressive\Connection;
use Qubus\Expressive\Connection\DriverConnection;

final class DatabaseConnectionServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->singleton(Connection::class, function () {
            $config = $this->codefy->configContainer;
            $default = $config->getConfigKey(key: 'database.default');

            return DriverConnection::make($config->getConfigKey(key: "database.connections.{$default}"));
        });

        $this->codefy->share(nameOrInstance: Connection::class);
    }
}
