<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Spam;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Http\Factories\JsonResponseFactory;

use function in_array;
use function Qubus\Security\Helpers\esc_attr;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;
use function strtoupper;

class HoneyPotMiddleware implements MiddlewareInterface
{
    private static HoneyPotMiddleware $current;

    private string $attrName;

    public function __construct(string $attrName = 'hpt_name')
    {
        $this->attrName = $attrName;
        self::$current = $this;
    }

    public static function getField(?string $name = null, ?string $label = null): string
    {
        $label = is_null__(var: $label) ? 'Honeypot Captcha' : esc_attr(string: $label);

        return sprintf(
            '<input type="text" name="%s" aria-label="%s">' . "\n",
            $name ?? self::$current->attrName,
            $label
        );
    }

    public static function getHiddenField(?string $name = null): string
    {
        return sprintf(
            '<input type="text" name="%s">' . "\n",
            $name ?? self::$current->attrName,
        );
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isValid($request)) {
            return JsonResponseFactory::create(
                data: 'Form submission error.',
                status: 403
            );
        }

        return $handler->handle($request);
    }

    private function isValid(ServerRequestInterface $request): bool
    {
        $method = strtoupper(string: $request->getMethod());

        if (in_array(needle: $method, haystack: ['GET', 'HEAD', 'CONNECT', 'TRACE', 'OPTIONS'], strict: true)) {
            return true;
        }

        $data = $request->getParsedBody();

        return isset($data[$this->attrName]) && $data[$this->attrName] === '';
    }
}
