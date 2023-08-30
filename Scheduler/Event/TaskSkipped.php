<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Event;

use Qubus\EventDispatcher\BaseEvent;

final class TaskSkipped extends BaseEvent
{
    public const EVENT_NAME = 'task.skipped';
}
