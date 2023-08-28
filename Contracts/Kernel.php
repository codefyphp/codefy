<?php

declare(strict_types=1);

namespace Codefy\Framework\Contracts;

use Codefy\Framework\Application;

interface Kernel
{
    /**
     * Get the CodefyPHP application instance.
     */
    public function codefy(): Application;

    /**
     * Kernel boots the application.
     *
     * @return bool
     */
    public function boot(): bool;
}
