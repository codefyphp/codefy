<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Scheduler\Schedule;
use Cron\CronExpression;
use Exception;
use Symfony\Component\Console\Helper\Table;

class ListCommand extends ConsoleCommand
{
    protected string $name = 'schedule:list';

    protected string $description = 'Lists the existing jobs/tasks.';

    protected string $help = 'This command displays the list of registered jobs/tasks.';

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
            "Class",
            "Expression",
            "Next Execution",
            "Run Only One Instance",
        ]);

        $jobs = $this->schedule->allProcessors();

        foreach ($jobs as $job) {
            $nextRun = new CronExpression($job->getExpression());

            $table->addRow([
                get_class($job),
                $nextRun->getExpression(),
                $nextRun->getNextRunDate()->format(format: 'd F Y h:i A'),
                $job->canRunOnlyOneInstance() ? 'yes' : 'no',
            ]);
        }

        $table->render();

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}
