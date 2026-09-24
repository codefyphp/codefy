<?php

declare(strict_types=1);

namespace Codefy\Framework\Http;

use Codefy\Framework\Contracts\RoutingController;
use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Factories\RedirectResponseFactory;
use Qubus\Routing\Controller\Controller;
use Qubus\View\Renderer;

/**
 * Shared controller helpers; concrete controllers inject their own dependencies.
 */
class BaseController extends Controller implements RoutingController
{
    protected Renderer $view;

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
     * @return ResponseInterface
     */
    public function redirect(string $url, int $status = 302): ResponseInterface
    {
        return RedirectResponseFactory::create(uri: $url, status: $status);
    }
}
