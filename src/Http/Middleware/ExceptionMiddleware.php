<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Application;
use Codefy\Framework\Http\Status;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Error\Handlers\Psr3ErrorHandler;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Http\HttpException;
use Qubus\Exception\Http\Psr7Exception;
use Qubus\Http\Factories\HtmlResponseFactory;
use Qubus\Http\Factories\RedirectResponseFactory;
use Qubus\View\Renderer;
use ReflectionException;
use Throwable;

class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(protected Application $app, protected Renderer $view)
    {
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
            $response = $handler->handle($request);
        } catch (HttpException | Psr7Exception $e) {
            $this->app->flash->error(message: $e->getMessage());

            $this->logException($e);

            return RedirectResponseFactory::create(
                uri: $e->getUri() ?? $request->getServerParams()['HTTP_REFERER'] ?? '/',
            );
        } catch (Throwable $t) {

            $this->logException($t);

            return $this->abort($t, $request);
        }

        return $response;
    }

    /**
     * @throws ReflectionException
     * @throws TypeException
     */
    protected function logException(Throwable $t, array $context = []): void
    {
        $psrLogger = new Psr3ErrorHandler($this->app->getLogger());
        $psrLogger->handle($t, $context);
    }

    /**
     * @throws Exception
     */
    protected function abort(Throwable $t, ServerRequestInterface $request): ResponseInterface
    {
        $render = $this->app->configContainer->getConfigKey(key: 'view.error_view');
        if (true === $render) {
            $searchTags = ['{{ code }}', '{{ message }}'];
            $replaceWith = [$t->getCode() ?? 500, Status::getMessageForCode($t->getCode() ?? 500)];
            $file = file_get_contents(__DIR__ . '/../../View/error.phtml');
            $file = str_replace($searchTags, $replaceWith, $file);

            return HtmlResponseFactory::create($file);
        }

        $this->app->flash->error(message: 'Internal Error');

        return RedirectResponseFactory::create(
            uri: $request->getServerParams()['HTTP_REFERER'] ?? '/',
        );
    }
}
