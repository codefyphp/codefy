<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Csrf;

use Codefy\Framework\Http\Middleware\Csrf\Traits\CsrfTokenAware;
use Codefy\Framework\Traits\TokenEncryptionAware;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Http\Cookies\Factory\HttpCookieFactory;

use function sprintf;

class CsrfTokenMiddleware implements MiddlewareInterface
{
    use CsrfTokenAware;
    use TokenEncryptionAware;

    public const string CSRF_SESSION_ATTRIBUTE = 'CSRF_TOKEN';

    public static CsrfTokenMiddleware $current;

    private ?string $token = null;

    public function __construct(protected ConfigContainer $configContainer, public readonly HttpCookieFactory $cookie,)
    {
        self::$current = $this;
    }

    /**
     * @throws Exception
     */
    public static function getField(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">' . "\n",
            self::$current->getFieldAttr(),
            self::$current->token
        );
    }

    /**
     * @throws Exception
     */
    public function getFieldAttr(): string
    {
        return $this->configContainer->getConfigKey(key: 'csrf.csrf_token', default: '_token');
    }

    /**
     * @inheritDoc
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Retrieve an existing token from the cookie or generate a new one. Plaintext.
        $this->token = $this->prepareToken($request);

        $response = $handler->handle(
            $request
                ->withAttribute(self::CSRF_SESSION_ATTRIBUTE, $this->token)
        );

        // Attach/Refresh the token cookie for the "next" request call. Will get encrypted.
        return $this->createCookie($response, $this->token);
    }
}
