<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use PDO;

use function sprintf;

final class PdoServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->singleton(PDO::class, function () {
            return $this->codefy->getDbConnection()->getPDO();
        });

        $this->codefy->share(nameOrInstance: PDO::class);
    }
}
