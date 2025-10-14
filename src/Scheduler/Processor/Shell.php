<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Processor;

use Stringable;

use function exec;

class Shell extends BaseProcessor implements Stringable, Processor
{
    /**
     * Returns the command.
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * Executes the shell command.
     */
    public function run(): bool
    {
        if (
            $this->preventOverlapping &&
            ! $this->mutex->tryLock($this)
        ) {
            return false;
        }

        $this->callBeforeCallbacks();

        $task = $this->canRunCommandInBackground() ? $this->runCommandInBackground() : $this->runCommandInForeground();

        exec($task);

        $this->callAfterCallbacks();

        return true;
    }

    public function __toString(): string
    {
        return $this->getCommand();
    }
}
