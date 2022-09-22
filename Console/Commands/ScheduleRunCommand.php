<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Application;
use Codefy\Foundation\Console\ConsoleCommand;
use Codefy\Foundation\Scheduler\Schedule;

class ScheduleRunCommand extends ConsoleCommand
{
    protected string $name = 'schedule:run';

    protected string $description = 'Executes schedules that are due.';

    protected string $help = 'Command which can start the scheduler.';

    public function __construct(protected Schedule $schedule, protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    public function handle(): int
    {
        $this->schedule->run();

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}
