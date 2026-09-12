<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

use Codefy\Framework\Scheduler\Processor\Processor;
use Throwable;

final readonly class FailedProcessor
{
    /**
     * @param Processor $processor
     * @param Throwable $exception
     */
    public function __construct(
        public Processor $processor,
        public Throwable $exception,
    ) {
    }
}
