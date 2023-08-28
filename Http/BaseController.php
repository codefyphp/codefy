<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Codefy\Framework\Application;
use Codefy\Framework\Contracts\RoutingController;
use Fenom;
use Fenom\Provider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Routing\Controller\Controller;
use Qubus\Routing\Router;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\resource_path;

class BaseController extends Controller implements RoutingController
{
    protected ServerRequestInterface $request;
    protected ResponseInterface $response;
    protected Router $router;
    protected Fenom $view;

    public function __construct(
        ?ServerRequestInterface $request = null,
        ?ResponseInterface $response = null,
        ?Router $router = null,
        ?Fenom $view = null
    ) {
        $this->setRequest($request ?? app(name: ServerRequestInterface::class));
        $this->response = $response ?? app(name: ResponseInterface::class);
        $this->router = $router ?? app(name: 'router');
        $this->setView(
            view: $view ??
            (new Fenom(
                provider: new Provider(template_dir: resource_path(path: 'views'))
            ))->setCompileDir(
                dir: resource_path(path: 'views'.Application::DS.'cache')
            )->setOptions(options: Fenom::DISABLE_CACHE)
        );
    }

    /**
     * Gets the request instance.
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Set the request instance.
     *
     * @param ServerRequestInterface $request
     * @return BaseController
     */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Gets the response instance.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Gets the view instance.
     *
     * @return Fenom
     */
    public function getView(): Fenom
    {
        return $this->view;
    }

    /**
     * Sets the view instance.
     *
     * @param Fenom $view
     */
    public function setView(Fenom $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Redirects to given $url.
     *
     * @param string $url A string.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return ResponseInterface|null
     */
    public function redirect(string $url, int $status = 302): ?ResponseInterface
    {
        if ($status) {
            $this->response = $this->response->withStatus($status);
        }

        $response = $this->response;

        if (!$response->getHeaderLine('Location')) {
            $response = $response->withHeader('Location', $url);
        }

        return $this->response = $response;
    }
}
