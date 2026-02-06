<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

use Codefy\Framework\Scheduler\Processor\Processor;
use Qubus\Exception\Exception;

final class FailedProcessor
{
    /**
     * @param Processor $processor
     * @param Exception $exception
     */
    public function __construct(
        // @phpstan-ignore property.onlyWritten
        private Processor $processor {
            get => $this->processor;
        },
        // @phpstan-ignore property.onlyWritten
        private Exception $exception {
            get => $this->exception;
        }
    ) {
    }
}
