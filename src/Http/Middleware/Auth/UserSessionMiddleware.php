<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Http\Middleware\Csrf\InvalidTokenException;
use Codefy\Framework\Http\Status;
use Codefy\Framework\Traits\TokenEncryptionAware;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Cookies\CookiesRequest;
use Qubus\Http\Cookies\CookiesResponse;
use Qubus\Http\Cookies\Factory\HttpCookieFactory;

use function is_string;
use function Qubus\Support\Helpers\is_null__;

final class UserSessionMiddleware implements MiddlewareInterface
{
    use TokenEncryptionAware;

    public const string SESSION_ATTRIBUTE = 'USERSESSION';

    public function __construct(protected ConfigContainer $configContainer, protected HttpCookieFactory $cookie)
    {
    }

    /**
     * @throws TypeException
     * @throws Exception
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userDetails = $this->userDetails($request);
        $response = $handler->handle($request->withAttribute(self::SESSION_ATTRIBUTE, $userDetails));

        return $this->createCookie($request, $response, $userDetails->token);
    }

    /**
     * The cookie expiry.
     *
     * @throws Exception
     */
    protected function cookieTtl(ServerRequestInterface $request): int
    {
        $ttl = isset($request->getParsedBody()['rememberme'])
        && $request->getParsedBody()['rememberme'] === 'yes'
        ? $this->configContainer->getConfigKey(key: 'cookies.remember')
        : $this->configContainer->getConfigKey(key: 'cookies.lifetime');

        return (int) $ttl;
    }

    /**
     * The cookie name.
     *
     * @return string
     * @throws Exception
     */
    protected function cookieName(): string
    {
        return $this->configContainer->getConfigKey(key: 'auth.cookie_name', default: 'USERSESSID');
    }

    /**
     * User details from request.
     *
     * @param ServerRequestInterface $request
     * @return mixed
     */
    protected function userDetails(ServerRequestInterface $request): mixed
    {
        return $request->getAttribute(AuthenticationMiddleware::AUTH_ATTRIBUTE);
    }

    /**
     * Creates an HTTP cookie.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $token
     * @return ResponseInterface
     * @throws Exception
     * @throws TypeException
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    protected function createCookie(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $token
    ): ResponseInterface {
        if (isset($request->getCookieParams()[$this->cookieName()]) && false === $this->tokensMatch($request)) {
            return $response;
        }

        if (false === $this->isNew($request)) {
            return $response;
        }

        $signed = $this->sign($token);

        return CookiesResponse::set(
            response: $response,
            setCookieCollection: $this->cookie->make(
                name: $this->cookieName(),
                value: $signed,
                maxAge: $this->cookieTtl($request)
            )
        );
    }

    /**
     * @throws Exception
     */
    public function isNew(ServerRequestInterface $request): bool
    {
        $name = $this->configContainer->getConfigKey(key: 'auth.cookie_name', default: 'USERSESSID');
        $cookie = CookiesRequest::get($request, $name);
        if (is_null__($cookie->getValue()) || '' === $cookie->getValue()) {
            return true;
        }

        return false;
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
     * @throws Exception
     * @throws WrongKeyOrModifiedCiphertextException
     */
    private function getTokenFromCookie(array $cookies): ?string
    {
        $name = $this->configContainer->getConfigKey(key: 'auth.cookie_name', default: 'USERSESSID');
        $value = $cookies[$name] ?? '';

        return '' === $value ? null : $this->unsign($value);
    }

    /**
     * @throws Exception
     */
    private function tokensMatch(ServerRequestInterface $request): bool
    {
        $expected = $this->fetchToken($request);
        $provided = $this->getTokenFromCookie($request->getCookieParams() ?? []);

        return $this->compareTokens($expected, $provided);
    }


    /**
     * @throws Exception
     */
    private function fetchToken(ServerRequestInterface $request): string
    {
        $userDetails = $this->userDetails($request);

        if (is_string($userDetails->token)) {
            return $userDetails->token;
        }

        throw new InvalidTokenException(
            uri: $request->getServerParams()['HTTP_REFERER'],
            message: 'User token is missing or invalid.',
            code: Status::FORBIDDEN
        );
    }
}
