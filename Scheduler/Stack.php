<?php

declare(strict_types=1);

namespace Codefy\Foundation\Scheduler;

use Codefy\Foundation\Scheduler\Event\TaskCompleted;
use Codefy\Foundation\Scheduler\Event\TaskFailed;
use Codefy\Foundation\Scheduler\Event\TaskStarted;
use Codefy\Foundation\Scheduler\Mutex\Locker;
use Codefy\Foundation\Scheduler\Traits\MailerAware;
use DateTimeZone;
use Qubus\EventDispatcher\EventDispatcher;
use Qubus\Exception\Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_filter;
use function Qubus\Support\Helpers\is_false__;

class Stack
{
    use MailerAware;

    /** @var Task[] $tasks */
    protected array $tasks = [];

    protected array $options = [];

    protected EventDispatcher $dispatcher;

    protected Locker $mutex;

    protected DateTimeZone|string|null $timezone;

    public function __construct(EventDispatcher $dispatcher, Locker $mutex, DateTimeZone|string|null $timezone = null)
    {
        $this->dispatcher = $dispatcher;
        $this->mutex = $mutex;
        $this->timezone = $timezone;
    }

    /**
     * Add tasks to the list.
     *
     * @param Task[] $tasks
     */
    public function addTasks(array $tasks): void
    {
        foreach ($tasks as $task) {
            $this->addTask($task);
        }
    }

    /**
     * Add a task to the list.
     */
    public function addTask(Task $task): Stack
    {
        $this->tasks[] = $task;

        return $this;
    }

    /**
     * Returns an array of tasks.
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /**
     * Gets tasks that are due and can run.
     *
     * @return array
     */
    public function tasksDue(): array
    {
        $that = clone $this;

        return array_filter($this->tasks, function (Task $task) use ($that) {
            return $task->isDue($that->timezone);
        });
    }

    public function withOptions(array $options = []): self
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dispatcher'    => $this->dispatcher,
            'mutex'         => $this->mutex,
            'timezone'      => $this->timezone,
            'maxRuntime'    => 120,
            'encryption' => null,
        ]);
    }

    public function run(): void
    {
        $schedule = new Schedule($this->options['timezone']);

        foreach ($this->tasks() as $task) {
            if (is_false__($task->getOption('enabled'))) {
                continue;
            }

            $this->dispatcher->dispatch(TaskStarted::EVENT_NAME);

            try {
                // Run code before executing task.
                $task->setUp();
                // Execute task.
                $task->execute($schedule);
                // Run code after executing task.
                $task->tearDown();
            } catch (Exception $ex) {
                $this->dispatcher->dispatch(TaskFailed::EVENT_NAME);
                $this->sendEmail($ex);
            }

            $this->dispatcher->dispatch(TaskCompleted::EVENT_NAME);
        }
    }
}
