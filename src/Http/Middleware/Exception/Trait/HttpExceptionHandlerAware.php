<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception\Trait;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\Psr7Exception;
use ReflectionException;
use Throwable;

trait HttpExceptionHandlerAware
{
    /**
     * @throws ReflectionException
     * @throws TypeException
     * @throws Exception
     */
    protected function handleHttpException(
        HttpException|Psr7Exception $e,
        ServerRequestInterface $request
    ): ResponseInterface {
        $this->logException($e);

        if ($this->isJson($request)) {
            return $this->jsonHttpErrorResponse($e);
        }

        return $this->shouldRenderView()
        ? $this->renderErrorView($e)
        : $this->redirectWithHttpError($request, $e);
    }

    /**
     * @throws NotFoundException
     * @throws ReflectionException
     * @throws TypeException
     * @throws Exception
     */
    protected function handleUnknownException(Throwable $t, ServerRequestInterface $request): ResponseInterface
    {
        $this->logException($t);

        if ($this->isJson($request)) {
            return $this->jsonInternalErrorResponse($t);
        }

        return $this->shouldRenderView()
        ? $this->renderErrorView($t)
        : $this->redirectWithInternalError($request);
    }
}
