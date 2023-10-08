<?php

declare(strict_types=1);

namespace Codefy\Framework\Console;

use Codefy\Framework\Application;
use Exception;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApplication extends SymfonyApplication
{
    private OutputInterface|string|null $lastOutput;

    public function __construct(protected Application $codefy)
    {
        parent::__construct(name: 'CodefyPHP', version: '2.0.0');
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $this->getCommandName(input: $input = $input ?: new ArgvInput());

        return parent::run(input: $input, output: $output);
    }

    /**
     * @throws Exception
     */
    public function call($command, array $parameters = [], bool|OutputInterface $outputBuffer = null): int
    {
        [$command, $input] = $this->parseCommand(command: $command, parameters: $parameters);

        if (! $this->has($command)) {
            throw new CommandNotFoundException(message: sprintf('The command "%s" does not exist.', $command));
        }

        return $this->run(
            $input,
            $this->lastOutput = ($outputBuffer instanceof OutputInterface) ? new BufferedOutput() : ''
        );
    }

    /**
     * Parse the incoming Codex command and its input.
     *
     * @param string $command
     * @param array $parameters
     * @return array
     */
    protected function parseCommand(string $command, array $parameters): array
    {
        if (empty($parameters)) {
            $command = $this->getCommandName(input: $input = new StringInput(input: $command));
        } else {
            array_unshift($parameters, $command);

            $input = new ArrayInput(parameters: $parameters);
        }

        return [$command, $input];
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output(): string
    {
        return $this->lastOutput && method_exists($this->lastOutput, 'fetch')
        ? $this->lastOutput->fetch()
        : '';
    }
}
