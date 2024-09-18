<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Resource;

use Codefy\Framework\Auth\Rbac\Entity\Permission;
use Codefy\Framework\Auth\Rbac\Entity\Role;
use Codefy\Framework\Auth\Rbac\Exception\SentinelException;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

final class ConfigStorageResource extends BaseStorageResource
{
    public function __construct(protected ConfigContainer $configContainer)
    {
    }

    public function roleToArray(Role $role): array
    {
        $childrenNames = [];
        foreach ($role->getChildren() as $child) {
            $childrenNames[] = $child->getName();
        }

        $permissionNames = [];
        foreach ($role->getPermissions() as $permission) {
            $permissionNames[] = $permission->getName();
        }

        return [
            'name' => $role->getName(),
            'description' => $role->getDescription(),
            'children' => $childrenNames,
            'permissions' => $permissionNames,
        ];
    }

    public function permissionToArray(Permission $permission): array
    {
        $childrenNames = [];
        foreach ($permission->getChildren() as $child) {
            $childrenNames[] = $child->getName();
        }

        return [
            'name' => $permission->getName(),
            'description' => $permission->getDescription(),
            'children' => $childrenNames,
            'ruleClass' => $permission->getRuleClass(),
        ];
    }

    /**
     * @throws SentinelException
     * @throws Exception
     */
    public function load(): void
    {
        $this->clear();

        $this->restorePermissions(
            permissions: $this->configContainer->getConfigKey(key: 'rbac.permissions', default: [])
        );
        $this->restoreRoles(roles: $this->configContainer->getConfigKey(key: 'rbac.roles', default: []));
    }

    /**
     * @inheritDoc
     */
    public function save(): void
    {
        return;
    }

    /**
     * @throws SentinelException
     */
    protected function restorePermissions(array $permissions): void
    {
        /** @var string[][] $permChildrenNames */
        $permChildrenNames = [];

        foreach ($permissions as $data) {
            $permission = $this->addPermission(name: $data['name'] ?? '', description: $data['description'] ?? '');
            $permission->setRuleClass(ruleClass: $data['ruleClass'] ?? '');
            $permChildrenNames[$permission->getName()] = $data['children'] ?? [];
        }

        foreach ($permChildrenNames as $permissionName => $childrenNames) {
            foreach ($childrenNames as $childName) {
                $permission = $this->getPermission(name: $permissionName);
                $child = $this->getPermission(name: $childName);
                if ($permission && $child) {
                    $permission->addChild(permission: $child);
                }
            }
        }
    }

    /**
     * @throws SentinelException
     */
    protected function restoreRoles(array $roles): void
    {
        /** @var string[][] $rolesChildrenNames */
        $rolesChildrenNames = [];

        foreach ($roles as $data) {
            $role = $this->addRole(name: $data['name'] ?? '', description: $data['description'] ?? '');
            $rolesChildrenNames[$role->getName()] = $data['children'] ?? [];
            $permissionNames = $data['permissions'] ?? [];
            foreach ($permissionNames as $permissionName) {
                if ($permission = $this->getPermission(name: $permissionName)) {
                    $role->addPermission(permission: $permission);
                }
            }
        }

        foreach ($rolesChildrenNames as $roleName => $childrenNames) {
            foreach ($childrenNames as $childName) {
                $role = $this->getRole(name: $roleName);
                $child = $this->getRole(name: $childName);
                if ($role && $child) {
                    $role->addChild(role: $child);
                }
            }
        }
    }
}
