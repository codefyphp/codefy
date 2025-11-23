<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Trait;

use Psr\Http\Message\ServerRequestInterface;
use Qubus\Error\Handlers\Psr3ErrorHandler;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Status;
use ReflectionException;
use Throwable;

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
        $ref = $request->getHeaderLine('Referer');
        // avoid redirect loop
        return ($ref === $request->getUri()->getPath()) ? '/' : $ref;
    }

    /**
     * @throws Exception
     */
    protected function shouldRenderView(): bool
    {
        return (bool) $this->app->configContainer->getConfigKey(key: 'view.error_view');
    }

    /**
     * @throws ReflectionException
     * @throws TypeException
     */
    protected function logException(Throwable $t): void
    {
        new Psr3ErrorHandler($this->app->getLogger())
            ->handle($t);
    }
}
