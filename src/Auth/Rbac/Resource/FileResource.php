<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Resource;

use Codefy\Framework\Auth\Rbac\Entity\Permission;
use Codefy\Framework\Auth\Rbac\Entity\Role;
use Codefy\Framework\Auth\Rbac\Exception\SentinelException;
use Codefy\Framework\Support\LocalStorage;
use League\Flysystem\FilesystemException;
use Qubus\Exception\Data\TypeException;

class FileResource extends BaseStorageResource
{
    /**
     * @var string
     */
    protected string $file;

    /**
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * @throws SentinelException
     * @throws FilesystemException|TypeException
     */
    public function load(): void
    {
        $this->clear();

        if (!file_exists($this->file) || (!$data = LocalStorage::disk()->read(json_decode($this->file, true)))) {
            $data = [];
        }

        $this->restorePermissions($data['permissions'] ?? []);
        $this->restoreRoles($data['roles'] ?? []);
    }

    /**
     * @throws FilesystemException
     * @throws TypeException|FilesystemException
     */
    public function save(): void
    {
        $data = [
            'roles' => [],
            'permissions' => [],
        ];
        foreach ($this->roles as $role) {
            $data['roles'][$role->name] = $this->roleToRow($role);
        }
        foreach ($this->permissions as $permission) {
            $data['permissions'][$permission->name] = $this->permissionToRow($permission);
        }

        LocalStorage::disk()->write($this->file, json_encode(value: $data, flags: JSON_PRETTY_PRINT));
    }

    protected function roleToRow(Role $role): array
    {
        $result = [];
        $result['name'] = $role->name;
        $result['description'] = $role->description;
        $childrenNames = [];
        foreach ($role->getChildren() as $child) {
            $childrenNames[] = $child->name;
        }
        $result['children'] = $childrenNames;
        $permissionNames = [];
        foreach ($role->getPermissions() as $permission) {
            $permissionNames[] = $permission->name;
        }
        $result['permissions'] = $permissionNames;
        return $result;
    }

    protected function permissionToRow(Permission $permission): array
    {
        $result = [];
        $result['name'] = $permission->name;
        $result['description'] = $permission->description;
        $childrenNames = [];
        foreach ($permission->getChildren() as $child) {
            $childrenNames[] = $child->name;
        }
        $result['children'] = $childrenNames;
        $result['ruleClass'] = $permission->getRuleClass();
        return $result;
    }

    /**
     * @throws SentinelException
     */
    protected function restorePermissions(array $permissionsData): void
    {
        /** @var string[][] $permChildrenNames */
        $permChildrenNames = [];

        foreach ($permissionsData as $pData) {
            $permission = $this->addPermission($pData['name'] ?? '', $pData['description'] ?? '');
            $permission->setRuleClass($pData['ruleClass'] ?? '');
            $permChildrenNames[$permission->name] = $pData['children'] ?? [];
        }

        foreach ($permChildrenNames as $permissionName => $childrenNames) {
            foreach ($childrenNames as $childName) {
                $permission = $this->getPermission($permissionName);
                $child = $this->getPermission($childName);
                if ($permission && $child) {
                    $permission->addChild($child);
                }
            }
        }
    }

    /**
     * @throws SentinelException
     */
    protected function restoreRoles($rolesData): void
    {
        /** @var string[][] $rolesChildrenNames */
        $rolesChildrenNames = [];

        foreach ($rolesData as $rData) {
            $role = $this->addRole($rData['name'] ?? '', $rData['description'] ?? '');
            $rolesChildrenNames[$role->name] = $rData['children'] ?? [];
            $permissionNames = $rData['permissions'] ?? [];
            foreach ($permissionNames as $permissionName) {
                if ($permission = $this->getPermission($permissionName)) {
                    $role->addPermission($permission);
                }
            }
        }

        foreach ($rolesChildrenNames as $roleName => $childrenNames) {
            foreach ($childrenNames as $childName) {
                $role = $this->getRole($roleName);
                $child = $this->getRole($childName);
                if ($role && $child) {
                    $role->addChild($child);
                }
            }
        }
    }
}
