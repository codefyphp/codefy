<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

/**
 * Interface for a garbage collection.
 *
 * If the 'queue' service implements this interface, the
 * garbageCollection() method will be called when scheduled.
 */
interface QueueGarbageCollection
{
    /**
     * Cleans queues of garbage.
     */
    public function garbageCollection(): void;
}
