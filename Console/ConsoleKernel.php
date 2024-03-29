<?php

declare(strict_types=1);

namespace Codefy\Framework\Console;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleApplication as Codex;
use Codefy\Framework\Factory\FileLoggerSmtpFactory;
use Codefy\Framework\Scheduler\Mutex\Locker;
use Codefy\Framework\Scheduler\Schedule;
use Exception;
use Qubus\Support\DateTime\QubusDateTimeZone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Qubus\Inheritance\Helpers\tap;

class ConsoleKernel
{
    protected ?Codex $codex = null;

    protected array $commands = [];

    protected bool $commandsLoaded = false;

    protected ?Schedule $schedule = null;

    protected array $bootstrappers = [
        \Codefy\Framework\Bootstrap\RegisterProviders::class,
        \Codefy\Framework\Bootstrap\BootProviders::class,
    ];

    public function __construct(protected Application $codefy)
    {
        $this->codefy->booted(function () {
            $this->defineConsoleSchedule();
        });
    }

    protected function defineConsoleSchedule(): void
    {
        $this->codefy->share(nameOrInstance: Schedule::class);

        $this->codefy->prepare(name: Schedule::class, callableOrMethodStr: function () {
            $timeZone = new QubusDateTimeZone(timezone: $this->codefy->make(
                name: 'codefy.config'
            )->getConfigKey('app.timezone'));

            $mutex = $this->codefy->make(name: Locker::class);

            return tap(value: new Schedule(timeZone: $timeZone, mutex: $mutex), callback: function ($schedule) {
                $this->schedule(schedule: $schedule);
            });
        });

        $this->schedule = $this->codefy->make(name: Schedule::class);
    }

    /**
     * @throws Exception
     */
    public function handle(InputInterface $input, ?OutputInterface $output = null): int
    {
        try {
            $this->bootstrap();

            return $this->getCodex()->run(input: $input, output: $output);
        } catch (Throwable $ex) {
            FileLoggerSmtpFactory::critical(message: $ex->getMessage(), context: [self::class => 'handle()']);
            return 1;
        }
    }

    protected function schedule(Schedule $schedule): void
    {
        //
    }

    protected function commands(): void
    {
        //
    }

    /**
     * Registers a command.
     *
     * @param Command $command
     * @return void
     */
    public function registerCommand(Command $command): void
    {
        $this->getCodex()->add(command: $command);
    }

    /**
     * Gets all the commands registered.
     *
     * @return array
     */
    public function all(): array
    {
        $this->bootstrap();

        return $this->getCodex()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output(): string
    {
        $this->bootstrap();

        return $this->getCodex()->output();
    }

    /**
     * Bootstrap the console kernel.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        if (! $this->codefy->hasBeenBootstrapped()) {
            $this->codefy->bootstrapWith($this->bootstrappers());
        }

        if (false === $this->commandsLoaded) {
            $this->commands();

            $this->commandsLoaded = true;
        }
    }

    /**
     * Retrieve the Codex instance.
     *
     * @return Codex
     */
    protected function getCodex(): Codex
    {
        if (null === $this->codex) {
            return $this->codex = new Codex(codefy: $this->codefy);
        }

        return $this->codex;
    }

    /**
     * Run a Codex console command by name.
     *
     * @param string $command
     * @param array $parameters
     * @param bool|OutputInterface|null $outputBuffer
     * @return int
     *
     * @throws CommandNotFoundException
     * @throws Exception
     */
    public function call(string $command, array $parameters = [], bool|OutputInterface $outputBuffer = null): int
    {
        $this->bootstrap();

        return $this->getCodex()->call(command: $command, parameters: $parameters, outputBuffer: $outputBuffer);
    }

    /**
     * Get the bootstrappers.
     *
     * @return string[]
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }
}
