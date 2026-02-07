<?php

declare(strict_types=1);

namespace Codefy\Framework\Console;

use Codefy\Framework\Application;
use Qubus\Exception\Data\TypeException;
use ReflectionException;
use ReflectionMethod;
use Stringable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use function count;
use function method_exists;
use function Qubus\Inheritance\Helpers\tap;
use function Qubus\Support\Helpers\collect;
use function Qubus\Support\Helpers\is_null__;
use function str_repeat;

use const PHP_EOL;

abstract class ConsoleCommand extends SymfonyCommand
{
    protected string $name = '';
    protected string $description = '';
    protected string $help = '';
    /**
     * Refers to name, type, description argument.
     *
     * @var array<mixed> $args
     */
    protected array $args = [];
    /**
     * Refers to name, shortcut, type, description and default.
     *
     * @var array<mixed> $options
     */
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
     * @throws ReflectionException|TypeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $method = method_exists(object_or_class: $this, method: 'handle') ? 'handle' : '__invoke';

        $classParameters = [];

        $parameters = new ReflectionMethod(objectOrMethod: $this, method: $method);

        foreach ($parameters->getParameters() as $arg) {
            $classParameters[] = $this->codefy->make(name: $arg->getName());
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
        return $this->input->getArgument(name: $key) ?? null;
    }

    /**
     * Returns the option value for the given option name.
     *
     * @param string|null $key
     * @return bool|string|string[]|null
     */
    protected function getOptions(?string $key = null): mixed
    {
        return $this->input->getOption(name: $key) ?? null;
    }

    /**
     * Outputs the string to the console without any tag.
     *
     * @param string $string
     */
    protected function terminalRaw(string $string): void
    {
        $this->output->writeln(messages: $string);
    }

    /**
     * Output to the terminal wrap in info tags.
     *
     * @param string $string
     */
    protected function terminalInfo(string $string): void
    {
        $this->output->writeln(messages: '<info>' . $string . '</info>');
    }

    /**
     * Output to the terminal wrap in comment tags.
     *
     * @param string $string
     */
    protected function terminalComment(string $string): void
    {
        $this->output->writeln(messages: '<comment>' . $string . '</comment>');
    }

    /**
     * Output to the terminal wrap in question tags.
     *
     * @param string $string
     */
    protected function terminalQuestion(string $string): void
    {
        $this->output->writeln(messages: '<question>' . $string . '</question>');
    }

    /**
     * Output to the terminal wrap in error tags.
     *
     * @param string $string
     */
    protected function terminalError(string $string): void
    {
        $this->output->writeln(messages: '<error>' . $string . '</error>');
    }

    /**
     * Output to the terminal with a blank line.
     *
     * @param int $count
     */
    protected function terminalNewLine(int $count = 1): void
    {
        $this->output->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * $arg[0] = argument name, $arg[1] = argument type and $arg[2] = argument description.
     *
     * @return void
     * @throws TypeException
     */
    private function setArguments(): void
    {
        if (count($this->args) <= 0) {
            return;
        }

        foreach ($this->args as $arg) {
            match ($arg[1]) { /* match based on the argument type */
                'required' => $this->addArgument(name: $arg[0], mode: InputArgument::REQUIRED, description: $arg[2]),
                'optional' => $this->addArgument(name: $arg[0], mode: InputArgument::OPTIONAL, description: $arg[2]),
                'array' => $this->addArgument(name: $arg[0], mode: InputArgument::IS_ARRAY, description: $arg[2]),
                default => throw new TypeException(message: 'Invalid input argument passed.')
            };
        }

        return;
    }

    /**
     * @param string $question
     * @return mixed
     */
    protected function confirm(string $question): mixed
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper(name: 'question');
        $question = new ConfirmationQuestion(question: $question, default: false);

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * @param string $question
     * @param bool|float|int|string|null $default
     * @return mixed
     */
    protected function ask(string $question, bool|float|int|null|string $default = null): mixed
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper(name: 'question');
        $question = new Question(question: $question, default: $default);

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * @param string $question
     * @param array<string|bool|int|float|Stringable> $choices
     * @param bool|float|int|string|null $default
     * @param string|null $message
     * @return mixed
     */
    protected function choice(
        string $question,
        array $choices,
        bool|float|int|null|string $default = null,
        ?string $message = null
    ): mixed {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper(name: 'question');
        $question = new ChoiceQuestion(question: $question, choices: $choices, default: $default);

        $question->setErrorMessage(errorMessage: $message ?? 'There is an error.');

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * @param string $question
     * @param array<string|bool|int|float|Stringable> $choices
     * @param bool|float|int|string|null $default
     * @param string|null $message
     * @return mixed
     */
    protected function multiChoice(
        string $question,
        array $choices,
        bool|float|int|null|string $default = null,
        ?string $message = null
    ): mixed {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper(name: 'question');
        $question = new ChoiceQuestion(question: $question, choices: $choices, default: $default);

        $question->setMultiselect(multiselect: true);
        $question->setErrorMessage(errorMessage: $message ?? 'There is an error.');

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * @return void
     * @throws TypeException
     */
    private function setOptions(): void
    {
        if (count($this->options) <= 0) {
            return;
        }

        foreach ($this->options as $option) {
            match ($option[2]) {
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

        return;
    }

    /**
     * Resolve the console command instance for the given command.
     *
     * @param Command|string $command
     * @return Command
     */
    protected function resolveCommand(Command|string $command): Command
    {
        if (is_string($command)) {
            if (! class_exists($command)) {
                return $this->getApplication()->find($command);
            }

            $command = $this->codefy->make($command);
        }

        if ($command instanceof SymfonyCommand) {
            $command->setApplication($this->getApplication());
        }

        return $command;
    }

    /**
     * Call another console command.
     *
     * @param Command|string $command
     * @param array<mixed> $arguments
     * @return int
     * @throws ExceptionInterface
     */
    public function call(Command|string $command, array $arguments = []): int
    {
        return $this->runCommand($command, $arguments, $this->output);
    }

    /**
     * Get the value of a command option.
     *
     * @param string|null $key
     * @return string|array<mixed>|bool|null
     */
    public function option(?string $key = null): bool|array|string|null
    {
        if (is_null__($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key) ?? null;
    }

    /**
     * Get all the options passed to the command.
     *
     * @return bool|array<mixed>|string|null
     */
    public function options(): bool|array|string|null
    {
        return $this->option();
    }

    /**
     * Run the given console command.
     *
     * @param Command|string $command
     * @param array<mixed> $arguments
     * @param OutputInterface $output
     * @return int
     * @throws ExceptionInterface
     */
    protected function runCommand(Command|string $command, array $arguments, OutputInterface $output): int
    {
        $arguments['command'] = $command;

        return $this->resolveCommand($command)->run(
            $this->createInputFromArguments($arguments),
            $output
        );
    }

    /**
     * Create an input instance from the given arguments.
     *
     * @param array<mixed> $arguments
     * @return ArrayInput
     */
    protected function createInputFromArguments(array $arguments): ArrayInput
    {
        return tap(new ArrayInput(array_merge($this->context(), $arguments)), function ($input) {
            if ($input->getParameterOption('--no-interaction')) {
                $input->setInteractive(false);
            }
        });
    }
    /**
     * Get all the context passed to the command.
     *
     * @return array<mixed>
     */
    protected function context(): array
    {
        return collect($this->option())
            ->only([
                'ansi',
                'no-ansi',
                'no-interaction',
                'quiet',
                'verbose',
            ])
            ->filter()
            ->mapWithKeys(fn ($value, $key) => ["--{$key}" => $value])
            ->all();
    }
}
