<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Csrf\Traits;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Exception;
use Qubus\Http\Cookies\CookiesResponse;

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
     * @param ServerRequestInterface $request
     * @return string
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     * @throws WrongKeyOrModifiedCiphertextException
     */
    protected function prepareToken(ServerRequestInterface $request): string
    {
        // Try to retrieve an existing token from the cookie request.
        $token = $this->getTokenFromCookie($request->getCookieParams());

        // If token isn't present in the session, we generate a new token.
        if ($token === '') {
            $token = $this->generateToken();
        }

        return $token;
    }

    /**
     * Get the token from the request cookie if it's present.
     * Decrypt the cookie token value using the app crypto key.
     *
     * Return null if the cookie is missing or if the decryption fails.
     *
     * @param array $cookies
     * @return string|null
     * @throws Exception
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    private function getTokenFromCookie(array $cookies): ?string
    {
        $key = Key::loadFromAsciiSafeString($this->configContainer->getConfigKey(key: 'app.crypto_key'));
        $name = $this->configContainer->getConfigKey(key: 'csrf.cookie_name', default: 'CSRFSESSID');
        $value = $cookies[$name] ?? '';

        return Crypto::decrypt(ciphertext: $value, key: $key);
    }

    /**
     * Create CSRF cookie to store the encrypted token value.
     *
     * Encrypt the value for better security (in case of XSS attack).
     *
     * @param ResponseInterface $response
     * @param string $token
     * @return ResponseInterface
     * @throws Exception
     * @throws EnvironmentIsBrokenException
     * @throws BadFormatException
     */
    private function createCookie(ResponseInterface $response, string $token): ResponseInterface
    {
        $key = Key::loadFromAsciiSafeString($this->configContainer->getConfigKey(key: 'app.crypto_key'));
        $name = $this->configContainer->getConfigKey(key: 'csrf.cookie_name', default: 'CSRFSESSID');
        $value = Crypto::encrypt(plaintext: $token, key: $key);
        $expires = (int) $this->configContainer->getConfigKey(key: 'csrf.lifetime', default: 86400);

        return CookiesResponse::set(
            response: $response,
            setCookieCollection: $this->cookie->make(
                name: $name,
                value: $value,
                maxAge: $expires
            )
        );
    }
}
