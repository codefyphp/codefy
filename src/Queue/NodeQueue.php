<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Factory\FileLoggerSmtpFactory;
use Codefy\Framework\Scheduler\Traits\ExpressionAware;
use Cron\CronExpression;
use Qubus\Exception\Data\TypeException;
use Qubus\NoSql\Collection;
use Qubus\NoSql\Node;
use Qubus\Support\Serializer\JsonSerializer;

use function call_user_func;
use function is_callable;
use function time;

class NodeQueue implements ReliableQueue, QueueGarbageCollection
{
    use ExpressionAware;

    protected ?ShouldQueue $queue = null;
    private ?Collection $db = null;

    /** @var array<callable|bool> $filters */
    protected array $filters = [];

    /** @var array<callable|bool> $rejects */
    protected array $rejects = [];

    protected \DateTimeZone|string|null $timezone = null;

    public function __construct(ShouldQueue $queue, ?string $node = null, \DateTimeZone|string|null $timezone = null)
    {
        $this->queue = $queue;
        $this->db = Node::open(file: $node ?? $this->queue->node());
        $this->timezone = $timezone;
    }

    /**
     * @param string|callable $schedule
     * @return bool
     */
    public function isDue(string|callable $schedule): bool
    {
        if (is_callable($schedule)) {
            return call_user_func($schedule);
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $schedule, $this->timezone);
        if ($dateTime !== false) {
            return $dateTime->format('Y-m-d H:i') == (date('Y-m-d H:i'));
        }

        return new CronExpression((string) $schedule)->isDue();
    }

    /**
     * @inheritDoc
     * @throws \ReflectionException
     */
    public function createItem(): string
    {
        $id = '';

        try {
            $id = $this->doCreateItem();
        } catch (\Exception $e) {
            FileLoggerFactory::getLogger()->error(
                sprintf('NODEQSTATE: %s', $e->getMessage()),
                ['Queue' => 'NodeQueue::createItem']
            );
        }

        return $id;
    }

    /**
     * Adds a queue item and store it directly to the queue.
     *
     * @return string A unique ID if the item was successfully created and was (best effort)
     *                added to the queue, otherwise false. We don't guarantee the item was
     *                committed to disk etc., but as far as we know, the item is now in the
     *                queue.
     * @throws \ReflectionException
     */
    protected function doCreateItem(): string
    {
        $lastId = '';

        $query = $this->db;
        $query->begin();
        try {
            $query->insert([
                'name' => $this->queue->name,
                'object' => new JsonSerializer()->serialize($this->queue),
                'created' => time(),
                'expire' => (int) 0,
                'executions' => (int) 0,
            ]);
            $query->commit();
            $lastId = $query->lastInsertId();
        } catch (\Exception $e) {
            $query->rollback();
            FileLoggerFactory::getLogger()->error(
                sprintf('NODEQSTATE: %s', $e->getMessage()),
                ['Queue' => 'NodeQueue::doCreateItem']
            );
        }
        /**
         * Return the new serial ID, or false on failure.
         */
        return $lastId;
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws \ReflectionException
     */
    public function numberOfItems(): int
    {
        try {
            return count($this->db->where('name', $this->queue->name)->get());
        } catch (\Exception $e) {
            $this->catchException($e);
            /**
             * If there is no node there cannot be any items.
             */
            return 0;
        }
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws \ReflectionException
     */
    public function claimItem(int $leaseTime = 3600): array|object|bool
    {
        /**
         * Claim an item by updating its expiry fields. If claim is not
         * successful another thread may have claimed the item in the meantime.
         * Therefore, loop until an item is successfully claimed, or we are
         * reasonably sure there are no unclaimed items left.
         */
        try {
            $item = $this->db
                ->where('expire', (int) 0)
                ->where('name', $this->queue->name)
                ->sortBy('created')
                ->sortBy('_id')
                ->first();
        } catch (\Exception $e) {
            $this->catchException($e);
            /**
             * If the node does not exist there are no items currently
             * available to claim.
             */
            return false;
        }
        if ($item) {
            $update = $this->db;
            $update->begin();
            try {
                /**
                 * Try to update the item. Only one thread can succeed in
                 * UPDATE-ing the same row. We cannot rely on REQUEST_TIME
                 * because items might be claimed by a single consumer which
                 * runs longer than 1 second. If we continue to use REQUEST_TIME
                 * instead of the current time(), we steal time from the lease,
                 * and will tend to reset items before the lease should really
                 * expire.
                 */
                $update->where('expire', (int) 0)->where('_id', $item['_id'])
                    ->update([
                    'expire' => (int) time() + (
                        $this->queue->leaseTime <= (int) 0
                            ? (int) $leaseTime
                            : (int) $this->queue->leaseTime
                    )
                ]);
                $update->commit();
                return $item;
            } catch (\Exception $e) {
                $update->rollback();
                $this->catchException($e);
                /**
                 * If the node does not exist there are no items currently
                 * available to claim.
                 */
                return false;
            }
        } else {
            /**
             * No items currently available to claim.
             */
            return false;
        }
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws \ReflectionException
     */
    public function deleteItem(mixed $item): void
    {
        $delete = $this->db;
        $delete->begin();
        try {
            $delete->where('_id', $item['_id'])
                ->delete();
            $delete->commit();
        } catch (\Exception $e) {
            $delete->rollback();
            $this->catchException($e);
        }
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws \ReflectionException
     */
    public function releaseItem(mixed $item): bool
    {
        $update = $this->db;
        $update->begin();
        try {
            $update->where('_id', $item['_id'])
                ->update([
                    'expire' => (int) 0,
                    'executions' => +1,
                ]);
            $update->commit();

            return true;
        } catch (\Exception $e) {
            $update->rollback();
            $this->catchException($e);
        }

        return false;
    }

    /**
     * @inheritDoc
     * @throws TypeException
     * @throws \ReflectionException
     */
    public function deleteQueue(): void
    {
        $delete = $this->db;
        $delete->begin();
        try {
            $delete->where('name', $this->queue->name)
                ->delete();
            $delete->commit();
        } catch (\Exception $e) {
            $delete->rollback();
            $this->catchException($e);
        }
    }

    /**
     * @throws \ReflectionException
     * @throws TypeException
     */
    public function garbageCollection(): void
    {
        $requestTime = time();

        $delete = $this->db;
        $delete->begin();
        try {
            /**
             * Clean up the queue for failed batches.
             */
            $delete
                ->where('created', '<', $requestTime - 864000)
                ->where('name', $this->queue->name)
                ->delete();
            $delete->commit();
        } catch (\Exception $e) {
            $delete->rollback();
            $this->catchException($e);
        }

        $update = $this->db;
        $update->begin();

        try {
            /**
             * Reset expired items in the default queue implementation node. If
             * that's not used, this will simply be a no-op.
             */
            $update->where('expire', 'not in', (int) 0)
                    ->where('expire', '<', $requestTime)
                    ->update([
                        'expire' => (int) 0
                    ]);
            $update->commit();
        } catch (\Exception $e) {
            $update->rollback();
            $this->catchException($e);
        }
    }

    /**
     * Act on an exception when queue might be stale.
     *
     * If the node does not yet exist, that's fine, but if the node exists and
     * yet the query failed, then the queue is stale and the exception needs to
     * propagate.
     *
     * @param \Exception $e The exception.
     * @throws \ReflectionException
     */
    protected function catchException(\Exception $e): void
    {
        FileLoggerSmtpFactory::getLogger()->error(
            sprintf('QUEUESTATE: %s', $e->getMessage()),
            ['Queue' => 'catchException']
        );
    }

    /**
     * @throws \ReflectionException
     * @throws TypeException
     */
    public function dispatch(): bool
    {
        /**
         * Delete queues that are considered dead.
         */
        $this->garbageCollection();

        /**
         * Check if queue is due or not due.
         */
        if (!$this->isDue($this->queue->schedule)) {
            return false;
        }

        $item = $this->claimItem();

        try {
            if (false !== $item) {
                $object = new JsonSerializer()->unserialize($item['object']);

                if (!$object instanceof ShouldQueue) {
                    $this->deleteItem($item);
                    return false;
                };

                if (false !== call_user_func([$object, 'handle'])) {
                    $this->deleteItem($item);
                } else {
                    $this->releaseItem($item);
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->catchException($e);
        }

        return false;
    }

    /**
     * Truth test to determine if a queue should run when it is due.
     */
    public function skip(callable|bool $callback): self
    {
        $this->rejects[] = is_callable($callback) ? $callback : fn () => $callback;

        return $this;
    }

    /**
     * Truth test to determine if a queue should run when it is due.
     */
    public function when(callable|bool $callback): self
    {
        $this->filters[] = is_callable($callback) ? $callback : fn () => $callback;

        return $this;
    }
}
