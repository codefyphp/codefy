<?php

namespace Codefy\Framework\Http\Middleware\Request;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function call_user_func;

abstract readonly class FormRequestHandler implements RequestHandlerInterface
{
    /**
     * @param FormRequestMiddleware $middleware
     */
    public function __construct(private FormRequestMiddleware $middleware)
    {
    }

    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $callable = function (ServerRequestInterface $request): ResponseInterface {
            return $this->innerHandle($request);
        };

        return $this->middleware->process($request, new class ($callable) implements RequestHandlerInterface {
            /** @var callable $callable */
            private $callable;

            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return call_user_func($this->callable, $request);
            }
        });
    }

    abstract protected function innerHandle(ServerRequestInterface $request): ResponseInterface;
}
