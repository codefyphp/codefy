<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Auth\Rbac\RbacLoader;
use Codefy\Framework\Auth\Rbac\Resource\ConfigStorageResource;
use Codefy\Framework\Auth\Rbac\Resource\StorageResource;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Exception\Exception;

final class RbacServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws Exception
     */
    public function boot(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        $this->codefy->alias(original: StorageResource::class, alias: ConfigStorageResource::class);
        $this->codefy->share(nameOrInstance: StorageResource::class);

        /** @var RbacLoader $rbac */
        $rbac = $this->codefy->make(name: RbacLoader::class);

        $rbac->initRbacRoles();
        $rbac->initRbacPermissions();
    }
}
