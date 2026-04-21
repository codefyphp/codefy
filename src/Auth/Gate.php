<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth;

use Qubus\Exception\Data\TypeException;

interface Gate
{
    /**
     * Authorization check.
     *
     * @param string $permissionName
     * @param array<array-key, mixed> $ruleParams
     * @return bool
     * @throws \ReflectionException
     * @throws TypeException
     */
    public function can(string $permissionName, array $ruleParams = []): bool;

    /**
     * Get the current authenticated user model.
     *
     * @return object|bool|null
     * @throws \ReflectionException
     */
    public function current(): object|bool|null;

    /**
     * A guest is any user without an authenticated token.
     */
    public function guest(): bool;

    /**
     * Whether user is logged in.
     *
     * @return bool
     */
    public function isLoggedIn(): bool;
}
