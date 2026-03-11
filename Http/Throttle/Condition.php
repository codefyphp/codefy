<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Throttle;

final class Condition
{
    /**
     * @var int
     */
    private int $ttl = 0;

    /**
     * @var int
     */
    private int $limit = 0;

    /**
     * @param int $ttl
     * @param int $limit
     */
    public function __construct(int $ttl, int $limit)
    {
        $this->ttl = $ttl;
        $this->limit = $limit;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->ttl;
    }

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }
}
