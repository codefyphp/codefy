<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Processor;

use Qubus\EventDispatcher\EventDispatcher;
use Stringable;

class Dispatcher extends BaseProcessor implements Stringable, Processor
{
    protected EventDispatcher $dispatcher;

    public function getCommand(): string
    {
        return $this->command;
    }

    public function run(): bool
    {
        if (
            $this->preventOverlapping &&
            ! $this->mutex->tryLock($this)
        ) {
            return false;
        }

        $this->callBeforeCallbacks();

        $this->dispatcher->dispatch($this->command);

        $this->callAfterCallbacks();

        return true;
    }

    public function setDispatcher(EventDispatcher $dispatcher): static
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getCommand();
    }
}
