<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Rbac\Entity;

interface AssertionRule
{
    /**
     * @param array|null $params
     * @return bool
     */
    public function execute(?array $params): bool;
}
