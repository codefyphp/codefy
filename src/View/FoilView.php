<?php

declare(strict_types=1);

namespace Codefy\Framework\View;

use Foil\Engine;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\View\Renderer;

use function Codefy\Framework\Helpers\config;
use function Foil\engine;

final class FoilView implements Renderer
{
    private Engine $engine;

    /**
     * @throws TypeException
     * @throws Exception
     */
    public function __construct(protected ConfigContainer $configContainer)
    {
        $this->engine = engine($this->configContainer->getConfigKey(key: 'view.options'));
    }

    public function render(array|string $template, array $data = []): string|array
    {
        return $this->engine->render(template: $template, data: $data);
    }
}
