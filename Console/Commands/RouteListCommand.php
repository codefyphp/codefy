<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Scheduler\Schedule;
use Exception;
use Symfony\Component\Console\Helper\Table;

class RouteListCommand extends ConsoleCommand
{
    protected string $name = 'route:list';

    protected string $description = 'Lists the loaded routes.';

    protected string $help = 'This command displays the list of loaded routes.';

    public function __construct(protected Schedule $schedule, protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $table = new Table($this->output);
        $table->setHeaders([
            "Domain",
            "Method",
            "Uri",
            "Name",
            "Middleware",
        ]);

        $routes = $this->codefy->make(name: 'router');

        foreach ($routes as $route) {
            $table->addRow([
                $route->getDomain(),
                implode('| ', $route->methods),
                $route->uri,
                $route->name,
                implode(', ', $route->gatherMiddlewares()),
            ]);
        }

        $table->render();

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}
