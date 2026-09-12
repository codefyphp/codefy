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
        public private(set) int $ttl,
        public private(set) int $limit,
    ) {
        if ($ttl <= 0 || $limit <= 0) {
            throw new \InvalidArgumentException('Rate limit TTL and limit must be positive.');
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->ttl;
    }
}
