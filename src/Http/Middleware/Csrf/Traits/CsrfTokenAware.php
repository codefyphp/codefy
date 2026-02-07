<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Csrf\Traits;

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\CookiesResponse;

use function sha1;
use function uniqid;

trait CsrfTokenAware
{
    //phpcs:disable
    protected ?string $salt = null
    {
        get => $this->salt ?? $this->configContainer->getConfigKey(key: 'csrf.salt');
        set(null|string $salt) => $this->salt = $salt;
    }
    //phpcs:enable

    protected bool $isNew = false;

    protected function generateToken(): string
    {
        return sha1(string: uniqid(prefix: sha1(string: $this->salt), more_entropy: true));
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws \Exception
     * @throws WrongKeyOrModifiedCiphertextException
     */
    protected function prepareToken(ServerRequestInterface $request): string
    {
        // Try to retrieve an existing token from the cookie request.
        $token = $this->getTokenFromCookie($request->getCookieParams());

        // If token isn't present in the session, we generate a new token.
        if ($token === null) {
            $this->isNew = true;
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
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws \Exception
     * @throws WrongKeyOrModifiedCiphertextException
     */
    private function getTokenFromCookie(array $cookies): ?string
    {
        $name = $this->configContainer->getConfigKey(key: 'csrf.cookie_name', default: 'CSRFSESSID');
        $value = $cookies[$name] ?? '';

        return '' === $value ? null : $this->unsign($value);
    }

    /**
     * Create CSRF cookie to store the encrypted token value.
     *
     * Encrypt the value for better security (in case of XSS attack).
     *
     * @param ResponseInterface $response
     * @param string $token
     * @return ResponseInterface
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws \Exception
     * @throws TypeException
     */
    private function createCookie(ResponseInterface $response, string $token): ResponseInterface
    {
        $name = $this->configContainer->getConfigKey(key: 'csrf.cookie_name', default: 'CSRFSESSID');
        $expires = (int) $this->configContainer->getConfigKey(key: 'csrf.lifetime', default: 86400);
        $signed = $this->sign($token);

        if (false === $this->isNew) {
            return $response;
        }

        return CookiesResponse::set(
            response: $response,
            setCookieCollection: $this->cookie->make(
                name: $name,
                value: $signed,
                maxAge: $expires
            )
        );
    }
}
