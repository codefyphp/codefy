<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

/**
 * Reliable queue interface.
 *
 * Classes implementing this interface preserve the order of messages and
 * guarantee that every item will be executed at least once.
 */
interface ReliableQueue extends Queue
{
}
