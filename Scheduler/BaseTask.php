<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

use Codefy\Framework\Scheduler\Expressions\Expressional;
use Codefy\Framework\Scheduler\Processor\BaseProcessor;
use Codefy\Framework\Scheduler\ValueObject\TaskId;
use Closure;
use DateTimeZone;
use Exception as NativeException;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

use function call_user_func;
use function date_default_timezone_get;
use function is_string;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;
use function strtotime;
use function time;

abstract class BaseTask extends BaseProcessor implements Task
{
    protected bool $acquireLock = false;

    protected mixed $lockValue;

    protected TaskId $pid;

    protected array $options;

    protected bool $truthy;

    protected string|null $timeZone = null;

    /** @var callable $whenOverlapping */
    protected $whenOverlapping;

    /** @var callable[]|Closure[] $filters */
    protected array $filters = [];

    /** @var callable[]|Closure[] $rejects */
    protected array $rejects = [];

    protected Schedule $schedule;

    /**
     * @throws TypeException
     */
    public function withOptions(array $options): self
    {
        $this->options = $options + [
                'schedule'       => null,
                'maxRuntime'     => 120,
                'recipients'     => null,
                'smtpSender'     => null,
                'smtpSenderName' => null,
                'enabled'        => false,
            ];

        if ($this->options['schedule'] instanceof Expressional) {
            $this->expression = $this->options['schedule'];
        }

        if (is_string($this->options['schedule'])) {
            $this->expression = $this->alias($this->options['schedule']);
        }

        return $this;
    }

    public function getOption(?string $option): mixed
    {
        return $this->options[$option];
    }

    public function withTimezone(?string $timeZone): self
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * @throws TypeException
     */
    public function pid(): TaskId
    {
        $this->pid = TaskId::fromString();

        return $this->pid;
    }

    /**
     * Called before a task is executed.
     */
    public function setUp(): void
    {
    }

    /**
     * Executes a task.
     */
    abstract public function execute(Schedule $schedule): void;

    /**
     * Called after a task is executed.
     */
    public function tearDown(): void
    {
    }

    /**
     * Check if the Task is overlapping.
     * @throws TypeException
     */
    public function isOverlapping(): bool
    {
        return $this->acquireLock &&
        $this->cache->hasItem($this->pid()) &&
        call_user_func($this->whenOverlapping, $this->lockValue) === false;
    }

    /**
     * Check if time hasn't arrived.
     *
     * @param string $datetime
     * @return bool
     */
    protected function notYet(string $datetime): bool
    {
        return time() < strtotime($datetime);
    }

    /**
     * Check if the time has passed.
     *
     * @param string $datetime
     * @return bool
     */
    protected function past(string $datetime): bool
    {
        return time() > strtotime($datetime);
    }

    /**
     * Check if event should be run.
     *
     * @param string $datetime
     * @return self
     */
    public function from(string $datetime): self
    {
        return $this->skip(function () use ($datetime) {
            return $this->notYet($datetime);
        });
    }

    /**
     * Check if event should be not run.
     *
     * @param string $datetime
     * @return self
     */
    public function to(string $datetime): self
    {
        return $this->skip(function () use ($datetime) {
            return $this->past($datetime);
        });
    }

    /**
     * Acquires the Task lock.
     * @throws TypeException
     */
    protected function acquireTaskLock(): void
    {
        if ($this->acquireLock) {
            $item = $this->cache->getItem($this->pid());

            if (!$item->isHit()) {
                $item->set($this->options['maxRuntime'] + time());
                $item->expiresAfter($this->options['maxRuntime']);
                $this->cache->save($item);
            }

            $this->lockValue = $item->get();
        }
    }

    /**
     * Removes the Task lock.
     * @throws TypeException
     */
    protected function removeTaskLock(): bool
    {
        return $this->cache->deleteItem($this->pid());
    }

    /**
     * @throws TypeException
     * @throws Exception
     */
    public function checkMaxRuntime(): void
    {
        $maxRuntime = $this->options['maxRuntime'];
        if (is_null__($maxRuntime)) {
            return;
        }

        $runtime = $this->getLockLifetime($this->pid());
        if ($runtime < $maxRuntime) {
            return;
        }

        throw new Exception(
            sprintf(
                'Max Runtime of %s secs exceeded! Current runtime for %s: %s secs.',
                $maxRuntime,
                static::class,
                $runtime
            ),
            8712
        );
    }

    /**
     * Retrieve the Task lock lifetime value.
     */
    private function getLockLifetime(TaskId $lockKey): int
    {
        if (!$this->cache->hasItem($lockKey)) {
            return 0;
        }

        return time() - $this->lockValue;
    }

    /**
     * Checks whether the task can and should run.
     * @throws TypeException
     */
    private function shouldRun(): bool
    {
        if (is_false__($this->options['enabled'])) {
            return false;
        }

        // If task overlaps don't execute.
        if ($this->isOverlapping()) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether the task can and should run.
     * @throws TypeException
     * @throws NativeException
     */
    public function isDue(string|DateTimeZone|null $timeZone = null): bool
    {
        if (is_false__($this->shouldRun())) {
            return false;
        }

        if (is_null__($timeZone)) {
            $timeZone = date_default_timezone_get();
        }

        $isDue = $this->expression->isDue(currentTime: 'now', timeZone: $this->timeZone ?? $timeZone);

        if (parent::isDue($this->timeZone ?? $timeZone) && $isDue) {
            return true;
        }

        return false;
    }
}
