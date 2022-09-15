<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler\Mutex;

use Codefy\Foundation\Scheduler\Processor\Processor;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

use function time;

class CacheLocker implements Locker
{
    protected CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function tryLock(Processor $processor): bool
    {
        $item = $this->cache->getItem($processor->mutexName());

        if ($item->isHit()) {
            //cache is hit, cannot lock!
            return false;
        }

        $item->set($processor->maxRuntime() + time());

        $item->expiresAfter($processor->maxRuntime());

        // and saves it
        return $this->cache->save($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hasLock(Processor $processor): bool
    {
        return $this->cache->hasItem($processor->mutexName());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function unlock(Processor $processor): bool
    {
        return $this->cache->deleteItem($processor->mutexName());
    }
}
