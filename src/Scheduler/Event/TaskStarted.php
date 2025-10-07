<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Event;

use Codefy\Framework\Scheduler\Task;
use Qubus\EventDispatcher\BaseEvent;
use Qubus\EventDispatcher\Legacy\Event;

final class TaskStarted extends BaseEvent implements Event
{
    public const string EVENT_NAME = 'task.started';

    public private(set) ?Task $task = null {
        get => $this->task;
    }

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }
}
