<?php

declare(strict_types=1);

namespace Codefy\Framework\View;

use Foil\Engine;
use Qubus\View\Renderer;

use function Codefy\Framework\Helpers\config;
use function Foil\engine;

final class FoilView implements Renderer
{
    private Engine $engine;

    public function __construct()
    {
        $this->engine = engine(config(key: 'view.options'));
    }

    public function render(array|string $template, array $data = []): string|array
    {
        return $this->engine->render(template: $template, data: $data);
    }
}
