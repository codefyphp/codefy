<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

interface ShouldQueue
{
    /**
     * The name of the queue this instance is working with.
     */
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

    /**
     * The code/task that should be executed.
     *
     * @return bool
     */
    public function handle(): bool;

    public function node(): string;
}
