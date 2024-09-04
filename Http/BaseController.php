<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Codefy\Framework\Contracts\RoutingController;
use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Controller\Controller;
use Qubus\Routing\Router;
use Qubus\View\Native\NativeLoader;
use Qubus\View\Renderer;

use function Codefy\Framework\Helpers\config;

class BaseController extends Controller implements RoutingController
{
    public function __construct(
        protected SessionService $sessionService,
        protected Router $router,
        protected ?Renderer $view = null,
    ) {
        $this->setView(view: $view ?? new NativeLoader(config('view.path')));
    }

    /**
     * Gets the view instance.
     *
     * @return Renderer
     */
    public function getView(): Renderer
    {
        return $this->view;
    }

    /**
     * Sets the view instance.
     *
     * @param Renderer $view
     * @return BaseController
     */
    public function setView(Renderer $view): self
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
