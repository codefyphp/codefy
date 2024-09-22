<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

$resource = Mockery::mock(\Codefy\Framework\Auth\Rbac\Resource\StorageResource::class);

it('should create Role instance.', function () use ($resource) {
    $role = new \Codefy\Framework\Auth\Rbac\Entity\RbacRole(
        roleName: 'admin',
        description: 'Super administrator.',
        rbacStorageCollection: $resource
    );

    Assert::assertEquals('admin', $role->getName());
});

it('should get the role description', function () use ($resource) {
    $role = new \Codefy\Framework\Auth\Rbac\Entity\RbacRole(
        roleName: 'admin',
        description: 'Super administrator.',
        rbacStorageCollection: $resource
    );

    Assert::assertEquals('Super administrator.', $role->getDescription());
});

it('should get children.', function () use ($resource) {
    $role2 = new \Codefy\Framework\Auth\Rbac\Entity\RbacRole(
        roleName: 'role2',
        description: 'desc2',
        rbacStorageCollection: $resource
    );

    $role3 = new \Codefy\Framework\Auth\Rbac\Entity\RbacRole(
        roleName: 'role3',
        description: 'desc3',
        rbacStorageCollection: $resource
    );

    $resource = Mockery::mock(\Codefy\Framework\Auth\Rbac\Resource\StorageResource::class);
    $resource->shouldReceive('getRole')->andReturn($role2, $role3);

    $role1 = new \Codefy\Framework\Auth\Rbac\Entity\RbacRole(
        roleName: 'role1',
        description: 'desc1',
        rbacStorageCollection: $resource
    );

    $role1->addChild($role2);
    $role1->addChild($role3);

    Assert::assertEquals(
        [
        'role2' => $role2,
        'role3' => $role3
        ],
        $role1->getChildren()
    );

    $role1->removeChild(roleName: 'role2');

    Assert::assertEquals(['role3' => $role3], $role1->getChildren());
});

it('should get permissions.', function () use ($resource) {
    $permission2 = new \Codefy\Framework\Auth\Rbac\Entity\RbacPermission(
        permissionName: 'perm:perm2',
        description: 'desc2',
        rbacStorageCollection: $resource
    );

    $permission3 = new \Codefy\Framework\Auth\Rbac\Entity\RbacPermission(
        permissionName: 'perm:perm3',
        description: 'desc3',
        rbacStorageCollection: $resource
    );

    $resource = Mockery::mock(\Codefy\Framework\Auth\Rbac\Resource\StorageResource::class);
    $resource->shouldReceive('getPermission')->andReturn($permission2, $permission3);

    $role1 = new \Codefy\Framework\Auth\Rbac\Entity\RbacRole(
        roleName: 'admin',
        description: 'Super administrator.',
        rbacStorageCollection: $resource
    );

    $role1->addPermission($permission2);
    $role1->addPermission($permission3);

    Assert::assertEquals(
        [
        'perm:perm2' => $permission2,
        'perm:perm3' => $permission3
        ],
        $role1->getPermissions()
    );

    $role1->removePermission(permissionName: 'perm:perm2');

    Assert::assertEquals(['perm:perm3' => $permission3], $role1->getPermissions());
});
