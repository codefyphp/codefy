<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Codefy\Framework\Contracts\RoutingController;
use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Factories\RedirectResponseFactory;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Controller\Controller;
use Qubus\Routing\Psr7Router;
use Qubus\View\Renderer;

class BaseController extends Controller implements RoutingController
{
    public function __construct(
        protected SessionService $sessionService {
            get => $this->sessionService;
        },
        protected Psr7Router $router {
            get => $this->router;
        },
        protected Renderer $view {
            get => $this->view;
        },
    ) {
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
        return RedirectResponseFactory::create(uri: $url, status: $status);
    }
}
