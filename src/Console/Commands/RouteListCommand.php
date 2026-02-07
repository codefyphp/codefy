<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Symfony\Component\Console\Helper\Table;

use function implode;

class RouteListCommand extends ConsoleCommand
{
    protected string $name = 'route:list';

    protected string $description = 'Lists the registered routes.';

    protected string $help = 'This command displays the list of registered routes.';

    /**
     * @throws \Exception
     */
    public function handle(): int
    {
        $table = new Table($this->output);
        $table->setHeaders([
            "Domain",
            "Action",
            "Method",
            "Uri",
            "Name",
            "Middleware",
        ]);

        $routes = $this->codefy->router->routes;

        foreach ($routes as $route) {
            $table->addRow([
                $route->getDomain(),
                $route->getActionName(),
                implode('| ', $route->methods),
                $route->uri,
                $route->name,
                implode(', ', $route->gatherMiddlewares()),
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
