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
        public private(set) int $expiresAt {
            get => $this->expiresAt;
            set(int $value) => $this->expiresAt = time() + $value;
        },
        public int $count = 0 {
            get => $this->count;
            set(int $value) => $this->count = $value;
        }
    ) {
    }
}
