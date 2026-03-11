<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Throttle;

final class Interval
{
    /**
     * @var int
     */
    private int $expiresAt = 0;

    /**
     * @var int
     */
    public int $count = 0;

    /**
     * @param int $expiresAt
     * @param int $count
     */
    public function __construct(int $expiresAt, int $count = 0)
    {
        $this->expiresAt = time() + $expiresAt;
        $this->count = $count;
    }

    /**
     * @return int
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }
}
