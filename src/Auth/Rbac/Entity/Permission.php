<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

interface Permission
{
    //phpcs:disable
    public string $name { get; }

    public string $description { get; }
    //phpcs:enable

    /**
     * @param Permission $permission
     */
    public function addChild(Permission $permission): void;

    /**
     * @param string $permissionName
     */
    public function removeChild(string $permissionName): void;

    /**
     * @return array<Permission>
     */
    public function getChildren(): array;

    /**
     * @param class-string|string $ruleClass
     */
    public function setRuleClass(string $ruleClass): void;

    /**
     * @return string|null
     */
    public function getRuleClass(): ?string;

    /**
     * @param array<array-key, mixed>|null $params
     * @return bool
     */
    public function checkAccess(?array $params = null): bool;
}
