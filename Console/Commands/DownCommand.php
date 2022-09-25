<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;

class DownCommand extends PhpMigCommand
{
    protected string $name = 'migrate:down';

    protected function configure(): void
    {
        parent::configure();

        $this->addArgument(
            name: 'version',
            mode: InputArgument::REQUIRED,
            description: 'The version number for the migration.'
        )
            ->setDescription(description: 'Revert a specific migration.')
            ->setHelp(
                help: <<<EOT
The <info>migrate:down</info> command reverts a specific migration
<info>php codex migrate:down 20111018185412</info>
EOT
            );
    }

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->bootstrap(input: $this->input, output: $this->output);

        $migrations = $this->getMigrations();
        $versions   = $this->getAdapter()->fetchAll();

        $version = $this->getArgument(key: 'version');

        if (!in_array(needle: $version, haystack: $versions)) {
            return ConsoleCommand::SUCCESS;
        }

        if (!isset($migrations[$version])) {
            return ConsoleCommand::SUCCESS;
        }

        $container = $this->getObjectMap();
        $container['phpmig.migrator']->down($migrations[$version]);

        return ConsoleCommand::SUCCESS;
    }
}
