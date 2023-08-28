<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

use DateTimeZone;
use Codefy\Framework\Scheduler\ValueObject\TaskId;

interface Task
{
    /**
     * Unique ID for the task.
     */
    public function pid(): TaskId;

    /**
     * Called before a task is executed.
     */
    public function setUp(): void;

    /**
     * Executes a task.
     */
    public function execute(Schedule $schedule): void;

    /**
     * Called after a task is executed.
     */
    public function tearDown(): void;

    /**
     * Get set options.
     *
     * @param string|null $option
     * @return mixed
     */
    public function getOption(?string $option): mixed;

    /**
     * Checks whether the task is currently due.
     */
    public function isDue(string|DateTimeZone|null $timeZone = null): bool;
}
