<?php

declare(strict_types=1);

namespace Codefy\Framework\Providers;

use Codefy\Framework\Http\Middleware\Exception\ExceptionHandler;
use Codefy\Framework\Http\Middleware\Exception\Strategy\HtmlHttpResponseStrategy;
use Codefy\Framework\Http\Middleware\Exception\Strategy\JsonHttpResponseStrategy;
use Codefy\Framework\Http\Middleware\Exception\Strategy\RedirectHttpResponseStrategy;
use Codefy\Framework\Support\CodefyServiceProvider;
use Codefy\Framework\View\ErrorViewRenderer;
use Qubus\FileSystem\FileSystem;

class HttpExceptionServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->define(
            name: ErrorViewRenderer::class,
            args: [
                ':app' => $this->codefy,
                ':filesystem' => FileSystem::class,
            ]
        );

        $this->codefy->define(
            name: ExceptionHandler::class,
            args: [
                ':strategies' => [
                    RedirectHttpResponseStrategy::class,
                    JsonHttpResponseStrategy::class,
                    HtmlHttpResponseStrategy::class,
                ],
                ':app' => $this->codefy,
            ]
        );
    }
}
