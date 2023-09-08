<?php

declare(strict_types=1);

namespace Codefy\Framework\View;

use Foil\Engine;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\View\Renderer;

use function Foil\engine;

final class FoilView implements Renderer
{
    private Engine $view;

    /**
     * @throws Exception
     */
    public function __construct(ConfigContainer $config)
    {
        $this->view = engine($config->getConfigKey('view.options'));
    }

    public function render(array|string $template, array $data = []): string|array
    {
        return $this->view->render(template: $template, data: $data);
    }
}
