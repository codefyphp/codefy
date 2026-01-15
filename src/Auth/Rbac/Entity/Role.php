<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

use Codefy\Framework\Auth\Rbac\Exception\SentinelException;

interface Role
{
    //phpcs:disable
    public string $name { get; }

    public string $description { get; }
    //phpcs:enable

    /**
     * @param Role $role
     */
    public function addChild(Role $role): void;

    /**
     * @param string $roleName
     */
    public function removeChild(string $roleName): void;

    /**
     * @return Role[]
     */
    public function getChildren(): array;

    /**
     * @param Permission $permission
     */
    public function addPermission(Permission $permission): void;

    /**
     * @param string $permissionName
     */
    public function removePermission(string $permissionName): void;

    /**
     * @param bool $withChildren
     * @return Permission[]
     */
    public function getPermissions(bool $withChildren = false): array;

    /**
     * @param string $permissionName
     * @param array|null $params
     * @return bool
     * @throws SentinelException
     */
    public function checkAccess(string $permissionName, ?array $params = null): bool;
}
