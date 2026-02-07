<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Trait;

use Codefy\Framework\View\ErrorViewRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\Psr7Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Factories\RedirectResponseFactory;

use function Qubus\Security\Helpers\esc_html;

trait HttpExceptionRenderAware
{
    protected ErrorViewRenderer $errorView;

    /**
     * @throws NotFoundException
     * @throws \Exception
     */
    protected function renderErrorView(\Throwable $t): ResponseInterface
    {
        $statusCode = $this->normalizeStatusCode((int) $t->getCode());
        $html = $this->errorView->render($statusCode);

        return $this->errorView->toResponse($html);
    }

    /**
     * @throws \Exception
     */
    protected function redirectWithHttpError(
        ServerRequestInterface $request,
        HttpException|Psr7Exception $e
    ): ResponseInterface {
        $this->app->flash->error(esc_html($e->getMessage()));

        return RedirectResponseFactory::create(
            uri: $e->getUri() ?: $this->getSafeReferrer($request)
        );
    }

    /**
     * @throws \Exception
     */
    protected function jsonHttpErrorResponse(Psr7Exception $e): ResponseInterface
    {
        $status = $this->normalizeStatusCode($e->getCode());

        return JsonResponseFactory::create(data: [
            'error' => 'http_error',
            'message' => esc_html($e->getMessage()),
        ], status: $status);
    }

    protected function redirectWithInternalError(ServerRequestInterface $request): ResponseInterface
    {
        $this->app->flash->error(message: 'Internal Server Error.');

        return RedirectResponseFactory::create(
            uri: $this->getSafeReferrer($request)
        );
    }

    /**
     * @throws \Exception
     */
    protected function jsonInternalErrorResponse(\Throwable $t): ResponseInterface
    {
        $status = $this->normalizeStatusCode($t->getCode());

        return JsonResponseFactory::create(data: [
            'error' => 'server_error',
            'message' => 'An internal error occurred.'
        ], status: $status);
    }
}
