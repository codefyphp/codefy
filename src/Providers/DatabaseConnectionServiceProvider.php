<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Opis\Database\Connection;
use PDO;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

use function sprintf;

final class DatabaseConnectionServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->singleton(Connection::class, function () {
            return $this->codefy->getDbConnection();
        });

        $this->codefy->share(nameOrInstance: Connection::class);
    }
}
