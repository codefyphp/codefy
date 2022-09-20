<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console;

use Codefy\Foundation\Application;
use Qubus\Exception\Data\TypeException;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function method_exists;

abstract class ConsoleCommand extends SymfonyCommand
{
    protected string $name = '';
    protected string $description = '';
    protected string $help = '';
    /* Refers to name, type, description argument. */
    protected array $args = [];
    /* Refers to name, shortcut, type, description and default. */
    protected array $options = [];
    protected InputInterface $input;
    protected OutputInterface $output;

    public function __construct(protected Application $codefy)
    {
        $this->setName(name: $this->name)
            ->setDescription(description: $this->description)
            ->setHelp(help: $this->help);
        parent::__construct();
    }

    /**
     * Configure commands.
     *
     * @return void
     */
    protected function configure(): void
    {
        try {
            $this->setArguments();
            $this->setOptions();
        } catch (TypeException) {
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $method = method_exists(object_or_class: $this, method: 'handle') ? 'handle' : '__invoke';

        $classParameters = [];

        $parameters = new ReflectionMethod(objectOrMethod: $this, method: $method);

        foreach ($parameters->getParameters() as $arg) {
            $classParameters[] = $arg->getType()->getName();
        }

        if ($parameters->getNumberOfParameters() > 0) {
            return (int) $this->codefy->execute(callableOrMethodStr: [$this, $method], args: $classParameters);
        }

        return (int) $this->codefy->execute(callableOrMethodStr: [$this, $method]);
    }

    /**
     * Returns the argument value for the given argument name.
     *
     * @param string|null $key
     * @return mixed
     */
    protected function getArgument(?string $key = null): mixed
    {
        return $this->input->getArgument(name: $key) ?? '';
    }

    /**
     * Returns the option value for the given option name.
     *
     * @param string|null $key
     * @return bool|string|string[]
     */
    protected function getOptions(?string $key = null): mixed
    {
        return $this->input->getOption(name: $key) ?? '';
    }

    /**
     * Outputs the string to the console without any tag.
     *
     * @param string $string
     * @return mixed
     */
    protected function terminalRaw(string $string): mixed
    {
        return $this->output->writeln(messages: $string);
    }

    /**
     * Output to the terminal wrap in info tags.
     *
     * @param string $string
     * @return string
     */
    protected function terminalInfo(string $string): mixed
    {
        return $this->output->writeln(messages: '<info>' . $string . '</info>');
    }

    /**
     * Output to the terminal wrap in comment tags.
     *
     * @param string $string
     * @return string
     */
    protected function terminalComment(string $string): mixed
    {
        return $this->output->writeln(messages: '<comment>' . $string . '</comment>');
    }

    /**
     * Output to the terminal wrap in question tags.
     *
     * @param string $string
     * @return string
     */
    protected function terminalQuestion(string $string): mixed
    {
        return $this->output->writeln(messages: '<question>' . $string . '</question>');
    }

    /**
     * Output to the terminal wrap in error tags.
     *
     * @param string $string
     * @return string
     */
    protected function terminalError(string $string): mixed
    {
        return $this->output->writeln(messages: '<error>' . $string . '</error>');
    }

    /**
     * $arg[0] = argument name, $arg[1] = argument type and $arg[2] = argument description.
     *
     * @return ConsoleCommand|bool
     * @throws TypeException
     */
    private function setArguments(): ConsoleCommand|bool
    {
        if (count($this->args) <= 0) {
            return false;
        }

        foreach ($this->args as $arg) {
            return match ($arg[1]) { /* match based on the argument type */
                'required' => $this->addArgument(name: $arg[0], mode: InputArgument::REQUIRED, description: $arg[2]),
                'optional' => $this->addArgument(name: $arg[0], mode: InputArgument::OPTIONAL, description: $arg[2]),
                'array' => $this->addArgument(name: $arg[0], mode: InputArgument::IS_ARRAY, description: $arg[2]),
                default => throw new TypeException(message: 'Invalid input argument passed.')
            };
        }

        return true;
    }

    /**
     * @return bool|ConsoleCommand
     * @throws TypeException
     */
    private function setOptions(): bool|ConsoleCommand
    {
        if (count($this->options) <= 0) {
            return false;
        }

        foreach ($this->options as $option) {
            return match ($option[2]) {
                'none' => $this->addOption(
                    name: $option[0],
                    shortcut: $option[1],
                    mode: InputOption::VALUE_NONE,
                    description: $option[3]
                ),
                'required' => $this->addOption(
                    name: $option[0],
                    shortcut: $option[1],
                    mode: InputOption::VALUE_REQUIRED,
                    description: $option[3]
                ),
                'optional' => $this->addOption(
                    name:  $option[0],
                    shortcut:  $option[1],
                    mode: InputOption::VALUE_OPTIONAL,
                    description: $option[3]
                ),
                'array' => $this->addOption(
                    name: $option[0],
                    shortcut: $option[1],
                    mode: InputOption::VALUE_IS_ARRAY,
                    description: $option[3]
                ),
                'negatable' => $this->addOption(
                    name: $option[0],
                    shortcut: $option[1],
                    mode: InputOption::VALUE_NEGATABLE,
                    description: $option[3]
                ),
                default => throw new TypeException(message: 'Invalid input argument passed.')
            };
        }

        return true;
    }
}
