<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

use Codefy\Framework\Auth\Rbac\Exception\SentinelException;
use Codefy\Framework\Auth\Rbac\Resource\StorageResource;

use function array_keys;
use function sprintf;

class RbacPermission implements Permission
{
    /** @var array<mixed> $childrenNames */
    protected array $childrenNames = [];
    protected ?string $ruleClass = '';

    //phpcs:disable
    /**
     * @param string $name
     * @param string $description
     * @param StorageResource $rbacStorageCollection
     */
    public function __construct(
        public private(set) string $name {
            get => $this->name;
        },
        public private(set) string $description {
            get => $this->description;
        },
        protected StorageResource $rbacStorageCollection
    ) {
    }
    //phpcs.enable

    /**
     * @inheritDoc
     */
    #[\Override]
    public function addChild(Permission $permission): void
    {
        $this->childrenNames[$permission->name] = true;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function removeChild(string $permissionName): void
    {
        unset($this->childrenNames[$permissionName]);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
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
     * @inheritDoc
     */
    #[\Override]
    public function setRuleClass(string $ruleClass): void
    {
        $this->ruleClass = $ruleClass;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getRuleClass(): ?string
    {
        return $this->ruleClass;
    }

    /**
     * @inheritDoc
     * @throws SentinelException
     */
    #[\Override]
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
                            $rule::class,
                            AssertionRule::class
                        )
                    );
                }

                $result = $rule->execute(params: $params);
            } catch (\Throwable $e) {
                throw new SentinelException(sprintf('Cannot instantiate rule class: %s.', $ruleClass));
            }
        }
        return $result;
    }
}
