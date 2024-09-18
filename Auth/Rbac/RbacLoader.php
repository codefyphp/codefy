<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac;

use Codefy\Framework\Auth\Rbac\Entity\Permission;
use Codefy\Framework\Auth\Rbac\Entity\Role;
use Codefy\Framework\Auth\Rbac\Exception\SentinelException;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use RuntimeException;

use function sprintf;

final readonly class RbacLoader
{
    public function __construct(private Rbac $rbac, private ConfigContainer $configContainer)
    {
    }

    /**
     * @throws Exception
     */
    public function initRbacRoles(): void
    {
        if ($this->configContainer->hasConfigKey(key: 'rbac')) {
            $rolesConfig = (array) $this->configContainer->getConfigKey(key: 'rbac.roles', default: []);
            $this->addRoles($rolesConfig);
        }
    }

    /**
     * @throws Exception
     */
    public function initRbacPermissions(): void
    {
        if ($this->configContainer->hasConfigKey(key: 'rbac')) {
            $permissionsConfig = (array) $this->configContainer->getConfigKey(key: 'rbac.permissions', default: []);
            $this->addPermissions($permissionsConfig);
        }
    }

    private function addRoles(array $rolesConfig, ?Role $parent = null): void
    {
        foreach ($rolesConfig as $name => $config) {
            $role = $this->rbac->addRole(name: $name, description: $config['description'] ?? '');
        }
        if (!empty($config['permissions'])) {
            foreach ((array) $config['permissions'] as $permissionName) {
                if ($permission = $this->rbac->getPermission(name: $permissionName)) {
                    $role->addPermission($permission);
                } else {
                    throw new RuntimeException(message: sprintf('Permission not found: %s', $permissionName));
                }
            }
        }

        $parent?->addChild($role);

        if (!empty($config['roles'])) {
            $this->addRoles(rolesConfig: $config['roles'], parent: $role);
        }
    }

    /**
     * @throws SentinelException
     */
    private function addPermissions(array $permissionsConfig, ?Permission $parent = null): void
    {
        foreach ($permissionsConfig as $name => $config) {
            $permission = $this->rbac->addPermission(name: $name, description: $config['description'] ?? '');

            $parent?->addChild($permission);

            if (!empty($config['permissions'])) {
                $this->addPermissions(permissionsConfig: $config['permissions'], parent: $permission);
            }
        }
    }
}