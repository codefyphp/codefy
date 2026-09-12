<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

interface ShouldQueue
{
    /**
     * The name of the queue this instance is working with.
     */
    // PHPCS 3 does not parse PHP 8.4 interface property hooks.
    //phpcs:disable
    public string $name {
        get;
        set;
    }

    /**
     * How long the processing is expected to take in seconds.
     */
    public int $leaseTime {
        get;
        set;
    }

    /**
     * When should the process run.
     */
    public string $schedule {
        get;
        set;
    }

    /**
     * How many times should a job execute before
     * considered dead.
     */
    public int $executions {
        get;
        set;
    }

    //phpcs:enable

    /**
     * The code/task that should be executed.
     *
     * @return bool
     */
    public function handle(): bool;

    public function node(): string;
}
