<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Exception;

use Codefy\Framework\Application;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionRenderAware;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionUtilityAware;
use Codefy\Framework\View\ErrorViewRenderer;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\Psr7Exception;
use ReflectionException;
use Throwable;

class HtmlHttpExceptionMiddleware implements MiddlewareInterface
{
    use HttpExceptionRenderAware;
    use HttpExceptionUtilityAware;

    public function __construct(
        protected readonly Application $app,
        protected ErrorViewRenderer $errorView
    ) {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws ReflectionException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpException | Psr7Exception $e) {
            $this->logException($e);
            return $this->renderErrorView($e);
        } catch (Throwable $t) {
            $this->logException($t);
            return $this->renderErrorView($t);
        }
    }
}
