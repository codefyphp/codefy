<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\Commands\Traits\MakeCommandAware;
use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Console\Exceptions\MakeCommandFileAlreadyExistsException;
use League\Flysystem\FilesystemException;
use Qubus\Exception\Data\TypeException;

/**
 * @deprecated since 3.1, will be removed in version 4.
 * @see \Codefy\Framework\Console\Commands\Domain\MakeDomainCommand
 * @see \Codefy\Framework\Console\Commands\Domain\MakeCommand
 * @see https://codefyphp.com/docs/getting-started/codex/
 */
class MakeCommand extends ConsoleCommand
{
    use MakeCommandAware;

    protected const string FILE_EXTENSION = '.php';

    // @phpstan-ignore property.onlyWritten
    private array $errors = [];

    // @phpstan-ignore property.onlyWritten
    private array $comments = [];

    // @phpstan-ignore property.onlyWritten
    private array $info = [];

    protected string $name = 'stub:make';

    protected string $description = 'Make command can make class controllers, repositories, providers, etc...';

    protected string $help = 'Command which can generate a class file from a set of predefined stub files';

    /* @var array<string> Stubs */
    private const array STUBS = [
        'controller'    => 'App\Infrastructure\Http\Controllers',
        'repository'    => 'App\Infrastructure\Persistence\Repository',
        'provider'      => 'App\Infrastructure\Providers',
        'middleware'    => 'App\Infrastructure\Http\Middleware',
        'error'         => 'App\Infrastructure\Errors',
        'command'       => 'App\Application\Console\Commands',
        'route'         => 'App\Infrastructure\Http\Routes',
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
            $this->terminalQuestion(string: 'Your file was created successfully.');
            return self::SUCCESS;
        } catch (MakeCommandFileAlreadyExistsException | TypeException | \RuntimeException | FilesystemException $e) {
            $this->terminalError(string: sprintf('%s', $e->getMessage()));
            return self::FAILURE;
        }
    }
}
