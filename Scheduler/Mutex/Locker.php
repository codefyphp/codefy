<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Mutex;

use Codefy\Framework\Scheduler\Processor\Processor;

interface Locker
{
    public function tryLock(Processor $processor): bool;

    public function hasLock(Processor $processor): bool;

    public function unlock(Processor $processor): bool;
}
