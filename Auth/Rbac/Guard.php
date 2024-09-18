<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac;

use Codefy\Framework\Auth\Rbac\Entity\Permission;
use Codefy\Framework\Auth\Rbac\Entity\Role;
use Codefy\Framework\Auth\Rbac\Exception\SentinelException;

interface Guard
{
    public function addRole(string $name, string $description = ''): Role;

    /**
     * @param string $name
     * @param string $description
     * @return Permission
     * @throws SentinelException
     */
    public function addPermission(string $name, string $description = ''): Permission;

    public function getRoles(): array;

    public function getRole(string $name): Role|null;

    public function deleteRole(string $name): void;

    public function getPermissions(): array;

    public function getPermission(string $name): Permission|null;

    public function deletePermission(string $name): void;

    public function clear(): void;

    /**
     * @throws SentinelException
     */
    public function save(): void;
}
