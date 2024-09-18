<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use PDO;

use function Codefy\Framework\Helpers\env;
use function sprintf;

final class PdoServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        $dsn = sprintf(
            '%s:dbname=%s;host=%s',
            env(key: 'DB_DRIVER'),
            env(key: 'DB_NAME'),
            env(key:'DB_HOST')
        );

        $this->codefy->define(name: PDO::class, args: [
            ':dsn' => $dsn,
            ':username' => env(key: 'DB_USER'),
            ':passwd' => env(key: 'DB_PASSWORD'),
        ]);

        $this->codefy->share(nameOrInstance: PDO::class);
    }
}
