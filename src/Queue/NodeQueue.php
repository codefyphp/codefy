<?php

declare(strict_types=1);

namespace Codefy\Framework\Queue;

use Codefy\Framework\Scheduler\Traits\ExpressionAware;
use Cron\CronExpression;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use JsonException;
use Random\RandomException;
use Throwable;

/** A local JSON queue. All workers must use this implementation and the same node path. */
class NodeQueue implements ReliableQueue, QueueGarbageCollection
{
    use ExpressionAware;

    /** @var list<callable> */
    protected array $filters = [];
    /** @var list<callable> */
    protected array $rejects = [];
    protected \DateTimeZone|string|null $timezone;
    private readonly string $file;

    public function __construct(
        protected ShouldQueue $queue,
        ?string $node = null,
        \DateTimeZone|string|null $timezone = null
    ) {
        $this->file = ($node ?? $queue->node()) . '.json';
        $this->timezone = $timezone;
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateInvalidTimeZoneException
     */
    public function isDue(string|callable $schedule): bool
    {
        if (is_callable($schedule)) {
            return (bool) $schedule();
        }
        $zone = is_string($this->timezone) ? new \DateTimeZone($this->timezone) : $this->timezone;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $schedule, $zone);
        if ($date !== false && $date->format('Y-m-d H:i:s') === $schedule) {
            return $date->format('Y-m-d H:i') === new \DateTimeImmutable('now', $zone)->format('Y-m-d H:i');
        }
        return new CronExpression($schedule)->isDue(new \DateTimeImmutable('now', $zone));
    }

    /**
     * @throws RandomException
     * @throws JsonException
     */
    public function createItem(): string
    {
        if ($this->queue->name === '' || $this->queue->executions <= 0) {
            throw new \InvalidArgumentException('Jobs require a name and a positive maximum attempt count.');
        }
        $object = JobSerializer::encode($this->queue);
        return $this->mutate(function (array &$items) use ($object): string {
            $id = bin2hex(random_bytes(16));
            $items[] = [
                '_id' => $id, 'name' => $this->queue->name, 'object' => $object,
                'created' => time(), 'expire' => 0, 'executions' => 0,
                'max_attempts' => $this->queue->executions, 'failed' => false, 'lease' => null,
            ];
            return $id;
        });
    }

    public function numberOfItems(): int
    {
        return count($this->items());
    }

    /** @return list<array<string, mixed>> Includes failed jobs for inspection. */
    public function items(): array
    {
        return $this->mutate(fn (array &$items): array => array_values(array_filter(
            $items,
            fn (array $item): bool => $item['name'] === $this->queue->name
        )), false);
    }

    /**
     * @throws RandomException
     */
    public function claimItem(int $leaseTime = 3600): array|object|bool
    {
        $leaseTime = $this->queue->leaseTime > 0 ? $this->queue->leaseTime : $leaseTime;
        if ($leaseTime <= 0) {
            throw new \InvalidArgumentException('The queue lease must be positive.');
        }
        return $this->mutate(function (array &$items) use ($leaseTime): array|bool {
            foreach ($items as &$item) {
                if ($item['name'] !== $this->queue->name || ($item['failed'] ?? false) || $item['expire'] > time()) {
                    continue;
                }
                if ($item['executions'] >= ($item['max_attempts'] ?? $this->queue->executions)) {
                    $item['failed'] = true;
                    continue;
                }
                $item['expire'] = time() + $leaseTime;
                $item['lease'] = bin2hex(random_bytes(16));
                ++$item['executions'];
                return $item;
            }
            return false;
        });
    }

    public function deleteItem(mixed $item): void
    {
        $this->mutate(function (array &$items) use ($item): void {
            foreach ($items as $key => $stored) {
                if ($this->owns($stored, $item)) {
                    unset($items[$key]);
                    return;
                }
            }
        });
    }

    public function releaseItem(mixed $item): bool
    {
        return $this->mutate(function (array &$items) use ($item): bool {
            foreach ($items as &$stored) {
                if ($this->owns($stored, $item)) {
                    $stored['expire'] = 0;
                    $stored['lease'] = null;
                    $stored['failed'] = $stored['executions'] >= ($stored['max_attempts'] ?? $this->queue->executions);
                    return true;
                }
            }
            return false;
        });
    }

    public function deleteQueue(): void
    {
        $this->mutate(function (array &$items): void {
            $items = array_filter($items, fn (array $item): bool => $item['name'] !== $this->queue->name);
        });
    }

    public function garbageCollection(): void
    {
        $this->mutate(function (array &$items): void {
            foreach ($items as &$item) {
                if ($item['name'] === $this->queue->name && $item['expire'] > 0 && $item['expire'] <= time()) {
                    $item['expire'] = 0;
                    $item['lease'] = null;
                    $item['failed'] = $item['executions'] >= ($item['max_attempts'] ?? $this->queue->executions);
                }
            }
        });
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateInvalidTimeZoneException
     * @throws Throwable
     * @throws RandomException
     * @throws JsonException
     */
    public function dispatch(): bool
    {
        if (!$this->isDue($this->queue->schedule) || !$this->filtersPass()) {
            return false;
        }
        $item = $this->claimItem();
        if ($item === false) {
            return false;
        }
        try {
            if (!$this->queue instanceof SerializableJob) {
                throw new \InvalidArgumentException('Persistent jobs must implement SerializableJob.');
            }
            $job = JobSerializer::decode($item['object'], [$this->queue::class]);
            if ($job->name !== $this->queue->name) {
                throw new \UnexpectedValueException('The restored job belongs to a different queue.');
            }
            if ($job->handle()) {
                $this->deleteItem($item);
                return true;
            }
            $this->releaseItem($item);
            return false;
        } catch (\Throwable $exception) {
            $this->releaseItem($item);
            throw $exception;
        }
    }

    public function skip(callable|bool $callback): self
    {
        $this->rejects[] = is_callable($callback) ? $callback : fn () => $callback;
        return $this;
    }

    public function when(callable|bool $callback): self
    {
        $this->filters[] = is_callable($callback) ? $callback : fn () => $callback;
        return $this;
    }

    /** @param array<string, mixed> $stored */
    private function owns(array $stored, mixed $item): bool
    {
        return is_array($item) && $stored['name'] === $this->queue->name
        && $stored['_id'] === ($item['_id'] ?? null) && is_string($stored['lease'] ?? null)
        && $stored['lease'] === ($item['lease'] ?? null) && $stored['expire'] > time();
    }

    /**
     * Serialize the entire read/modify/write operation, then atomically replace the JSON file.
     *
     * @param callable(array<int, array<string, mixed>>&): mixed $callback
     */
    private function mutate(callable $callback, bool $write = true): mixed
    {
        $directory = dirname($this->file);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the queue directory.');
        }
        $lock = fopen($this->file . '.lock', 'c');
        if ($lock === false) {
            throw new \RuntimeException('Unable to open the queue lock.');
        }
        $temporary = null;
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Unable to acquire the queue lock.');
            }
            $items = is_file($this->file)
            ? json_decode(file_get_contents($this->file), true, 512, JSON_THROW_ON_ERROR)
            : [];
            if (!is_array($items)) {
                throw new \UnexpectedValueException('Invalid queue storage.');
            }
            $result = $callback($items);
            if ($write) {
                $json = json_encode(array_values($items), JSON_THROW_ON_ERROR);
                $temporary = tempnam($directory, '.queue-');
                if ($temporary === false || file_put_contents($temporary, $json) !== strlen($json)) {
                    throw new \RuntimeException('Unable to write queue storage.');
                }
                if (!rename($temporary, $this->file)) {
                    throw new \RuntimeException('Unable to replace queue storage.');
                }
            }
            return $result;
        } finally {
            if (is_string($temporary) && is_file($temporary)) {
                unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
