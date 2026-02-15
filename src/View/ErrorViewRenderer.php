<?php

declare(strict_types=1);

namespace Codefy\Framework\View;

use Codefy\Framework\Application;
use Psr\Http\Message\ResponseInterface;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\FileSystem\FileSystem;
use Qubus\Http\Factories\HtmlResponseFactory;
use Qubus\Http\Status;

use function sprintf;

final readonly class ErrorViewRenderer
{
    public function __construct(
        private Application $app,
        private FileSystem $fileSystem,
    ) {
    }

    /**
     * @throws NotFoundException
     * @throws \Exception
     */
    public function render(int $code): string
    {
        $httpCode    = $code > 0 ? $code : 500;
        $message     = Status::getMessageForCode($httpCode);
        $template    = $this->loadTemplate();

        return $this->replaceTags((string) $template, $httpCode, $message);
    }

    /**
     * @throws \Exception
     */
    public function toResponse(string $html): ResponseInterface
    {
        return HtmlResponseFactory::create($html);
    }

    /**
     * @throws NotFoundException
     * @throws \Exception
     */
    private function loadTemplate(): bool|string
    {
        $path = $this->app->hook->filter->applyFilter(
            'error.view',
            $this->app::$ROOT_PATH . '/vendor/codefyphp/codefy/src/View/error.phtml'
        );

        if (!$this->fileSystem->exists(filename: $path, throw: false)) {
            throw new \RuntimeException(sprintf("Error view template not found: %s", $path));
        }

        return $this->fileSystem->getContents($path);
    }

    private function replaceTags(string $template, int $code, string $message): string
    {
        return str_ireplace(
            search: ['{{ code }}', '{{ message }}'],
            replace: [(string) $code, $message],
            subject: $template,
        );
    }
}
