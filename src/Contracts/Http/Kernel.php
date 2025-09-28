<?php

declare(strict_types=1);

namespace Codefy\Framework\Contracts\Http;

use Codefy\Framework\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Kernel
{
    /**
     * Get the CodefyPHP application instance.
     */
    public function codefy(): Application;

    /**
     * Handle a server request.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Kernel boots the application.
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public function boot(ServerRequestInterface $request): void;
}
