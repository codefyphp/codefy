<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Auth\Rbac\Resource\ConfigStorageResource;
use Codefy\Framework\Auth\Rbac\Resource\StorageResource;
use Codefy\Framework\Support\CodefyServiceProvider;

final class RbacServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        $this->codefy->alias(original: StorageResource::class, alias: ConfigStorageResource::class);
        $this->codefy->share(nameOrInstance: StorageResource::class);
    }
}
