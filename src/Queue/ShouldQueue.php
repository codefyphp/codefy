<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

interface ShouldQueue
{
    /**
     * The code/task that should be executed.
     *
     * @return void
     */
    public function handle(): void;

    public function node(): string;
}
