<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Expressive\Database;
use Qubus\Expressive\QueryBuilder;

final class QueryBuilderServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->singleton(key: Database::class, value: function () {
            return QueryBuilder::fromInstance(
                $this->codefy->getDbConnection()
            );
        });
        $this->codefy->share(nameOrInstance: Database::class);
    }
}
