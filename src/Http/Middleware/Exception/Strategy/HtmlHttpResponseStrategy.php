<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Strategy;

use Codefy\Framework\Application;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionRenderAware;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionUtilityAware;
use Codefy\Framework\View\ErrorViewRenderer;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Http\Client\NotFoundException;
use Throwable;

class HtmlHttpResponseStrategy implements HttpResponseStrategy
{
    use HttpExceptionUtilityAware;
    use HttpExceptionRenderAware;

    public function __construct(protected ErrorViewRenderer $errorView, protected Application $app)
    {
    }

    public function supports(Throwable $e, ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');

        return str_contains($accept, 'text/html')
        || str_contains($accept, '*/*')
        || $accept === '';
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    public function createResponse(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        return $this->renderErrorView($e);
    }
}
