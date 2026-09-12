<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;

class UserCookieDecryptMiddleware implements MiddlewareInterface
{
    use AuthTokenAware;

    public const string USER_COOKIE = 'auth.token';

    public function __construct(
        private readonly ConfigContainer $configContainer,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws TypeException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookieName = $this->configContainer->getConfigKey('auth.cookie_name', 'USERSESSID');
        $encrypted  = $request->getCookieParams()[$cookieName] ?? null;
        $request = $request->withoutAttribute(self::USER_COOKIE);

        if (!is_string($encrypted) || $encrypted === '') {
            // No cookie present; just continue the pipeline.
            return $handler->handle($request);
        }

        $token = $this->decryptCookie($encrypted);

        if ($token !== null) {
            // Attach ONLY the decrypted token — never the cookie.
            $request = $request->withAttribute(self::USER_COOKIE, $token);
        }

        return $handler->handle($request);
    }

    /**
     * Decrypt the cookie safely. Return null for invalid ciphertext; configuration errors propagate.
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    private function decryptCookie(string $encrypted): ?string
    {
        return $this->decryptAuthToken($encrypted);
    }
}
