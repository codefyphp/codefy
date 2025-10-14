<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Event;

use Qubus\EventDispatcher\BaseEvent;
use Qubus\EventDispatcher\Legacy\Event;

final class TaskSkipped extends BaseEvent implements Event
{
    public const string EVENT_NAME = 'task.skipped';

    public function getName(): string
    {
        return self::EVENT_NAME;
    }
}
