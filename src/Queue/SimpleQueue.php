<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

use Codefy\Framework\Queue\Traits\QueueAware;

use function Codefy\Framework\Helpers\database_path;

abstract class SimpleQueue implements ShouldQueue
{
    use QueueAware;

    public function node(): string
    {
        return database_path(path: 'nodequeue');
    }
}
