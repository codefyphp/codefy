<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands\Domain;

use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use League\Flysystem\FilesystemException;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\FileSystem\FileSystem;
use Symfony\Component\Console\Input\InputArgument;

use function dirname;
use function Qubus\Support\Helpers\windows_os;

abstract class GeneratorCommand extends ConsoleCommand
{
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type;

    public function __construct(
        protected Application $codefy,
        protected FileSystem $filesystem,
    ) {
        parent::__construct(codefy: $codefy);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    abstract protected function getStub(): string;

    /**
     * @return int
     * @throws Exception
     * @throws FilesystemException
     * @throws TypeException
     */
    public function handle(): int
    {
        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        if (
            (! $this->input->hasOption('force') ||
                ! $this->input->getOption('force')) &&
                $this->alreadyExists($this->getNameInput())
        ) {
            $this->terminalError($this->type . ' already exists.');

            return ConsoleCommand::FAILURE;
        }

        $this->makeDirectory($path);

        $this->filesystem->putContents($path, $this->sortImports($this->buildClass($name)));

        $info = $this->type;

        if (windows_os()) {
            $path = str_replace('/', '\\', $path);
        }

        $this->terminalInfo(sprintf('%s [%s] created successfully.', $info, $path));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param string $name
     * @return string
     * @throws TypeException
     */
    protected function qualifyClass(string $name): string
    {
        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if ($this->codefy->string->startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace;
    }

    /**
     * Determine if the class already exists.
     *
     * @param string $rawName
     * @return bool
     * @throws TypeException
     * @throws NotFoundException
     */
    protected function alreadyExists(string $rawName): bool
    {
        return $this->filesystem->exists($this->getPath($this->qualifyClass($rawName)), throw: false);
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     * @throws TypeException
     */
    protected function getPath(string $name): string
    {
        $name = $this->codefy->string->replaceFirst($this->rootNamespace(), '', $name);

        return $this->codefy->path() . '/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param string $path
     * @return string
     * @throws FilesystemException
     * @throws Exception
     */
    protected function makeDirectory(string $path): string
    {
        if (! $this->filesystem->directoryExists(dirname($path))) {
            $this->filesystem->mkdir(path: dirname($path), permissions: 0755, recursive: true);
        }

        return $path;
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     * @throws TypeException
     */
    protected function buildClass(string $name): string
    {
        $stub = $this->filesystem->getContents($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return $this
     * @throws TypeException
     */
    protected function replaceNamespace(string &$stub, string $name): static
    {
        $searches = [
            ['DummyNamespace', 'DummyRootNamespace'],
            ['{{ namespace }}', '{{ rootNamespace }}'],
            ['{{namespace}}', '{{rootNamespace}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$this->getNamespace($name), $this->rootNamespace()],
                $stub
            );
        }

        return $this;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     *
     * @param string $name
     * @return string
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return string
     */
    protected function replaceClass(string $stub, string $name): string
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        return str_replace(['DummyClass', '{{ class }}', '{{class}}'], $class, $stub);
    }

    /**
     * Alphabetically sorts the imports for the given stub.
     *
     * @param string $stub
     * @return string
     */
    protected function sortImports(string $stub): string
    {
        if (preg_match('/(?P<imports>(?:^use [^;{]+;$\n?)+)/m', $stub, $match)) {
            $imports = explode("\n", trim($match['imports']));

            sort($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $stub);
        }

        return $stub;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        $name = trim($this->input->getArgument(name: 'name'));

        if ($this->codefy->string->endsWith($name, '.php')) {
            return $this->codefy->string->substr($name, 0, -4);
        }

        return $name;
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     * @throws TypeException
     */
    protected function rootNamespace(): string
    {
        return $this->codefy->getNamespace();
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, int|string>>
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the ' . strtolower($this->type)],
        ];
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<array-key, mixed>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => [
                'What should the ' . strtolower($this->type) . ' be named?',
                match ($this->type) {
                    'Console command' => 'E.g. SendEmailsCommand',
                    'Controller' => 'E.g. UserController',
                    'Exception' => 'E.g. InvalidOrderException',
                    'Error' => 'E.g. PostError',
                    'Task' => 'E.g. ProcessPodcast',
                    'Middleware' => 'E.g. EnsureTokenIsValidMiddleware',
                    'Model' => 'E.g. Post',
                    'Provider' => 'E.g. ElasticServiceProvider',
                    'Request' => 'E.g. CreateUserRequest',
                    'Rule' => 'E.g. Uppercase',
                    'Test' => 'E.g. UserTest',
                    default => '',
                },
            ],
        ];
    }
}
