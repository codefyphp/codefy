<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Csrf;

use Codefy\Framework\Http\Middleware\Csrf\Traits\CsrfTokenAware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionService;

use function sprintf;

class CsrfTokenMiddleware implements MiddlewareInterface
{
    use CsrfTokenAware;

    public const string SESSION_ATTRIBUTE = 'CSRF_TOKEN';

    public static CsrfTokenMiddleware $current;

    private string $token;

    public function __construct(protected ConfigContainer $configContainer, protected SessionService $sessionService)
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
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->sessionService::$options = [
                'cookie-name' => 'CSRFSESSID',
                'cookie-lifetime' => (int) $this->configContainer->getConfigKey(key: 'csrf.lifetime', default: 86400),
            ];

            $session = $this->sessionService->makeSession($request);

            $this->token = $this->prepareToken(session: $session);

            /**
             * If true, the application will do a header check, if not,
             * it will expect data submitted via an HTML form tag.
             */
            if ($this->configContainer->getConfigKey(key: 'csrf.request_header') === true) {
                $request = $request->withHeader($this->configContainer->getConfigKey(key: 'csrf.header'), $this->token);
            }

            $response = $handler->handle(
                $request
                    ->withAttribute(self::SESSION_ATTRIBUTE, $this->token)
            );

            /** @var CsrfSession $csrf */
            $csrf = $session->get(CsrfSession::class);
            $csrf->withCsrfToken($this->token);

            return $this->sessionService->commitSession($response, $session);
        } catch (\Exception $e) {
            return $handler->handle($request);
        }
    }
}
