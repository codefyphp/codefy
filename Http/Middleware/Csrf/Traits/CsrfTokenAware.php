<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Csrf\Traits;

use Qubus\Exception\Exception;
use Qubus\Http\Session\HttpSession;

use function hash_equals;
use function hash_hmac;
use function sha1;
use function uniqid;

trait CsrfTokenAware
{
    /**
     * @throws Exception
     */
    protected function generateToken(): string
    {
        $salt = $this->configContainer->getConfigKey(key: 'csrf.salt');

        return sha1(string: uniqid(prefix: sha1(string: $salt), more_entropy: true));
    }

    /**
     * @throws Exception
     */
    protected function prepareToken(HttpSession $session): string
    {
        // Try to retrieve an existing token from the session.
        $token = $session->clientSessionId();

        // If token isn't present in the session, we generate a new token.
        if ($token === '') {
            $token = $this->generateToken();
        }
        return hash_hmac(algo: 'sha256', data: $token, key: $this->configContainer->getConfigKey(key: 'csrf.salt'));
    }

    /**
     * @throws Exception
     */
    protected function hashEquals(string $knownString, string $userString): bool
    {
        return hash_equals(
            $knownString,
            hash_hmac(
                algo: 'sha256',
                data: $userString,
                key: $this->configContainer->getConfigKey(key: 'csrf.salt')
            )
        );
    }
}
