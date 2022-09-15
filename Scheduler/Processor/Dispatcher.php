<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Processor;

use Qubus\Console\Processor\BaseProcessor;
use Qubus\EventDispatcher\EventDispatcher;
use Stringable;

class Dispatcher extends BaseProcessor implements Stringable, Processor
{
    protected EventDispatcher $dispatcher;

    public function __construct(string $command)
    {
        $this->command = $command;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function run()
    {
        return $this->dispatcher->dispatch($this->command);
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
