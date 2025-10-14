<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use PDO;

final class PdoServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->singleton(PDO::class, function () {
            return $this->codefy->getDbConnection()->pdo;
        });

        $this->codefy->share(nameOrInstance: PDO::class);
    }
}
