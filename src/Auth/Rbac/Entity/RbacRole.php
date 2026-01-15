<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

use Codefy\Framework\Auth\Rbac\Resource\StorageResource;
use Override;

use function array_keys;
use function array_merge;

class RbacRole implements Role
{
    protected array $childrenNames = [];
    protected array $permissionNames = [];

    //phpcs:disable
    /**
     * @param string $name
     * @param string $description
     * @param StorageResource $rbacStorageCollection
     */
    public function __construct(
        public private(set) string $name {
            get => $this->name;
        },
        public private(set) string $description {
            get => $this->description;
        },
        protected StorageResource $rbacStorageCollection
    ) {
    }
    //phpcs:enable

    /**
     * @inheritDoc
     */
    #[Override]
    public function addChild(Role $role): void
    {
        $this->childrenNames[$role->name] = true;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function removeChild(string $roleName): void
    {
        unset($this->childrenNames[$roleName]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
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
     * @inheritDoc
     */
    #[Override]
    public function addPermission(Permission $permission): void
    {
        $this->permissionNames[$permission->name] = true;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function removePermission(string $permissionName): void
    {
        unset($this->permissionNames[$permissionName]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
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
     * @inheritDoc
     */
    #[Override]
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
     * @param mixed $result
     */
    protected function collectChildrenPermissions(Permission $permission, mixed &$result): void
    {
        foreach ($permission->getChildren() as $childPermission) {
            $childPermissionName = $childPermission->name;
            if (!isset($result[$childPermissionName])) {
                $result[$childPermissionName] = $childPermission;
                $this->collectChildrenPermissions($childPermission, $result);
            }
        }
    }
}
