<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Factory\FileLoggerFactory;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

class UserCookieDecryptMiddleware implements MiddlewareInterface
{
    public const string USER_COOKIE = 'auth.token';

    public function __construct(
        private readonly ConfigContainer $configContainer,
    ) {
    }

    /**
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookieName = $this->configContainer->getConfigKey('auth.cookie_name', 'USERSESSID');
        $encrypted  = $request->getCookieParams()[$cookieName] ?? null;

        if ($encrypted === null || $encrypted === '') {
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
     * Decrypt the cookie safely. Return null on any failure.
     *
     * @throws \ReflectionException
     */
    private function decryptCookie(string $encrypted): ?string
    {
        try {
            $keyString = $this->configContainer->getConfigKey(key: 'app.crypto_key');
            $key = Key::loadFromAsciiSafeString($keyString);

            return Crypto::decrypt($encrypted, $key);
        } catch (\Throwable $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            return null;
        }
    }
}
