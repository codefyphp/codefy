<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Event;

use Codefy\Framework\Scheduler\Task;
use Qubus\EventDispatcher\BaseEvent;

final class TaskCompleted extends BaseEvent
{
    public const EVENT_NAME = 'task.completed';

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
