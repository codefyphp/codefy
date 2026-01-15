<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Processor;

use DateTimeZone;

interface Processor
{
    /**
     * Unique name to use for a mutually exclusive lock.
     */
    public function mutexName(): string;

    public function maxRuntime(): int;

    /**
     * Set a command description.
     */
    public function description(string $description): self;

    /**
     * Check if the command can run in background.
     */
    public function canRunCommandInBackground(): bool;

    /**
     * Check if process con only have one instance.
     *
     * @return bool
     */
    public function canRunOnlyOneInstance(): bool;

    /**
     * Gets the current cron expression for the task.
     */
    public function getExpression(): ?string;

    /**
     * Determine if the given command should run based on the Cron expression.
     */
    public function isDue(string|DateTimeZone|null $timeZone = null): bool;

    public function run(): mixed;

    /**
     * Returns the command.
     *
     * @return callable|string
     */
    public function getCommand(): callable|string;
}
