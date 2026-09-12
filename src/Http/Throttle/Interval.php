<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Throttle;

use function time;

class Interval
{
    /**
     * @param int $expiresAt
     * @param int $count
     */
    public function __construct(
        public private(set) int $expiresAt,
        public int $count = 0,
    ) {
        $this->expiresAt = time() + $expiresAt;
    }
}
