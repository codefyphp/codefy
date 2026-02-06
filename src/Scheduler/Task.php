<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

use Codefy\Framework\Scheduler\Processor\Processor;
use Cron\CronExpression;
use Psr\EventDispatcher\EventDispatcherInterface;

interface Task extends Processor
{
    /**
     * Return an instance with configured options.
     *
     * @param array<array|int|string|bool|CronExpression|null> $options
     * @return self
     */
    public function withOptions(array $options): self;

    /**
     * Return an instance with scheduler.
     *
     * @param Schedule $schedule
     * @return self
     */
    public function withScheduler(Schedule $schedule): self;

    /**
     * Return an instance with event dispatcher.
     *
     * @param EventDispatcherInterface $dispatcher
     * @return self
     */
    public function withDispatcher(EventDispatcherInterface $dispatcher): self;

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
