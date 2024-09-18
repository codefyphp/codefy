<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

use Codefy\Framework\Auth\Rbac\Exception\SentinelException;
use Codefy\Framework\Auth\Rbac\Resource\StorageResource;
use Throwable;

use function array_keys;
use function sprintf;

class RbacPermission implements Permission
{
    protected array $childrenNames = [];
    protected ?string $ruleClass = '';

    /**
     * @param string $permissionName
     * @param string $description
     * @param StorageResource $rbacStorageCollection
     */
    public function __construct(
        protected string $permissionName,
        protected string $description,
        protected StorageResource $rbacStorageCollection
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->permissionName;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param Permission $permission
     */
    public function addChild(Permission $permission): void
    {
        $this->childrenNames[$permission->getName()] = true;
    }

    /**
     * @param string $permissionName
     */
    public function removeChild(string $permissionName): void
    {
        unset($this->childrenNames[$permissionName]);
    }

    /**
     * @return Permission[]
     */
    public function getChildren(): array
    {
        $result = [];
        $permissionNames = array_keys(array: $this->childrenNames);
        foreach ($permissionNames as $name) {
            $result[$name] = $this->rbacStorageCollection->getPermission(name: $name);
        }
        return $result;
    }

    /**
     * @param string $ruleClass
     */
    public function setRuleClass(string $ruleClass): void
    {
        $this->ruleClass = $ruleClass;
    }

    /**
     * @return string|null;
     */
    public function getRuleClass(): ?string
    {
        return $this->ruleClass;
    }

    /**
     * @param array|null $params
     * @return bool
     * @throws SentinelException
     */
    public function checkAccess(?array $params = null): bool
    {
        $result = true;
        if ($ruleClass = $this->getRuleClass()) {
            try {
                $rule = new $ruleClass();
                if (!$rule instanceof AssertionRule) {
                    throw new SentinelException(
                        sprintf(
                            'Rule class: %s is not an instance of %s.',
                            $rule,
                            AssertionRule::class
                        )
                    );
                }

                $result = $rule->execute(params: $params);
            } catch (Throwable $e) {
                throw new SentinelException(sprintf('Cannot instantiate rule class: %s.', $ruleClass));
            }
        }
        return $result;
    }
}
