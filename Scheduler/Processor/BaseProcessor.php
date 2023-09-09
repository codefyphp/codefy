<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Processor;

use Codefy\Framework\Scheduler\Mutex\Locker;
use Codefy\Framework\Scheduler\Traits\ExpressionAware;
use Cron\CronExpression;
use DateTimeZone;
use Exception;

use function escapeshellarg;
use function is_callable;
use function is_string;
use function pclose;
use function popen;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function Qubus\Support\Helpers\windows_os;
use function serialize;
use function sha1;
use function substr;
use function trim;

abstract class BaseProcessor implements Processor
{
    use ExpressionAware;

    protected Locker $mutex;

    /** @var callable|string $command */
    protected $command;

    protected string $description;

    protected DateTimeZone|string $timezone;

    /** @var array $args */
    protected array $args = [];

    /** @var array $filters */
    protected array $filters = [];

    /** @var array $rejects */
    protected array $rejects = [];

    /** @var callable[] $beforeCallbacks */
    protected array $beforeCallbacks = [];

    /** @var callable[] $afterCallbacks */
    protected array $afterCallbacks = [];

    protected bool $runInBackground = true;

    protected bool $preventOverlapping = false;

    protected int $expiresAfter = 120;

    public function __construct(
        Locker $mutex,
        callable|string $command,
        ?array $args = null,
        DateTimeZone|string|null $timezone = null
    ) {
        $this->mutex = $mutex;
        $this->command = $command;
        $this->args = $args ?? [];
        $this->timezone = $timezone;
    }

    /**
     * Unique name to use for a mutually exclusive lock.
     */
    public function mutexName(): string
    {
        return substr(sha1(serialize($this->getExpression() . $this->command)), 0, 8);
    }

    /**
     * Set arguments for the command.
     *
     * @param array|null $args
     * @return BaseProcessor
     */
    public function withArgs(?array $args = null): self
    {
        $this->args = $args ?? [];
        return $this;
    }

    /**
     * Set a command description.
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    protected function runCommandInForeground(): string
    {
        $command = $this->command;

        return trim($command);
    }

    protected function runCommandInBackground(): string
    {
        $command = $this->compile();

        if (windows_os()) {
            $command = pclose(popen("start /B " . $command, "r"));
        } else {
            $command = '(' . $command . ') > /dev/null 2>&1 &';
        }

        return trim($command);
    }

    /**
     * Force the command to run in foreground.
     */
    public function runInForeground(): self
    {
        $this->runInBackground = false;

        return $this;
    }

    /**
     * Check if the command can run in background.
     */
    public function canRunCommandInBackground(): bool
    {
        if (is_callable($this->command) || $this->runInBackground === false) {
            return false;
        }

        return true;
    }

    /**
     * Call all the before callbacks for the command.
     */
    public function callBeforeCallbacks(): void
    {
        foreach ($this->beforeCallbacks as $callback) {
            $this->call($callback);
        }
    }

    /**
     * Call all the after callbacks for the command.
     */
    public function callAfterCallbacks(): void
    {
        foreach ($this->afterCallbacks as $callback) {
            $this->call($callback);
        }
    }

    /**
     * Truth test to determine if a command should run when it is due.
     */
    public function when(callable|bool $callback): self
    {
        $this->filters[] = is_callable($callback) ? $callback : fn () => $callback;

        return $this;
    }

    /**
     * Truth test to determine if a command should run when it is due.
     */
    public function skip(callable|bool $callback): self
    {
        $this->rejects[] = is_callable($callback) ? $callback : fn () => $callback;

        return $this;
    }

    /**
     * Set a function to be called before a command is executed.
     */
    public function before(callable $fn): self
    {
        $this->beforeCallbacks[] = $fn;

        return $this;
    }

    /**
     * Set a function to be called after a command is executed.
     */
    public function after(callable $fn): self
    {
        return $this->then($fn);
    }

    /**
     * Set a function to be called after a command is executed.
     */
    public function then(callable $fn, bool $runInBackground = false): self
    {
        $this->afterCallbacks[] = $fn;

        if (is_false__($runInBackground)) {
            $this->runInForeground();
        }

        return $this;
    }

    /**
     * Prevents commands from overlapping.
     *
     * @param int $expiresAfter The amount of time in seconds the mutex lock should live.
     */
    public function onlyOneInstance(int $expiresAfter = 120): self
    {
        $this->preventOverlapping = true;

        $this->expiresAfter = $expiresAfter;

        return $this->skip(function () {
            return $this->mutex->hasLock($this);
        });
    }

    /**
     * Check if process con only have one instance.
     *
     * @return bool
     */
    public function canRunOnlyOneInstance(): bool
    {
        return $this->preventOverlapping;
    }

    public function maxRuntime(): int
    {
        return $this->expiresAfter;
    }

    /**
     * Gets the current cron expression for the task.
     */
    public function getExpression(): ?string
    {
        if ($this->expression instanceof CronExpression) {
            $expression = $this->expression->getExpression();
        }

        if (is_string($this->expression)) {
            $expression = $this->expression;
        }
        return $expression;
    }

    /**
     * Determine if the given command should run based on the Cron expression.
     * @throws Exception
     */
    public function isDue(string|DateTimeZone|null $timeZone = null): bool
    {
        return $this->expressionPasses($timeZone) && $this->filtersPass();
    }

    /**
     * Compile the Task command.
     */
    protected function compile(): string
    {
        $compiled = $this->command;

        if (is_callable($compiled)) {
            return $compiled;
        }

        // Sanitize command arguments.
        foreach ($this->args as $key => $value) {
            $compiled .= ' ' . escapeshellarg($key);
            if (! is_null__($value)) {
                $compiled .= ' ' . escapeshellarg($value);
            }
        }

        return $compiled;
    }
}
