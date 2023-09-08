<?php

declare(strict_types=1);

namespace Codefy\Framework\View;

use Fenom;
use Fenom\Error\CompileException;
use Fenom\Provider;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\View\Renderer;

final class FenomView implements Renderer
{
    private Fenom $view;

    /**
     * @throws Exception
     */
    public function __construct(ConfigContainer $config)
    {
        $this->view = (new Fenom(
            provider: new Provider(template_dir: $config->getConfigKey('view.path'))
        ))->setCompileDir(
            dir: $config->getConfigKey('view.cache')
        )->setOptions(options: $config->getConfigKey('view.options'));
    }

    /**
     * @throws CompileException
     */
    public function render(array|string $template, array $data = []): string|array
    {
        return $this->view->display(template: $template, vars: $data);
    }
}
