<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Processor;

use function exec;

class Shell extends BaseProcessor implements \Stringable, Processor
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

        try {
            $this->callBeforeCallbacks();

            $task = $this->canRunCommandInBackground()
            ? $this->runCommandInBackground()
            : $this->runCommandInForeground();

            exec($task, $output, $exitCode);

            $this->callAfterCallbacks();

            return $exitCode === 0;
        } finally {
            if ($this->preventOverlapping) {
                return $this->mutex->unlock($this);
            }
        }
    }

    public function __toString(): string
    {
        return $this->getCommand();
    }
}
