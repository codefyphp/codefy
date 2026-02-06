<?php

declare(strict_types=1);

namespace Codefy\Framework\View;

use Qubus\View\Renderer;

interface TemplateRenderer extends Renderer
{
    public function render(array|string $template, array $data = []): mixed;
}