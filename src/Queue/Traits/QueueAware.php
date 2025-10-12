<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue\Traits;

trait QueueAware
{
    /**
     * The name of the queue this instance is working with.
     */
    protected string $name = '';

    /**
     * How long the processing is expected to take in seconds.
     */
    protected int $leaseTime = 3600;

    protected bool $debug = false;

    /**
     * When should the process run.
     */
    protected string $schedule = '* * * * *';
}
