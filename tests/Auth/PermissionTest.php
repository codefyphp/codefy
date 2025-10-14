<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

$resource = Mockery::mock(\Codefy\Framework\Auth\Rbac\Resource\StorageResource::class);

it(description: 'should get the permission name.', closure: function () use ($resource) {
    $permission = new \Codefy\Framework\Auth\Rbac\Entity\RbacPermission(
        name: 'admin:edit',
        description: 'Edit permission.',
        rbacStorageCollection: $resource
    );

    Assert::assertEquals('admin:edit', $permission->name);
});

it(description: 'should get permission description.', closure: function () use ($resource) {
    $permission = new \Codefy\Framework\Auth\Rbac\Entity\RbacPermission(
        name: 'admin:edit',
        description: 'Edit permission.',
        rbacStorageCollection: $resource
    );

    Assert::assertEquals('Edit permission.', $permission->description);
});

it(description: 'should get children.', closure: function () use ($resource) {
    $permission2 = new \Codefy\Framework\Auth\Rbac\Entity\RbacPermission(
        name: 'perm:perm2',
        description: 'desc2',
        rbacStorageCollection: $resource
    );

    $permission3 = new \Codefy\Framework\Auth\Rbac\Entity\RbacPermission(
        name: 'perm:perm3',
        description: 'desc3',
        rbacStorageCollection: $resource
    );

    $resource = Mockery::mock(\Codefy\Framework\Auth\Rbac\Resource\StorageResource::class);
    $resource->shouldReceive('getPermission')->andReturn($permission2, $permission3);

    $permission1 = new \Codefy\Framework\Auth\Rbac\Entity\RbacPermission(
        name: 'perm:perm1',
        description: 'desc1',
        rbacStorageCollection: $resource
    );

    $permission1->addChild($permission2);
    $permission1->addChild($permission3);

    Assert::assertEquals(
        [
        'perm:perm2' => $permission2,
        'perm:perm3' => $permission3
        ],
        $permission1->getChildren()
    );

    $permission1->removeChild(permissionName: 'perm:perm2');

    Assert::assertEquals(['perm:perm3' => $permission3], $permission1->getChildren());
});

it(description: 'should set and get rule.', closure: function () use ($resource) {
    $permission = new \Codefy\Framework\Auth\Rbac\Entity\RbacPermission(
        name: 'admin:edit',
        description: 'Edit permission.',
        rbacStorageCollection: $resource
    );

    $permission->setRuleClass(ruleClass: 'AuthorRule');

    Assert::assertEquals('AuthorRule', $permission->getRuleClass());
});
