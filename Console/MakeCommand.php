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

    /* @var array stubs */
    private const STUBS = [
        'controller'            => 'Codefy\Foundation\Infrastructure\Http\Controllers',
        'resource-controller'   => 'Codefy\Foundation\Infrastructure\Http\Controllers',
        'rest-controller'       => 'Codefy\Foundation\Infrastructure\Http\Controllers',
        'repository'    => 'App\Repository',
        'fillable'      => 'App\Database\Fillable',
        'schema'        => 'App\Database\Schema',
        'form'          => 'App\Forms',
        'entity'        => 'App\Entity',
        'model'         => 'App\Model',
        'validate'      => 'App\Validate',
        'event'         => 'App\Event',
        'listener'      => 'App\EventListener',
        'subscriber'    => 'App\EventSubscriber',
        'middleware'    => 'App\Middleware'
    ];

    protected array $args = [
        [
            'resource',
            'required',
            'What do you want to make. You can make the following: [controller, model, aggregate, form, schema etc..'
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

    protected function handle(): int
    {
        $stub = $this->getArgument('resource');
        $option = $this->getOptions('dir');

        try {
            $this->resolveResource($stub, $option);
            $this->terminalQuestion('Your file was created successfully');
            return ConsoleCommand::SUCCESS;
        } catch (MakeCommandFileAlreadyExistsException|TypeException|RuntimeException|FilesystemException $e) {
            $this->terminalError(sprintf('%s', $e->getMessage()));
        } finally {
            return ConsoleCommand::FAILURE;
        }
    }
}
