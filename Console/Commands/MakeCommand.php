<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\Commands\Traits\MakeCommandAware;
use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Console\Exceptions\MakeCommandFileAlreadyExistsException;
use League\Flysystem\FilesystemException;
use Qubus\Exception\Data\TypeException;
use RuntimeException;

class MakeCommand extends ConsoleCommand
{
    use MakeCommandAware;

    protected const FILE_EXTENSION = '.php';

    private array $errors = [];

    private array $comments = [];

    private array $info = [];

    protected string $name = 'stub:make';

    protected string $description = 'Make command can make class controllers, repositories, providers, etc...';

    protected string $help = 'Command which can generate a class file from a set of predefined stub files';

    /* @var array Stubs */
    private const STUBS = [
        'controller'    => 'App\Infrastructure\Http\Controllers',
        'repository'    => 'App\Infrastructure\Persistence\Repository',
        'provider'      => 'App\Infrastructure\Providers',
        'middleware'    => 'App\Infrastructure\Http\Middleware',
        'error'         => 'App\Infrastructure\Errors',
    ];

    protected array $args = [
        [
            'resource',
            'required',
            'What do you want to make. You can make the following: [controller, repositories, providers, etc.].'
        ],
    ];

    protected array $options = [
        [
            '--dir',
            '-d',
            'optional',
            'Specify the directory of your controller, model, aggregate, entity, etc.',
            false
        ],
    ];

    public function handle(): int
    {
        $stub = $this->getArgument(key: 'resource');
        $option = $this->getOptions(key: 'dir');

        try {
            $this->resolveResource(resource: $stub, options: $option);
            $this->terminalQuestion(string: 'Your file was created successfully');
            return ConsoleCommand::SUCCESS;
        } catch (MakeCommandFileAlreadyExistsException|TypeException|RuntimeException|FilesystemException $e) {
            $this->terminalError(string: sprintf('%s', $e->getMessage()));
        } finally {
            return ConsoleCommand::FAILURE;
        }
    }
}
