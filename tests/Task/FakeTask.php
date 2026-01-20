<?php

declare(strict_types=1);

namespace Codefy\Framework\Tests\Task;

use Codefy\Framework\Scheduler\BaseTask;
use Codefy\Framework\Scheduler\Schedule;

class FakeTask extends BaseTask
{
    /**
     * Called before a task is executed.
     */
    public function setUp(): void
    {
        // do something before execute is called.
    }

    /**
     * Executes a task.
     */
    public function execute(Schedule $schedule): void
    {
        // do the task
    }

    /**
     * Called after a task is executed.
     */
    public function tearDown(): void
    {
        // cleanup after execute is called.
    }
}
