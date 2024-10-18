<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use PDO;

use function Codefy\Framework\Helpers\config;
use function sprintf;

final class PdoServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $default = config(key: 'database.default');
        $dsn = sprintf(
            '%s:dbname=%s;host=%s',
            config(key: "database.connections.{$default}.driver"),
            config(key: "database.connections.{$default}.dbname"),
            config(key: "database.connections.{$default}.host")
        );

        $this->codefy->define(name: PDO::class, args: [
            ':dsn' => $dsn,
            ':username' => config(key: "database.connections.{$default}.username"),
            ':passwd' => config(key: "database.connections.{$default}.password"),
        ]);

        $this->codefy->share(nameOrInstance: PDO::class);
    }
}
