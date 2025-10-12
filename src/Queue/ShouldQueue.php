<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

interface ShouldQueue
{
    /**
     * The code/task that should be executed.
     *
     * @return bool
     */
    public function handle(): bool;

    public function node(): string;
}
