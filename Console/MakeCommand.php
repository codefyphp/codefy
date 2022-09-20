<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console;

use Codefy\Foundation\Console\Exceptions\MakeCommandFileAlreadyExistsException;
use Codefy\Foundation\Console\Traits\MakeCommandAware;
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

    protected string $description = 'Make command can make class controllers, models, entities, forms etc...';

    protected string $help = 'Command which can generate a class file from a set of predefined stub files';

    /* @var array Stubs */
    private const STUBS = [
        'controller'               => 'App\Infrastructure\Http\Controllers',
        'resourceController'       => 'App\Infrastructure\Http\Controllers',
        'restController'           => 'App\Infrastructure\Http\Controllers',
        'aggregate'                => 'App\Domain',
        'eventSourcedAggregate'    => 'App\Domain',
        'repository'               => 'App\Infrastructure\Persistence\Repository',
        'domainEvent'              => 'App\Domain',
        'subscriber'               => 'App\Domain',
        'serviceProvider'          => 'App\Infrastructure\Providers',
    ];

    protected array $args = [
        [
            'resource',
            'required',
            'What do you want to make. You can make the following: [controller, model, aggregate, etc..'
        ],
    ];

    protected array $options = [
        [
            'dir',
            null,
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
