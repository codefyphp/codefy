<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Csrf;

use Codefy\Framework\Http\Middleware\Csrf\Traits\CsrfTokenAware;
use Codefy\Framework\Support\RequestMethod;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Http\Session\SessionService;

use function hash_equals;
use function is_array;
use function is_string;
use function strlen;

class CsrfProtectionMiddleware implements MiddlewareInterface
{
    use CsrfTokenAware;

    public function __construct(protected ConfigContainer $configContainer, protected SessionService $sessionService)
    {
    }

    /**
     * @inheritDoc
     * @throws TokenMismatchException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (true === $this->needsProtection($request) && false === $this->tokensMatch($request)) {
            throw new TokenMismatchException(
                uri: $request->getServerParams()['HTTP_REFERER'],
                message: 'Bad CSRF token.',
                code: 412
            );
        }

        return $handler->handle($request);
    }

    /**
     * Check for methods not defined as safe.
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function needsProtection(ServerRequestInterface $request): bool
    {
        return RequestMethod::isSafe($request->getMethod()) === false;
    }

    /**
     * @throws Exception
     */
    private function tokensMatch(ServerRequestInterface $request): bool
    {
        $expected = $this->fetchToken($request);
        $provided = $this->getTokenFromRequest($request);

        return hash_equals($expected, $provided);
    }


    /**
     * @throws Exception
     */
    private function fetchToken(ServerRequestInterface $request): string
    {
        $token = $request->getAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE);

        // Ensure the token stored previously by the CsrfTokenMiddleware is present and has a valid format.
        if (
                is_string($token) &&
                ctype_alnum($token) &&
                strlen($token) === $this->configContainer->getConfigKey(key: 'csrf.csrf_token_length')
        ) {
            return $token;
        }

        throw new InvalidTokenException(
            uri: $request->getServerParams()['HTTP_REFERER'],
            message: 'Missing or invalid CSRF token.',
            code: 403
        );
    }

    /**
     * @throws Exception
     */
    private function getTokenFromRequest(ServerRequestInterface $request): string
    {
        if ($request->hasHeader($this->configContainer->getConfigKey(key: 'csrf.header'))) {
            return (string) $request->getHeaderLine($this->configContainer->getConfigKey(key: 'csrf.header'));
        }

        // Handle the case for a POST form.
        $body = $request->getParsedBody();

        if (
                is_array(
                    $body
                ) &&
                isset($body[$this->configContainer->getConfigKey(key: 'csrf.csrf_token')]) &&
                is_string($body[$this->configContainer->getConfigKey(key: 'csrf.csrf_token')])
        ) {
            return $body[$this->configContainer->getConfigKey(key: 'csrf.csrf_token')];
        }

        return '';
    }
}
