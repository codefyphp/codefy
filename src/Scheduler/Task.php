<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

interface Task
{
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
}
