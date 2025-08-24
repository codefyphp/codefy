<?php

declare(strict_types=1);

use Codefy\Framework\Auth\Rbac\Entity\Permission;
use Codefy\Framework\Auth\Rbac\Entity\Role;
use Codefy\Framework\Auth\Rbac\Rbac;
use Codefy\Framework\Auth\Rbac\Resource\FileResource;
use PHPUnit\Framework\Assert;

$resource = new FileResource(file: 'rbac.json');

it('should create instance.', function () use ($resource) {
    new Rbac($resource);

    Assert::assertTrue(condition: true);
});

it('should create role and return Role instance.', function () use ($resource) {
    $rbac = new Rbac($resource);

    Assert::assertInstanceOf(
        expected: Role::class,
        actual: $rbac->addRole(name: 'admin', description: 'Administrator')
    );
});

it('should create Permission and return Permission instance.', function () use ($resource) {
    $rbac = new Rbac($resource);

    Assert::assertInstanceOf(
        Permission::class,
        $rbac->addPermission(name: 'dashboard:view', description: 'Access dashboard.')
    );
});

it('should get empty array for roles.', function () use ($resource) {
    $rbac = new Rbac($resource);

    Assert::assertEquals([], $rbac->roles);
});

it('should get role.', function () use ($resource) {
    $rbac = new Rbac($resource);
    $perm1 = $rbac->addPermission(name: 'create:post', description: 'Can create posts');
    $perm2 = $rbac->addPermission(name: 'moderate:post', description: 'Can moderate posts');
    $perm3 = $rbac->addPermission(name: 'update:post', description: 'Can update posts');
    $perm4 = $rbac->addPermission(name: 'delete:post', description: 'Can delete posts');
    $perm2->addChild($perm3);
    $perm2->addChild($perm4);

    $adminRole = $rbac->addRole(name: 'admin');
    $moderatorRole = $rbac->addRole(name: 'moderator');
    $authorRole = $rbac->addRole(name: 'author');
    $adminRole->addChild($moderatorRole);

    Assert::assertInstanceOf(Role::class, $rbac->getRole(name: 'admin'));
});

it('should get empty array for permissions.', function () use ($resource) {
    $rbac = new Rbac($resource);

    Assert::assertEquals(expected: [], actual: $rbac->permissions);
});

it('should get permission.', function () use ($resource) {
    $rbac = new Rbac($resource);
    $perm1 = $rbac->addPermission(name: 'create:post', description: 'Can create posts');

    Assert::assertInstanceOf(expected: Permission::class, actual: $rbac->getPermission(name: 'create:post'));
});
