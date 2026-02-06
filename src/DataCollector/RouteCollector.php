<?php

declare(strict_types=1);

namespace Codefy\Framework\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

final class RouteCollector extends DataCollector implements Renderable
{
    public function __construct(
        private readonly string|null $routeName,
        private readonly string|null $controller
    ) {
    }

    public function collect(): array
    {
        return [
            'route' => $this->routeName,
            'controller' => $this->controller,
        ];
    }

    public function getName(): string
    {
        return 'route';
    }

    public function getWidgets(): array
    {
        $widget = $this->isHtmlVarDumperUsed()
        ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
        : "PhpDebugBar.Widgets.VariableListWidget";

        return [
            'route' => [
                'icon' => 'route',
                'widget' => $widget,
                'map' => 'route',
                "default" => "{}",
            ],
        ];
    }
}
