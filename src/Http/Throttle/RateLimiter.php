<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Throttle;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

use function md5;
use function sprintf;

class RateLimiter
{
    /**
     * @var Condition[]
     */
    private array $conditions = [];

    /**
     * @var CacheItemPoolInterface
     */
    private CacheItemPoolInterface $cache;

    /**
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param Condition $condition
     * @return RateLimiter
     */
    public function add(Condition $condition): RateLimiter
    {
        foreach ($this->conditions as $existing) {
            if ($condition->ttl === $existing->ttl) {
                throw new \LogicException(
                    sprintf('This instance already has a condition with a ttl of %d', $existing->ttl)
                );
            } elseif ($condition->ttl > $existing->ttl && $condition->limit <= $existing->limit) {
                throw new \LogicException(
                    sprintf(
                        'Adding a condition of ttl %d, limit %d will never be 
                        reached due to existing condition of ttl %d, limit %d',
                        $condition->ttl,
                        $condition->limit,
                        $existing->ttl,
                        $existing->limit
                    )
                );
            } elseif ($condition->ttl < $existing->ttl && $condition->limit >= $existing->limit) {
                throw new \LogicException(
                    sprintf(
                        'Adding a condition of ttl %d, limit %d will prevent existing 
                        condition of ttl %d, limit %d from being reached',
                        $condition->ttl,
                        $condition->limit,
                        $existing->ttl,
                        $existing->limit
                    )
                );
            }
        }
        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * @param string $identifier
     * @param int $count
     * @return $this
     * @throws RateException
     * @throws InvalidArgumentException
     */
    public function increment(string $identifier, int $count = 1): RateLimiter
    {
        foreach ($this->conditions as $condition) {
            $item = $this->getItem($identifier, $condition);
            if ($item->isHit()) {
                /** @var Interval $interval */
                $interval = $item->get();
            } else {
                $interval = new Interval($condition->ttl);
            }
            $item->expiresAfter($condition->ttl);
            $interval->count += $count;
            $item->set($interval);
            $this->cache->save($item);

            if ($interval->count > $condition->limit) {
                throw new RateException($identifier, $condition);
            }
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @return Interval[]
     * @throws InvalidArgumentException
     */
    public function getIntervals(string $identifier): array
    {
        $intervals = [];
        foreach ($this->conditions as $condition) {
            $item = $this->getItem($identifier, $condition);
            $interval = $item->isHit() ? $item->get() : new Interval($condition->ttl);
            $intervals[] = $interval;
        }

        return $intervals;
    }

    /**
     * Reset counter
     *
     * @param string $identifier
     * @return $this
     * @throws InvalidArgumentException
     */
    public function reset(string $identifier): RateLimiter
    {
        foreach ($this->conditions as $condition) {
            $key = sprintf('%s-%s', $identifier, $condition);
            $this->cache->deleteItem(md5($key));
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @param Condition $condition
     * @return CacheItemInterface
     * @throws InvalidArgumentException
     */
    private function getItem(string $identifier, Condition $condition): CacheItemInterface
    {
        $key = sprintf('%s-%s', $identifier, $condition);
        return $this->cache->getItem(md5($key));
    }
}
