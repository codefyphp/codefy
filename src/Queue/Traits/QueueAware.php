<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue\Traits;

trait QueueAware
{
    /**
     * The name of the queue this instance is working with.
     */
    public string $name = '';

    /**
     * How long the processing is expected to take in seconds.
     */
    public int $leaseTime = 3600;

    /**
     * When should the process run.
     */
    public string $schedule = '* * * * *';

    /**
     * How many times should a job execute before
     * considered dead.
     */
    public int $executions = 3;
}
