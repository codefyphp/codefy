<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Repository;

use Qubus\Http\Session\SessionEntity;
use SensitiveParameter;
use stdClass;

interface AuthUserRepository
{
    /**
     * Authenticate with a user's credential
     * (email, username, or login token)
     * along with a password.
     *
     * @param string $credential
     * @param string|null $password
     * @return SessionEntity|null
     */
    public function authenticate(string $credential, #[SensitiveParameter] ?string $password = null): ?SessionEntity;

    /**
     * Find a user by their token.
     *
     * @param string $token
     * @return bool|object|null
     */
    public function find(string $token): bool|null|object;
}
