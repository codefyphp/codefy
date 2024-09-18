<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

use Codefy\Framework\Auth\Rbac\Resource\StorageResource;

use function array_keys;
use function array_merge;

class RbacRole implements Role
{
    protected array $childrenNames = [];

    protected array $permissionNames = [];

    /**
     * @param string $roleName
     * @param string $description
     * @param StorageResource $rbacStorageCollection
     */
    public function __construct(
        protected string $roleName,
        protected string $description,
        protected StorageResource $rbacStorageCollection
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->roleName;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param Role $role
     */
    public function addChild(Role $role): void
    {
        $this->childrenNames[$role->getName()] = true;
    }

    /**
     * @param string $roleName
     */
    public function removeChild(string $roleName): void
    {
        unset($this->childrenNames[$roleName]);
    }

    /**
     * @return Role[]
     */
    public function getChildren(): array
    {
        $result = [];
        $roleNames = array_keys(array: $this->childrenNames);
        foreach ($roleNames as $name) {
            $result[$name] = $this->rbacStorageCollection->getRole(name: $name);
        }
        return $result;
    }

    /**
     * @param Permission $permission
     */
    public function addPermission(Permission $permission): void
    {
        $this->permissionNames[$permission->getName()] = true;
    }

    /**
     * @param string $permissionName
     */
    public function removePermission(string $permissionName): void
    {
        unset($this->permissionNames[$permissionName]);
    }

    /**
     * @param bool $withChildren
     * @return Permission[]
     */
    public function getPermissions(bool $withChildren = false): array
    {
        $result = [];
        $permissionNames = array_keys(array: $this->permissionNames);

        foreach ($permissionNames as $name) {
            $permission = $this->rbacStorageCollection->getPermission(name: $name);
            $result[$name] = $permission;
        }

        if ($withChildren) {
            foreach ($result as $permission) {
                $this->collectChildrenPermissions($permission, $result);
            }
            foreach ($this->getChildren() as $child) {
                $result = array_merge($result, $child->getPermissions(withChildren: true));
            }
        }

        return $result;
    }

    /**
     * @param string $permissionName
     * @param array|null $params
     * @return bool
     */
    public function checkAccess(string $permissionName, ?array $params = null): bool
    {
        $permissions = $this->getPermissions(withChildren: true);
        if (isset($permissions[$permissionName])) {
            if ($permissions[$permissionName]->checkAccess(params: $params)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Permission $permission
     * @param $result
     */
    protected function collectChildrenPermissions(Permission $permission, &$result): void
    {
        foreach ($permission->getChildren() as $childPermission) {
            $childPermissionName = $childPermission->getName();
            if (!isset($result[$childPermissionName])) {
                $result[$childPermissionName] = $childPermission;
                $this->collectChildrenPermissions($childPermission, $result);
            }
        }
    }
}
