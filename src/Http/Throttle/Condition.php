<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Throttle;

class Condition
{
    /**
     * @param int $ttl
     * @param int $limit
     */
    public function __construct(
        public private(set) int $ttl {
            get => $this->ttl;
            set(int $value) => $this->ttl = $value;
        },
        public private(set) int $limit {
            get => $this->limit;
            set(int $value) => $this->limit = $value;
        }
    ) {
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->ttl;
    }
}
