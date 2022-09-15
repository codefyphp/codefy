<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler;

use Codefy\Foundation\Scheduler\Processor\Processor;
use Qubus\Exception\Exception;

final class FailedProcessor
{
    /**
     * @param Processor $processor
     * @param Exception $exception
     */
    public function __construct(private Processor $processor, private Exception $exception)
    {
    }

    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    public function getException(): Exception
    {
        return $this->exception;
    }
}