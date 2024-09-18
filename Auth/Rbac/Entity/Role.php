<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

use Codefy\Framework\Auth\Rbac\Exception\SentinelException;

interface Role
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @param Role $role
     */
    public function addChild(Role $role);

    /**
     * @param string $roleName
     */
    public function removeChild(string $roleName);

    /**
     * @return Role[]
     */
    public function getChildren(): array;

    /**
     * @param Permission $permission
     */
    public function addPermission(Permission $permission);

    /**
     * @param string $permissionName
     */
    public function removePermission(string $permissionName);

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
    public function checkAccess(string $permissionName, array $params = null): bool;
}
