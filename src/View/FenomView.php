<?php

declare(strict_types=1);

namespace Codefy\Framework\View;

use Fenom\Error\CompileException;
use Fenom\Provider;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\View\Renderer;

final class FenomView implements Renderer
{
    private \Fenom $fenom;

    /**
     * @throws TypeException
     * @throws Exception
     */
    public function __construct(protected ConfigContainer $configContainer)
    {
        $this->fenom = new \Fenom(
            provider: new Provider(template_dir: $this->configContainer->getConfigKey(key: 'view.path'))
        )->setCompileDir(
            dir: $this->configContainer->getConfigKey(key: 'view.cache')
        )->setOptions(options: $this->configContainer->getConfigKey(key: 'view.options'));
    }

    /**
     * @param array<mixed>|string $template
     * @param array<mixed> $data
     * @return array<mixed>
     * @throws CompileException
     */
    public function render(array|string $template, array $data = []): array
    {
        return $this->fenom->display(template: $template, vars: $data);
    }
}
