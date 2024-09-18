<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac;

use Codefy\Framework\Auth\Rbac\Resource\StorageResource;
use Codefy\Framework\Auth\Rbac\Entity\Permission;
use Codefy\Framework\Auth\Rbac\Entity\Role;

class Rbac implements Guard
{
    protected StorageResource $storageResource;

    public function __construct(StorageResource $storageResource)
    {
        $this->storageResource = $storageResource;
        $this->storageResource->load();
    }

    /**
     * @param string $name
     * @param string $description
     *
     * @return Role
     */
    public function addRole(string $name, string $description = ''): Role
    {
        return $this->storageResource->addRole($name, $description);
    }

    public function addPermission(string $name, string $description = ''): Permission
    {
        return $this->storageResource->addPermission($name, $description);
    }

    /**
     * @return Role[]
     */
    public function getRoles(): array
    {
        return $this->storageResource->getRoles();
    }

    /**
     * @param string $name
     * @return Role|null
     */
    public function getRole(string $name): ?Role
    {
        return $this->storageResource->getRole($name);
    }

    /**
     * @param string $name
     */
    public function deleteRole(string $name): void
    {
        $this->storageResource->deleteRole($name);
    }

    /**
     * @return Permission[]
     */
    public function getPermissions(): array
    {
        return $this->storageResource->getPermissions();
    }

    /**
     * @param string $name
     * @return Permission|null
     */
    public function getPermission(string $name): ?Permission
    {
        return $this->storageResource->getPermission($name);
    }

    /**
     * @param string $name
     */
    public function deletePermission(string $name): void
    {
        $this->storageResource->deletePermission($name);
    }

    /**
     */
    public function clear(): void
    {
        $this->storageResource->clear();
    }

    private function load(): void
    {
        $this->storageResource->load();
    }

    public function save(): void
    {
        $this->storageResource->save();
    }
}
