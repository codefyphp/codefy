<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

use Codefy\Framework\Scheduler\Mutex\Locker;
use Codefy\Framework\Scheduler\Processor\Callback;
use Codefy\Framework\Scheduler\Processor\Processor;
use Codefy\Framework\Scheduler\Processor\Shell;
use Codefy\Framework\Scheduler\Traits\LiteralAware;
use Cron\CronExpression;
use Psr\EventDispatcher\EventDispatcherInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeZone;

use function array_filter;
use function count;
use function escapeshellarg;
use function file_exists;
use function is_callable;
use function is_string;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;

use const PHP_BINARY;

class Schedule
{
    use LiteralAware;

    public const int SUNDAY = 0;
    public const int MONDAY = 1;
    public const int TUESDAY = 2;
    public const int WEDNESDAY = 3;
    public const int THURSDAY = 4;
    public const int FRIDAY = 5;
    public const int SATURDAY = 6;

    //phpcs:disable
    /** @var Processor[] $processors */
    protected array $processors = [] {
        &get => $this->processors;
    }

    /** @var Processor[] $executedProcessors */
    public protected(set) array $executedProcessors = [] {
        &get => $this->executedProcessors;
    }

    /** @var FailedProcessor[] $failedProcessors */
    public protected(set) array $failedProcessors = [] {
        &get => $this->failedProcessors;
    }

    //phpcs:enable

    public function __construct(
        public readonly EventDispatcherInterface $dispatcher,
        public readonly QubusDateTimeZone $timeZone,
        public readonly Locker $mutex
    ) {
    }

    /**
     * Add a single Processor to the stack.
     */
    public function queueProcessor(Processor $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    /**
     * For running Codex commands.
     *
     * @param callable|string $command
     * @param array<string, string> $args
     * @return Shell|Callback
     */
    public function command(callable|string $command, array $args = []): Shell|Callback
    {
        if (is_callable($command)) {
            $command = new Callback($this->mutex, $command, $args, $this->timeZone);
        } else {
            $codexCommand = sprintf('%s codex %s', PHP_BINARY, $command);
            $command = new Shell($this->mutex, $codexCommand, $args, $this->timeZone);
        }

        $this->queueProcessor($command);

        return $command;
    }

    /**
     * @param string $script
     * @param string|null $bin
     * @param array<string, string> $args
     * @return Shell
     */
    public function php(string $script, ?string $bin = null, array $args = []): Shell
    {
        $bin = ! is_null__($bin) && is_string($bin) && file_exists($bin) ?
        $bin : PHP_BINARY;

        $command = $bin . ' ' . $script;

        if (count($args)) {
            $command .= $this->compileArguments($args);
        }

        $command = new Shell($this->mutex, $command, $args, $this->timeZone);

        if (! file_exists($script)) {
            $this->pushFailedProcessor(
                $command,
                new TypeException(message: 'The script should be a valid path to a file.')
            );
        }

        $this->queueProcessor($command);

        return $command;
    }

    /**
     * @param class-string $task
     * @param array<array-key, array<mixed>|int|string|bool|CronExpression|null> $options
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function task(string $task, array $options = []): Task
    {
        $instance = new \ReflectionClass($task);
        /** @var Task $task */
        $task = $instance->newInstanceArgs([$this->mutex, '', [], $this->timeZone]);
        $task
            ->withOptions($options)
            ->withScheduler($this)
            ->withDispatcher($this->dispatcher);

        $this->queueProcessor($task);

        return $task;
    }

    /**
     * @return Processor[]
     */
    public function allProcessors(): array
    {
        return $this->processors;
    }

    /**
     * Get all the processors on the schedule that are due.
     *
     * @return Processor[]
     */
    public function dueProcessors(): array
    {
        return array_filter($this->processors, function (Processor $processor): bool {
            return $processor->isDue($this->timeZone);
        });
    }

    public function run(): void
    {
        foreach ($this->dueProcessors() as $processor) {
            try {
                $processor->run();
                $this->pushExecutedProcessor(processor: $processor);
            } catch (\Qubus\Exception\Exception $ex) {
                $this->pushFailedProcessor($processor, $ex);
            }
        }
    }

    /**
     * Reset all collected data of last run.
     *
     * Call before run() if you call run() multiple times.
     */
    public function resetRun(): static
    {
        // Reset collected data of last run
        $this->executedProcessors = [];
        $this->failedProcessors = [];

        return $this;
    }

    /**
     * Push a successfully executed process.
     */
    private function pushExecutedProcessor(Processor $processor): Processor
    {
        $this->executedProcessors[] = $processor;

        return $processor;
    }

    /**
     * Push a failed process.
     */
    private function pushFailedProcessor(Processor $processor, \Qubus\Exception\Exception $ex): Processor
    {
        $this->failedProcessors[] = new FailedProcessor($processor, $ex);

        return $processor;
    }

    /**
     * Compile the Task command.
     *
     * @param array<string, string> $args
     */
    protected function compileArguments(array $args = []): string
    {
        $compiled = '';

        // Sanitize command arguments.
        foreach ($args as $key => $value) {
            $compiled .= ' ' . escapeshellarg($key);
            if (! is_null__($value)) {
                $compiled .= ' ' . escapeshellarg($value);
            }
        }

        return $compiled;
    }
}
