<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler;

use Codefy\Framework\Scheduler\Event\TaskCompleted;
use Codefy\Framework\Scheduler\Event\TaskFailed;
use Codefy\Framework\Scheduler\Event\TaskStarted;
use Codefy\Framework\Scheduler\Expressions\Expressional;
use Codefy\Framework\Scheduler\Processor\BaseProcessor;
use Codefy\Framework\Scheduler\Traits\MailerAware;
use Codefy\Framework\Scheduler\ValueObject\TaskId;
use Closure;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Qubus\Exception\Data\TypeException;

use function is_string;
use function Qubus\Support\Helpers\is_false__;
use function strtotime;
use function time;

abstract class BaseTask extends BaseProcessor implements Task
{
    use MailerAware;

    protected ?TaskId $pid = null;

    protected array $options = [];

    protected string|null $timeZone = null;

    /** @var callable[]|Closure[] $filters */
    protected array $filters = [];

    /** @var callable[]|Closure[] $rejects */
    protected array $rejects = [];

    protected ?Schedule $schedule = null;

    protected ?EventDispatcherInterface $dispatcher = null;

    /**
     * @throws TypeException
     */
    public function withOptions(array $options): self
    {
        $new  = clone $this;
        $new->options = $options + [
            'expression'     => null,
            'maxRuntime'     => 120,
            'recipients'     => null,
            'smtpSender'     => null,
            'smtpSenderName' => null,
            'enabled'        => true,
            ];

        if ($new->options['expression'] instanceof Expressional) {
            $new->expression = $new->options['expression'];
        }

        if (is_string($new->options['expression'])) {
            $new->expression = $new->alias($new->options['expression']);
        }

        return $new;
    }

    public function withScheduler(Schedule $schedule): self
    {
        $new = clone $this;
        $new->schedule = $schedule;

        return $new;
    }

    public function withDispatcher(EventDispatcherInterface $dispatcher): self
    {
        $new = clone $this;
        $new->dispatcher = $dispatcher;

        return $new;
    }

    public function getOption(?string $option): mixed
    {
        if ($option === null) {
            return $this->options;
        }

        return $this->options[$option] ?? null;
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
     * Check if event should not run.
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
     * Checks whether the task can and should run.
     */
    private function shouldRun(): bool
    {
        if (is_false__($this->options['enabled'])) {
            return false;
        }

        // If task overlaps don't execute.
        if (
                $this->canRunOnlyOneInstance() &&
                ! $this->mutex->tryLock($this)
        ) {
            return false;
        }

        return true;
    }

    public function run(): bool
    {
        if (!$this->shouldRun()) {
            return false;
        }

        $this->dispatcher->dispatch(event: new TaskStarted($this));

        try {
            // Run code before executing task.
            $this->setUp();
            // Execute task.
            $this->execute($this->schedule);
            // Run code after executing task.
            $this->tearDown();
        } catch (Exception $ex) {
            $this->dispatcher->dispatch(event: new TaskFailed($this));
            $this->sendEmail($ex);
        }

        $this->dispatcher->dispatch(event: new TaskCompleted($this));

        return true;
    }
}
