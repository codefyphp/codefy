<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Http\Middleware\Csrf\InvalidTokenException;
use Codefy\Framework\Traits\TokenEncryptionAware;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Factories\RedirectResponseFactory;
use Qubus\Http\Status;

use function is_string;
use function Qubus\Support\Helpers\is_false__;

class UserAuthorizationMiddleware implements MiddlewareInterface
{
    use TokenEncryptionAware;

    public const string HEADER_HTTP_STATUS_CODE = 'AUTH_STATUS_CODE';

    public function __construct(
        protected ConfigContainer $configContainer,
        protected ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_false__($this->isLoggedIn($request))) {
            return RedirectResponseFactory::create(
                $this->configContainer->getConfigKey(key: 'auth.redirect_guests_to')
            )->withAddedHeader(self::HEADER_HTTP_STATUS_CODE, 'not_authorized');
        }

        return $handler->handle($request);
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
     * Get the token from the request cookie if it's present.
     * Decrypt the cookie token value using the app crypto key.
     *
     * Return null if the cookie is missing or if the decryption fails.
     *
     * @param array<array-key, mixed> $cookies
     * @return string|null
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws \Exception
     * @throws WrongKeyOrModifiedCiphertextException
     */
    private function getTokenFromCookie(array $cookies): ?string
    {
        $name = $this->configContainer->getConfigKey(key: 'auth.cookie_name', default: 'USERSESSID');
        $value = $cookies[$name] ?? '';

        return '' === $value ? null : $this->unsign($value);
    }

    /**
     * @throws \Exception
     */
    private function tokensMatch(ServerRequestInterface $request): bool
    {
        $expected = $this->fetchToken($request);
        $provided = $this->getTokenFromCookie($request->getCookieParams());

        return $this->compareTokens($expected, $provided);
    }


    /**
     * @throws \Exception
     */
    private function fetchToken(ServerRequestInterface $request): string
    {
        $userDetails = $this->userDetails($request);

        if (is_string($userDetails->token)) {
            return $userDetails->token;
        }

        throw new InvalidTokenException(
            uri: $request->getHeaderLine('Referer'),
            message: 'User token is missing or invalid.',
            code: Status::FORBIDDEN
        );
    }

    /**
     * @throws \Exception
     */
    private function isLoggedIn(ServerRequestInterface $request): bool
    {
        if ($this->tokensMatch($request)) {
            return true;
        }

        return false;
    }
}
