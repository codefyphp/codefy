<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Trait;

use Psr\Http\Message\ServerRequestInterface;
use Qubus\Error\Handlers\Psr3ErrorHandler;
use Qubus\Exception\Exception;
use Qubus\Http\Status;

trait HttpExceptionUtilityAware
{
    protected function normalizeStatusCode(int $code): int
    {
        return ($code >= 400 && $code <= 599)
        ? $code
        : Status::INTERNAL_SERVER_ERROR;
    }

    protected function isJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');

        if ($accept === '') {
            return false;
        }

        return str_contains($accept, 'application/json')
        || str_contains($accept, 'application/*+json')
        || $accept === '*/*';
    }

    protected function getSafeReferrer(ServerRequestInterface $request): string
    {
        return $this->safeRedirectUri($request, $request->getHeaderLine('Referer'));
    }

    protected function safeRedirectUri(ServerRequestInterface $request, string $target): string
    {
        // Use local absolute paths only. Reject browser URL normalization ambiguities.
        if (
            !str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || preg_match('/[\\\\\\x00-\\x20\\x7f]/', rawurldecode($target)) === 1
            || str_starts_with(rawurldecode($target), '//')
        ) {
            return '/';
        }

        return $target === $request->getUri()->getPath() ? '/' : $target;
    }

    /**
     * @throws Exception
     */
    protected function publicErrorMessage(\Throwable $exception): string
    {
        $http = $exception instanceof \Qubus\Exception\Http\HttpException
        || $exception instanceof \Qubus\Exception\Http\Psr7Exception;
        return $this->app->hasDebugModeEnabled()
        || ($http && $exception->getCode() >= 400 && $exception->getCode() < 500)
        ? $exception->getMessage()
        : 'Internal Server Error.';
    }

    /**
     * @throws Exception
     */
    protected function shouldRenderView(): bool
    {
        return (bool) $this->app->configContainer->getConfigKey(key: 'view.error_view');
    }

    /**
     * @throws \ReflectionException
     */
    protected function logException(\Throwable $t): void
    {
        new Psr3ErrorHandler($this->app->getLogger())
            ->handle($t);
    }
}
