<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue\Traits;

trait QueueAware
{
    /**
     * The name of the queue this instance is working with.
     */
    public string $name = '' {
        get => $this->name;
        set(string $value) => $this->name = $value;
    }

    /**
     * How long the processing is expected to take in seconds.
     */
    public int $leaseTime = 3600 {
        get => $this->leaseTime;
        set(int $value) => $this->leaseTime = $value;
    }

    /**
     * When should the process run.
     */
    public string $schedule = '* * * * *' {
        get => $this->schedule;
        set(string $value) => $this->schedule = $value;
    }

    /**
     * How many times should a job execute before
     * considered dead.
     */
    public int $executions = 3 {
        get => $this->executions;
        set(int $value) => $this->executions = $value;
    }
}
