<?php

declare(strict_types=1);

namespace Codefy\Framework\View;

use Fenom;
use Fenom\Error\CompileException;
use Fenom\Provider;
use Qubus\View\Renderer;

use function Codefy\Framework\Helpers\config;

final class FenomView implements Renderer
{
    private Fenom $fenom;

    public function __construct()
    {
        $this->fenom = (new Fenom(
            provider: new Provider(template_dir: config(key: 'view.path'))
        ))->setCompileDir(
            dir: config(key: 'view.cache')
        )->setOptions(options: config(key: 'view.options'));
    }

    /**
     * @throws CompileException
     */
    public function render(array|string $template, array $data = []): string|array
    {
        return $this->fenom->display(template: $template, vars: $data);
    }
}
