<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

use Codefy\Framework\Scheduler\Mutex\Locker;
use Codefy\Framework\Scheduler\Processor\Callback;
use Codefy\Framework\Scheduler\Processor\Processor;
use Codefy\Framework\Scheduler\Processor\Shell;
use Codefy\Framework\Scheduler\Traits\LiteralAware;

use DateTimeZone;

use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

use Qubus\Support\DateTime\QubusDateTimeZone;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\config;
use function array_filter;
use function count;
use function escapeshellarg;
use function file_exists;
use function is_callable;
use function is_string;
use function Qubus\Support\Helpers\is_null__;

use const PHP_BINARY;

class Schedule
{
    use LiteralAware;

    public const SUNDAY = 0;
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;

    /** @var Processor[] $processors */
    protected array $processors = [];

    protected array $executedProcessors = [];

    protected array $failedProcessors = [];

    public function __construct(public readonly QubusDateTimeZone $timeZone, public readonly Locker $mutex)
    {
    }

    /**
     * Add a single Processor to the stack.
     */
    public function queueProcessor(Processor $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    public function command(callable|string $command, array $args = []): Shell|Callback
    {
        if (is_callable($command)) {
            $command = new Callback($this->mutex, $command, $args, $this->timeZone);
        } else {
            if (count($args)) {
                $command .= $this->compileArguments($args);
            }
            $command = new Shell($this->mutex, $command, $args, $this->timeZone);
        }

        $this->queueProcessor($command);

        return $command;
    }

    public function php(string $script, ?string $bin = null, array $args = []): Shell
    {
        $bin = ! is_null__($bin) && is_string($bin) && file_exists($bin) ?
        $bin : (PHP_BINARY === '' ? '/usr/bin/php' : PHP_BINARY);

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
            } catch (Exception $ex) {
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
     * Get the executed processes.
     *
     * @return array
     */
    public function getExecutedProcessors(): array
    {
        return $this->executedProcessors;
    }

    /**
     * Push a failed process.
     */
    private function pushFailedProcessor(Processor $processor, Exception $ex): Processor
    {
        $this->failedProcessors[] = new FailedProcessor($processor, $ex);

        return $processor;
    }

    /**
     * Get the failed jobs.
     *
     * @return FailedProcessor[]
     */
    public function getFailedProcessors(): array
    {
        return $this->failedProcessors;
    }

    /**
     * Compile the Task command.
     */
    protected function compileArguments(array $args = []): string
    {
        // Sanitize command arguments.
        foreach ($args as $key => $value) {
            $compiled = ' ' . escapeshellarg($key);
            if (! is_null__($value)) {
                $compiled .= ' ' . escapeshellarg($value);
            }
        }

        return $compiled;
    }
}
