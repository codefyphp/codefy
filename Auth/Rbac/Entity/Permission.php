<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

interface Permission
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
     * @param Permission $permission
     */
    public function addChild(Permission $permission);

    /**
     * @param string $permissionName
     */
    public function removeChild(string $permissionName);

    /**
     * @return Permission[]
     */
    public function getChildren(): array;

    /**
     * @param string $ruleClass
     */
    public function setRuleClass(string $ruleClass);

    /**
     * @return string|null;
     */
    public function getRuleClass(): ?string;

    /**
     * @param array|null $params
     * @return bool
     */
    public function checkAccess(array $params = null): bool;
}
