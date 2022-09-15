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
    /* refers to name, type, description argument */
    protected array $args = [];
    /* refers to name, shortcut, type, description and default */
    protected array $options = [];
    protected InputInterface $input;
    protected OutputInterface $output;

    public function __construct(protected Application $codefy)
    {
        $this->setName($this->name)
            ->setDescription($this->description)
            ->setHelp($this->help);
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
            $classParameters[] = $this->codefy->make(name: $arg->getType()->getName());
        }

        if ($parameters->getNumberOfParameters() > 0) {
            return (int) $this->codefy->call(closure: [$this, $method], parameters: $classParameters);
        }

        return (int) $this->codefy->call(closure: [$this, $method]);
    }

    /**
     * Returns the argument value for the given argument name
     * @param string|null $key
     * @return mixed
     */
    protected function getArgument(?string $key = null): mixed
    {
        return $this->input->getArgument($key) ?? '';
    }

    /**
     * Returns the option value for the given option name
     * @param string|null $key
     * @return bool|string|string[]
     */
    protected function getOptions(?string $key = null): mixed
    {
        return $this->input->getOption($key) ?? '';
    }

    /**
     * Outputs the string to the console without any tag
     * @param string $string
     * @return mixed
     */
    protected function terminalRaw(string $string): mixed
    {
        return $this->output->writeln($string);
    }

    /**
     * output to the terminal wrap in info tags
     * @param string $string
     * @return string
     */
    protected function terminalInfo(string $string): mixed
    {
        return $this->output->writeln('<info>' . $string . '</info>');
    }

    /**
     * output to the terminal wrap in comment tags
     * @param string $string
     * @return string
     */
    protected function terminalComment(string $string): mixed
    {
        return $this->output->writeln('<comment>' . $string . '</comment>');
    }

    /**
     * output to the terminal wrap in question tags
     * @param string $string
     * @return string
     */
    protected function terminalQuestion(string $string): mixed
    {
        return $this->output->writeln('<question>' . $string . '</question>');
    }

    /**
     * output to the terminal wrap in error tags
     * @param string $string
     * @return string
     */
    protected function terminalError(string $string): mixed
    {
        return $this->output->writeln('<error>' . $string . '</error>');
    }

    /**
     * $arg[0] = argument name, $arg[1] = argument type and $arg[2] = argument description
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
                'required' => $this->addArgument($arg[0], InputArgument::REQUIRED, $arg[2]),
                'optional' => $this->addArgument($arg[0], InputArgument::OPTIONAL, $arg[2]),
                'array' => $this->addArgument($arg[0], InputArgument::IS_ARRAY, $arg[2]),
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
                'none' => $this->addOption($option[0], $option[1], InputOption::VALUE_NONE, $option[3]),
                'required' => $this->addOption($option[0], $option[1], InputOption::VALUE_REQUIRED, $option[3]),
                'optional' => $this->addOption($option[0], $option[1], InputOption::VALUE_OPTIONAL, $option[3]),
                'array' => $this->addOption($option[0], $option[1], InputOption::VALUE_IS_ARRAY, $option[3]),
                'negatable' => $this->addOption($option[0], $option[1], InputOption::VALUE_NEGATABLE, $option[3]),
                default => throw new TypeException('Invalid input argument passed.')
            };
        }

        return true;
    }
}
