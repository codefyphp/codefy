<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use PDO;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

use function sprintf;

final class PdoServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws Exception
     */
    public function register(): void
    {
        /** @var ConfigContainer $config */
        $config = $this->codefy->make('codefy.config');

        $default = $config->getConfigKey(key: 'database.default');
        $dsn = sprintf(
            '%s:dbname=%s;host=%s;charset=utf8mb4',
            $config->getConfigKey(key: "database.connections.{$default}.driver"),
            $config->getConfigKey(key: "database.connections.{$default}.dbname"),
            $config->getConfigKey(key: "database.connections.{$default}.host")
        );

        $this->codefy->singleton(PDO::class, function () use ($dsn, $config, $default) {
            return new PDO(
                $dsn,
                $config->getConfigKey(
                    key: "database.connections.{$default}.username"
                ),
                $config->getConfigKey(
                    key: "database.connections.{$default}.password"
                ),
                [
                    PDO::ATTR_EMULATE_PREPARES => $config->getConfigKey(key: "database.pdo.emulate_prepares"),
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => $config->getConfigKey(key: "database.pdo.persistent"),
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        });

        $this->codefy->share(nameOrInstance: PDO::class);
    }
}
