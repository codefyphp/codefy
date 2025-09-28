<?php

declare(strict_types=1);

namespace Codefy\Framework\Auth\Repository;

use Qubus\Http\Session\SessionEntity;
use SensitiveParameter;

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
}
