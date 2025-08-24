<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Resource;

use Codefy\Framework\Auth\Rbac\Entity\Permission;
use Codefy\Framework\Auth\Rbac\Entity\RbacPermission;
use Codefy\Framework\Auth\Rbac\Entity\RbacRole;
use Codefy\Framework\Auth\Rbac\Entity\Role;
use Codefy\Framework\Auth\Rbac\Exception\SentinelException;

abstract class BaseStorageResource implements StorageResource
{
    //phpcs:disable
    public protected(set) array $roles = [] {
        &get => $this->roles;
    }

    public protected(set) array $permissions = [] {
        &get => $this->permissions;
    }
    //phpcs:enable

    /**
     * @throws SentinelException
     */
    public function addRole(string $name, string $description = ''): Role
    {
        if (isset($this->roles[$name])) {
            throw new SentinelException(message: 'Role already exists.');
        }
        $role = new RbacRole(name: $name, description: $description, rbacStorageCollection: $this);
        $this->roles[$name] = $role;
        return $role;
    }

    public function addPermission(string $name, string $description = ''): Permission
    {
        if (isset($this->permissions[$name])) {
            throw new SentinelException(message: 'Permission already exists.');
        }

        $permission = new RbacPermission(
            name: $name,
            description: $description,
            rbacStorageCollection: $this
        );

        $this->permissions[$name] = $permission;
        return $permission;
    }

    public function getRole(string $name): ?Role
    {
        return $this->roles[$name] ?? null;
    }

    public function deleteRole(string $name): void
    {
        unset($this->roles[$name]);

        foreach ($this->roles as $role) {
            $role->removeChild($name);
        }
    }

    public function getPermission(string $name): ?Permission
    {
        return $this->permissions[$name] ?? null;
    }

    public function deletePermission(string $name): void
    {
        unset($this->permissions[$name]);

        foreach ($this->roles as $role) {
            $role->removePermission(permissionName: $name);
        }

        foreach ($this->permissions as $permission) {
            $permission->removeChild(permissionName: $name);
        }
    }

    public function clear(): void
    {
        $this->roles = [];
        $this->permissions = [];
    }
}
