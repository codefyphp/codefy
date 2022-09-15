<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Event;

use Codefy\Foundation\Scheduler\Task;
use Qubus\EventDispatcher\BaseEvent;

final class TaskFailed extends BaseEvent
{
    public const EVENT_NAME = 'task.failed';

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function getTask(): Task
    {
        return $this->task;
    }
}
