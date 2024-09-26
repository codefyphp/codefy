<?php

declare(strict_types=1);

use Codefy\Framework\Auth\Rbac\Entity\Permission;
use Codefy\Framework\Auth\Rbac\Entity\Role;
use Codefy\Framework\Auth\Rbac\Rbac;
use PHPUnit\Framework\Assert;

$resource = Mockery::mock(\Codefy\Framework\Auth\Rbac\Resource\StorageResource::class);

it('should create instance.', function () use ($resource) {
    $resource->shouldReceive('load');

    new Rbac($resource);

    Assert::assertTrue(true);
});

it('should create role and return Role instance.', function () use ($resource) {
    $resource->shouldReceive('load');
    $resource->shouldReceive('addRole')
            ->with('role1', 'desc1')
            ->andReturn(Mockery::mock(Role::class));

    $rbac = new Rbac($resource);

    Assert::assertInstanceOf(Role::class, $rbac->addRole(name: 'role1', description: 'desc1'));
});

it('should create Permission and return Permission instance.', function () use ($resource) {
    $resource->shouldReceive('load');
    $resource->shouldReceive('addPermission')
            ->with('dashboard:view', 'Access dashboard.')
            ->andReturn(Mockery::mock(Permission::class));

    $rbac = new Rbac($resource);

    Assert::assertInstanceOf(
        Permission::class,
        $rbac->addPermission(name: 'dashboard:view', description: 'Access dashboard.')
    );
});

it('should get roles.', function () use ($resource) {
    $resource->shouldReceive('load');
    $resource->shouldReceive('getRoles')
            ->andReturn([]);

    $rbac = new Rbac($resource);

    Assert::assertEquals([], $rbac->getRoles());
});

it('should get role.', function () use ($resource) {
    $resource->shouldReceive('load');
    $resource->shouldReceive('getRole')
            ->with('role1')
            ->andReturn(Mockery::mock(Role::class));

    $rbac = new Rbac($resource);

    Assert::assertInstanceOf(Role::class, $rbac->getRole('role1'));
});

it('should get permissions.', function () use ($resource) {
    $resource->shouldReceive('load');
    $resource->shouldReceive('getPermissions')
            ->andReturn([]);

    $rbac = new Rbac($resource);

    Assert::assertEquals([], $rbac->getPermissions());
});

it('should get permission.', function () use ($resource) {
    $resource->shouldReceive('load');
    $resource->shouldReceive('getPermission')
            ->with('perm1')
            ->andReturn(Mockery::mock(Permission::class));

    $rbac = new Rbac($resource);

    Assert::assertInstanceOf(Permission::class, $rbac->getPermission(name: 'perm1'));
});
